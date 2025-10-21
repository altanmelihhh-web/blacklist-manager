<?php
/**
 * Blacklist Manager - Source Manager
 * Handles automatic blacklist sources (URLs to fetch)
 */

class SourceManager {
    private $instance;
    private $sources_file;

    public function __construct($instance) {
        $this->instance = $instance;
        $this->sources_file = $instance['data_dir'] . '/sources.json';

        // Ensure data directory exists
        ensure_directory($instance['data_dir']);

        // Initialize sources file if not exists
        if (!file_exists($this->sources_file)) {
            $this->initializeDefaultSources();
        }
    }

    /**
     * Initialize with default sources from config
     */
    private function initializeDefaultSources() {
        $config = load_config();
        $sources = $config['default_sources'] ?? [];

        // Add metadata
        foreach ($sources as &$source) {
            $source['id'] = uniqid('src_');
            $source['last_update'] = null;
            $source['last_status'] = 'never';
            $source['entry_count'] = 0;
        }

        $this->saveSources($sources);
    }

    /**
     * Get all sources
     */
    public function getAllSources() {
        if (!file_exists($this->sources_file)) {
            return [];
        }

        $json = file_get_contents($this->sources_file);
        return json_decode($json, true) ?? [];
    }

    /**
     * Get enabled sources
     */
    public function getEnabledSources() {
        $sources = $this->getAllSources();
        return array_filter($sources, function($source) {
            return $source['enabled'] === true;
        });
    }

    /**
     * Get source by ID
     */
    public function getSource($source_id) {
        $sources = $this->getAllSources();
        foreach ($sources as $source) {
            if ($source['id'] === $source_id) {
                return $source;
            }
        }
        return null;
    }

    /**
     * Add new source
     */
    public function addSource($data) {
        $sources = $this->getAllSources();

        $source = [
            'id' => uniqid('src_'),
            'name' => $data['name'],
            'url' => $data['url'],
            'enabled' => $data['enabled'] ?? true,
            'type' => $data['type'] ?? 'plain',
            'update_interval' => (int)($data['update_interval'] ?? 86400),
            'description' => $data['description'] ?? '',
            'last_update' => null,
            'last_status' => 'never',
            'entry_count' => 0
        ];

        $sources[] = $source;
        return $this->saveSources($sources);
    }

    /**
     * Update source
     */
    public function updateSource($source_id, $data) {
        $sources = $this->getAllSources();

        foreach ($sources as &$source) {
            if ($source['id'] === $source_id) {
                $source['name'] = $data['name'] ?? $source['name'];
                $source['url'] = $data['url'] ?? $source['url'];
                $source['enabled'] = $data['enabled'] ?? $source['enabled'];
                $source['type'] = $data['type'] ?? $source['type'];
                $source['update_interval'] = (int)($data['update_interval'] ?? $source['update_interval']);
                $source['description'] = $data['description'] ?? $source['description'];

                return $this->saveSources($sources);
            }
        }

        return false;
    }

    /**
     * Delete source
     */
    public function deleteSource($source_id) {
        $sources = $this->getAllSources();
        $filtered = array_filter($sources, function($source) use ($source_id) {
            return $source['id'] !== $source_id;
        });

        return $this->saveSources(array_values($filtered));
    }

    /**
     * Toggle source enabled/disabled
     */
    public function toggleSource($source_id, $enabled) {
        $sources = $this->getAllSources();

        foreach ($sources as &$source) {
            if ($source['id'] === $source_id) {
                $source['enabled'] = (bool)$enabled;
                return $this->saveSources($sources);
            }
        }

        return false;
    }

    /**
     * Fetch and parse source
     */
    public function fetchSource($source_id) {
        $source = $this->getSource($source_id);
        if (!$source) {
            return ['success' => false, 'error' => 'Source not found'];
        }

        // Use curl to fetch
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $source['url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Blacklist-Manager/1.0');

        $content = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($content === false || $http_code !== 200) {
            $this->updateSourceStatus($source_id, 'failed', 0);
            return ['success' => false, 'error' => $error ?: 'HTTP ' . $http_code];
        }

        // Parse content
        $entries = $this->parseSourceContent($content, $source['type']);

        // Save to cache file
        $cache_file = $this->instance['data_dir'] . '/source_' . $source_id . '.txt';
        file_put_contents($cache_file, implode(PHP_EOL, $entries));

        // Update source status
        $this->updateSourceStatus($source_id, 'success', count($entries));

        return [
            'success' => true,
            'entries' => count($entries),
            'cache_file' => $cache_file
        ];
    }

    /**
     * Parse source content based on type
     */
    private function parseSourceContent($content, $type) {
        $lines = explode("\n", $content);
        $entries = [];

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines and comments
            if (empty($line) || $line[0] === '#') {
                continue;
            }

            // Parse based on type
            switch ($type) {
                case 'ipset':
                case 'netset':
                    // Just IP or CIDR
                    if (validate_ip($line)) {
                        $entries[] = $line;
                    }
                    break;

                case 'plain':
                default:
                    // Try to extract IP from line
                    if (preg_match('/\b(?:\d{1,3}\.){3}\d{1,3}(?:\/\d{1,2})?\b/', $line, $matches)) {
                        if (validate_ip($matches[0])) {
                            $entries[] = $matches[0];
                        }
                    }
                    break;
            }
        }

        return array_unique($entries);
    }

    /**
     * Update source status after fetch
     */
    private function updateSourceStatus($source_id, $status, $entry_count) {
        $sources = $this->getAllSources();

        foreach ($sources as &$source) {
            if ($source['id'] === $source_id) {
                $source['last_update'] = date('Y-m-d H:i:s');
                $source['last_status'] = $status;
                $source['entry_count'] = $entry_count;
                break;
            }
        }

        return $this->saveSources($sources);
    }

    /**
     * Get sources that need update
     */
    public function getSourcesNeedingUpdate() {
        $sources = $this->getEnabledSources();
        $needs_update = [];

        foreach ($sources as $source) {
            if ($this->shouldUpdateSource($source)) {
                $needs_update[] = $source;
            }
        }

        return $needs_update;
    }

    /**
     * Check if source should be updated
     */
    private function shouldUpdateSource($source) {
        if (!$source['enabled']) {
            return false;
        }

        if ($source['last_update'] === null) {
            return true;
        }

        $last_update_time = strtotime($source['last_update']);
        $next_update_time = $last_update_time + $source['update_interval'];

        return time() >= $next_update_time;
    }

    /**
     * Get source cache file path
     */
    public function getSourceCacheFile($source_id) {
        return $this->instance['data_dir'] . '/source_' . $source_id . '.txt';
    }

    /**
     * Get all cached entries from sources
     */
    public function getAllCachedEntries() {
        $sources = $this->getEnabledSources();
        $all_entries = [];

        foreach ($sources as $source) {
            $cache_file = $this->getSourceCacheFile($source['id']);
            if (file_exists($cache_file)) {
                $entries = file($cache_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($entries as $entry) {
                    $all_entries[] = [
                        'ip' => $entry,
                        'source' => $source['name']
                    ];
                }
            }
        }

        return $all_entries;
    }

    /**
     * Save sources to file
     */
    private function saveSources($sources) {
        $json = json_encode($sources, JSON_PRETTY_PRINT);
        return file_put_contents($this->sources_file, $json) !== false;
    }

    /**
     * Force update all enabled sources
     */
    public function updateAllSources() {
        $sources = $this->getEnabledSources();
        $results = [];

        foreach ($sources as $source) {
            $result = $this->fetchSource($source['id']);
            $results[$source['name']] = $result;
        }

        return $results;
    }
}

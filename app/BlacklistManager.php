<?php
/**
 * Blacklist Manager - Blacklist Operations
 * Handles blacklist/whitelist CRUD operations
 */

require_once __DIR__ . '/helpers.php';

class BlacklistManager {
    private $instance;
    private $blacklist_path;
    private $whitelist_path;
    private $output_path;

    public function __construct($instance) {
        $this->instance = $instance;
        $this->blacklist_path = $instance['data_dir'] . '/' . $instance['blacklist_file'];
        $this->whitelist_path = $instance['data_dir'] . '/' . $instance['whitelist_file'];
        $this->output_path = $instance['data_dir'] . '/' . $instance['output_file'];

        // Ensure data directory exists
        ensure_directory($instance['data_dir']);

        // Ensure files exist
        if (!file_exists($this->blacklist_path)) {
            touch($this->blacklist_path);
        }
        if (!file_exists($this->whitelist_path)) {
            touch($this->whitelist_path);
        }
        if (!file_exists($this->output_path)) {
            touch($this->output_path);
        }
    }

    /**
     * Get all blacklist entries
     */
    public function getBlacklist() {
        if (!file_exists($this->blacklist_path)) {
            return [];
        }

        $lines = file($this->blacklist_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $entries = [];

        foreach ($lines as $line) {
            $entry = parse_list_entry($line);
            $entries[] = $entry;
        }

        return $entries;
    }

    /**
     * Get all whitelist entries
     */
    public function getWhitelist() {
        if (!file_exists($this->whitelist_path)) {
            return [];
        }

        $lines = file($this->whitelist_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $entries = [];

        foreach ($lines as $line) {
            $entry = parse_list_entry($line);
            $entries[] = $entry;
        }

        return $entries;
    }

    /**
     * Add entry to blacklist
     */
    public function addToBlacklist($ip, $comment = '', $fqdn = '', $jira = '') {
        // Validate IP if provided
        if (!empty($ip) && $ip !== 'N/A') {
            // Add /32 if no CIDR
            if (strpos($ip, '/') === false) {
                $ip = $ip . '/32';
            }

            if (!validate_ip($ip)) {
                return ['success' => false, 'error' => 'Invalid IP address'];
            }

            // Check if protected
            if (is_protected_ip($ip, array_merge(
                $this->instance['protected_blocks'],
                $this->instance['custom_protected']
            ))) {
                return ['success' => false, 'error' => 'Cannot blacklist protected IP'];
            }

            // Check if already exists
            if ($this->ipExists($ip)) {
                return ['success' => false, 'error' => 'IP already in blacklist'];
            }
        }

        // Validate FQDN if provided
        if (!empty($fqdn) && !validate_fqdn($fqdn)) {
            return ['success' => false, 'error' => 'Invalid FQDN'];
        }

        // Create entry
        $entry = create_list_entry($ip, $comment, $fqdn, $jira);
        file_put_contents($this->blacklist_path, $entry . PHP_EOL, FILE_APPEND);

        // Update output file
        $this->regenerateOutput();

        return ['success' => true];
    }

    /**
     * Add entry to whitelist
     */
    public function addToWhitelist($ip, $comment = '', $fqdn = '', $jira = '') {
        // Validate IP if provided
        if (!empty($ip) && $ip !== 'N/A') {
            if (strpos($ip, '/') === false) {
                $ip = $ip . '/32';
            }

            if (!validate_ip($ip)) {
                return ['success' => false, 'error' => 'Invalid IP address'];
            }

            // Check if already exists
            if ($this->ipExistsInWhitelist($ip)) {
                return ['success' => false, 'error' => 'IP already in whitelist'];
            }
        }

        // Validate FQDN
        if (!empty($fqdn) && !validate_fqdn($fqdn)) {
            return ['success' => false, 'error' => 'Invalid FQDN'];
        }

        // Create entry
        $entry = create_list_entry($ip, $comment, $fqdn, $jira);
        file_put_contents($this->whitelist_path, $entry . PHP_EOL, FILE_APPEND);

        return ['success' => true];
    }

    /**
     * Remove entry from blacklist
     */
    public function removeFromBlacklist($ip) {
        $entries = file($this->blacklist_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $new_entries = [];

        foreach ($entries as $entry) {
            $parsed = parse_list_entry($entry);
            if ($parsed['ip'] !== $ip) {
                $new_entries[] = $entry;
            }
        }

        file_put_contents($this->blacklist_path, implode(PHP_EOL, $new_entries) . PHP_EOL);
        $this->regenerateOutput();

        return ['success' => true];
    }

    /**
     * Remove entry from whitelist
     */
    public function removeFromWhitelist($ip) {
        $entries = file($this->whitelist_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $new_entries = [];

        foreach ($entries as $entry) {
            $parsed = parse_list_entry($entry);
            if ($parsed['ip'] !== $ip) {
                $new_entries[] = $entry;
            }
        }

        file_put_contents($this->whitelist_path, implode(PHP_EOL, $new_entries) . PHP_EOL);

        return ['success' => true];
    }

    /**
     * Update blacklist entry
     */
    public function updateBlacklistEntry($old_ip, $new_ip, $comment = '', $fqdn = '', $jira = '') {
        $entries = file($this->blacklist_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $new_entries = [];
        $found = false;

        foreach ($entries as $entry) {
            $parsed = parse_list_entry($entry);
            if ($parsed['ip'] === $old_ip) {
                $new_entries[] = create_list_entry($new_ip, $comment, $fqdn, $jira);
                $found = true;
            } else {
                $new_entries[] = $entry;
            }
        }

        if ($found) {
            file_put_contents($this->blacklist_path, implode(PHP_EOL, $new_entries) . PHP_EOL);
            $this->regenerateOutput();
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'Entry not found'];
    }

    /**
     * Check if IP exists in blacklist
     */
    private function ipExists($ip) {
        $entries = $this->getBlacklist();
        foreach ($entries as $entry) {
            if ($entry['ip'] === $ip) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if IP exists in whitelist
     */
    private function ipExistsInWhitelist($ip) {
        $entries = $this->getWhitelist();
        foreach ($entries as $entry) {
            if ($entry['ip'] === $ip) {
                return true;
            }
        }
        return false;
    }

    /**
     * Search in blacklist
     */
    public function searchBlacklist($query) {
        $entries = $this->getBlacklist();
        $results = [];

        foreach ($entries as $entry) {
            if (stripos($entry['ip'], $query) !== false ||
                stripos($entry['comment'], $query) !== false ||
                stripos($entry['fqdn'], $query) !== false ||
                stripos($entry['jira'], $query) !== false) {
                $results[] = $entry;
            }
        }

        return $results;
    }

    /**
     * Regenerate output file (clean IPs/FQDNs only)
     */
    public function regenerateOutput() {
        $entries = $this->getBlacklist();
        $output = [];

        foreach ($entries as $entry) {
            if (!empty($entry['ip']) && $entry['ip'] !== 'N/A') {
                $output[] = $entry['ip'];
            }
            if (!empty($entry['fqdn'])) {
                $output[] = $entry['fqdn'];
            }
        }

        // Remove duplicates
        $output = array_unique($output);

        file_put_contents($this->output_path, implode(PHP_EOL, $output) . PHP_EOL);

        return count($output);
    }

    /**
     * Bulk import from array
     */
    public function bulkImport($entries) {
        $success = 0;
        $errors = [];

        foreach ($entries as $entry) {
            $result = $this->addToBlacklist(
                $entry['ip'] ?? '',
                $entry['comment'] ?? '',
                $entry['fqdn'] ?? '',
                $entry['jira'] ?? ''
            );

            if ($result['success']) {
                $success++;
            } else {
                $errors[] = ($entry['ip'] ?? $entry['fqdn']) . ': ' . $result['error'];
            }
        }

        return [
            'success' => $success,
            'errors' => $errors
        ];
    }

    /**
     * Export to CSV
     */
    public function exportToCSV() {
        $entries = $this->getBlacklist();
        $csv = "IP,Comment,Date,FQDN,Jira\n";

        foreach ($entries as $entry) {
            $csv .= sprintf('"%s","%s","%s","%s","%s"' . "\n",
                $entry['ip'],
                $entry['comment'],
                $entry['date'],
                $entry['fqdn'],
                $entry['jira']
            );
        }

        return $csv;
    }

    /**
     * Get statistics
     */
    public function getStats() {
        $blacklist = $this->getBlacklist();
        $whitelist = $this->getWhitelist();

        return [
            'blacklist_count' => count($blacklist),
            'whitelist_count' => count($whitelist),
            'output_count' => $this->regenerateOutput()
        ];
    }
}

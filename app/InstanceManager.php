<?php
/**
 * Blacklist Manager - Instance Manager
 * Handles instance CRUD operations, logo management, etc.
 */

class InstanceManager {
    private $config_file;
    private $config;

    public function __construct() {
        $this->config_file = __DIR__ . '/../config/config.php';
        $this->loadConfig();
    }

    private function loadConfig() {
        if (!file_exists($this->config_file)) {
            throw new Exception('Configuration file not found');
        }
        $this->config = require $this->config_file;
    }

    private function saveConfig() {
        $export = var_export($this->config, true);
        $content = "<?php\n/**\n * Blacklist Manager - Configuration File\n */\n\nreturn $export;";
        return file_put_contents($this->config_file, $content) !== false;
    }

    public function getAllInstances() {
        return $this->config['instances'];
    }

    public function getEnabledInstances() {
        return array_filter($this->config['instances'], function($inst) {
            return $inst['enabled'];
        });
    }

    public function getInstance($instance_id) {
        foreach ($this->config['instances'] as $instance) {
            if ($instance['id'] === $instance_id) {
                return $instance;
            }
        }
        return null;
    }

    public function updateInstanceName($instance_id, $new_name) {
        foreach ($this->config['instances'] as &$instance) {
            if ($instance['id'] === $instance_id) {
                $instance['name'] = $new_name;
                $instance['slug'] = $this->slugify($new_name);
                return $this->saveConfig();
            }
        }
        return false;
    }

    public function updateInstanceLogo($instance_id, $logo_filename) {
        foreach ($this->config['instances'] as &$instance) {
            if ($instance['id'] === $instance_id) {
                // Delete old logo if exists
                if (!empty($instance['logo'])) {
                    $old_logo = __DIR__ . '/../public/images/logos/' . $instance['logo'];
                    if (file_exists($old_logo)) {
                        unlink($old_logo);
                    }
                }
                $instance['logo'] = $logo_filename;
                return $this->saveConfig();
            }
        }
        return false;
    }

    public function deleteInstanceLogo($instance_id) {
        foreach ($this->config['instances'] as &$instance) {
            if ($instance['id'] === $instance_id) {
                if (!empty($instance['logo'])) {
                    $logo_path = __DIR__ . '/../public/images/logos/' . $instance['logo'];
                    if (file_exists($logo_path)) {
                        unlink($logo_path);
                    }
                }
                $instance['logo'] = '';
                return $this->saveConfig();
            }
        }
        return false;
    }

    public function toggleInstance($instance_id, $enabled) {
        foreach ($this->config['instances'] as &$instance) {
            if ($instance['id'] === $instance_id) {
                $instance['enabled'] = (bool)$enabled;
                return $this->saveConfig();
            }
        }
        return false;
    }

    public function updateInstanceDescription($instance_id, $description) {
        foreach ($this->config['instances'] as &$instance) {
            if ($instance['id'] === $instance_id) {
                $instance['description'] = $description;
                return $this->saveConfig();
            }
        }
        return false;
    }

    public function addProtectedIP($instance_id, $ip) {
        foreach ($this->config['instances'] as &$instance) {
            if ($instance['id'] === $instance_id) {
                if (!in_array($ip, $instance['custom_protected'])) {
                    $instance['custom_protected'][] = $ip;
                    return $this->saveConfig();
                }
            }
        }
        return false;
    }

    public function removeProtectedIP($instance_id, $ip) {
        foreach ($this->config['instances'] as &$instance) {
            if ($instance['id'] === $instance_id) {
                $key = array_search($ip, $instance['custom_protected']);
                if ($key !== false) {
                    unset($instance['custom_protected'][$key]);
                    $instance['custom_protected'] = array_values($instance['custom_protected']);
                    return $this->saveConfig();
                }
            }
        }
        return false;
    }

    public function createInstance($data) {
        $instance = [
            'id' => $data['id'] ?? uniqid('inst_'),
            'name' => $data['name'],
            'slug' => $this->slugify($data['name']),
            'logo' => '',
            'enabled' => $data['enabled'] ?? true,
            'description' => $data['description'] ?? '',
            'protected_blocks' => $data['protected_blocks'] ?? [
                '10.0.0.0/8',
                '172.16.0.0/12',
                '192.168.0.0/16',
                '127.0.0.0/8'
            ],
            'custom_protected' => [],
            'data_dir' => __DIR__ . '/../data/' . ($data['id'] ?? uniqid('inst_')),
            'blacklist_file' => 'blacklist.txt',
            'whitelist_file' => 'whitelist.txt',
            'output_file' => 'output_blacklist.txt'
        ];

        // Create data directory
        ensure_directory($instance['data_dir']);

        // Initialize files
        touch($instance['data_dir'] . '/' . $instance['blacklist_file']);
        touch($instance['data_dir'] . '/' . $instance['whitelist_file']);
        touch($instance['data_dir'] . '/' . $instance['output_file']);

        $this->config['instances'][] = $instance;
        return $this->saveConfig();
    }

    public function deleteInstance($instance_id) {
        $index = null;
        foreach ($this->config['instances'] as $idx => $instance) {
            if ($instance['id'] === $instance_id) {
                $index = $idx;
                // Delete logo if exists
                if (!empty($instance['logo'])) {
                    $logo_path = __DIR__ . '/../public/images/logos/' . $instance['logo'];
                    if (file_exists($logo_path)) {
                        unlink($logo_path);
                    }
                }
                break;
            }
        }

        if ($index !== null) {
            unset($this->config['instances'][$index]);
            $this->config['instances'] = array_values($this->config['instances']);
            return $this->saveConfig();
        }
        return false;
    }

    private function slugify($text) {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        return empty($text) ? 'instance' : $text;
    }

    public function getInstanceMode() {
        return $this->config['instance_mode'] ?? 'multi';
    }

    public function setInstanceMode($mode) {
        if (in_array($mode, ['single', 'multi'])) {
            $this->config['instance_mode'] = $mode;
            return $this->saveConfig();
        }
        return false;
    }
}

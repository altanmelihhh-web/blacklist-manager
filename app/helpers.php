<?php
/**
 * Blacklist Manager - Helper Functions
 * Core utility functions for IP validation, CIDR operations, etc.
 */

/**
 * Validate IP address or CIDR notation
 */
function validate_ip($ip) {
    if (strpos($ip, '/') !== false) {
        list($subnet, $prefix) = explode('/', $ip);
        return (filter_var($subnet, FILTER_VALIDATE_IP) &&
                is_numeric($prefix) &&
                $prefix >= 0 &&
                $prefix <= 32);
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ||
           filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
}

/**
 * Validate CIDR notation
 */
function validate_cidr($cidr) {
    if (preg_match('/^(\d{1,3}\.){3}\d{1,3}\/\d+$/', $cidr)) {
        list($ip, $prefix) = explode('/', $cidr);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            $prefix = (int)$prefix;
            return $prefix >= 0 && $prefix <= 32;
        }
    }
    return false;
}

/**
 * Validate FQDN (Fully Qualified Domain Name)
 */
function validate_fqdn($fqdn) {
    if (substr($fqdn, -1) === '.') {
        return false;
    }
    return (filter_var($fqdn, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false);
}

/**
 * Check if IP is in private range
 */
function is_private_ip($ip) {
    $private_ranges = [
        ['10.0.0.0', '10.255.255.255'],
        ['172.16.0.0', '172.31.255.255'],
        ['192.168.0.0', '192.168.255.255'],
        ['127.0.0.0', '127.255.255.255']
    ];

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $ip_long = ip2long($ip);
        foreach ($private_ranges as $range) {
            $start_long = ip2long($range[0]);
            $end_long = ip2long($range[1]);
            if ($ip_long >= $start_long && $ip_long <= $end_long) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Get IP range from CIDR
 */
function get_ip_range_from_cidr($cidr) {
    list($ip, $mask) = explode('/', $cidr);
    $ip_long = ip2long($ip);
    $mask = (int)$mask;
    $mask_long = -1 << (32 - $mask);
    $network_start = $ip_long & $mask_long;
    $network_end = $network_start | (~$mask_long & 0xFFFFFFFF);
    return [long2ip($network_start), long2ip($network_end)];
}

/**
 * Check if IP is in subnet range
 */
function is_ip_in_subnet_range($ip, $subnet) {
    list($start_ip, $end_ip) = get_ip_range_from_cidr($subnet);
    $ip_long = ip2long($ip);
    $start_long = ip2long($start_ip);
    $end_long = ip2long($end_ip);

    if ($ip_long === false || $start_long === false || $end_long === false) {
        return false;
    }
    return ($ip_long >= $start_long && $ip_long <= $end_long);
}

/**
 * Check if IP is protected (cannot be blacklisted)
 */
function is_protected_ip($ip, $protected_blocks) {
    // Remove CIDR if present for single IP check
    $check_ip = $ip;
    if (strpos($ip, '/') !== false) {
        list($check_ip) = explode('/', $ip);
    }

    foreach ($protected_blocks as $block) {
        if (is_ip_in_subnet_range($check_ip, $block)) {
            return true;
        }
    }
    return false;
}

/**
 * Convert IP to prefix format
 */
function convert_ip_to_prefix($ip) {
    if (strpos($ip, '/') !== false) {
        return $ip;
    }
    return "$ip/32";
}

/**
 * Sanitize input
 */
function sanitize_input($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Load configuration
 */
function load_config() {
    static $config = null;
    if ($config === null) {
        $config_file = __DIR__ . '/../config/config.php';
        if (!file_exists($config_file)) {
            die('Configuration file not found. Please copy config.example.php to config.php');
        }
        $config = require $config_file;
    }
    return $config;
}

/**
 * Get instance by ID
 */
function get_instance($instance_id) {
    $config = load_config();
    foreach ($config['instances'] as $instance) {
        if ($instance['id'] === $instance_id) {
            return $instance;
        }
    }
    return null;
}

/**
 * Get all enabled instances
 */
function get_enabled_instances() {
    $config = load_config();
    return array_filter($config['instances'], function($instance) {
        return $instance['enabled'] === true;
    });
}

/**
 * Ensure directory exists
 */
function ensure_directory($path) {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
    return $path;
}

/**
 * Format file size
 */
function format_filesize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Log message
 */
function log_message($message, $level = 'INFO') {
    $config = load_config();
    if ($config['app']['debug']) {
        $log_file = __DIR__ . '/../data/app.log';
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] [$level] $message" . PHP_EOL;
        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }
}

/**
 * Redirect helper
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Set flash message
 */
function set_flash($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Get and clear flash message
 */
function get_flash() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Parse list file format (IP|comment|date|fqdn|jira)
 */
function parse_list_entry($line) {
    $parts = explode('|', $line);
    return [
        'ip' => $parts[0] ?? '',
        'comment' => $parts[1] ?? '',
        'date' => $parts[2] ?? '',
        'fqdn' => $parts[3] ?? '',
        'jira' => $parts[4] ?? ''
    ];
}

/**
 * Create list entry
 */
function create_list_entry($ip, $comment = '', $fqdn = '', $jira = '') {
    $date = date('Y-m-d H:i:s');
    return "$ip|$comment|$date|$fqdn|$jira";
}

/**
 * Upload logo for instance
 */
function upload_instance_logo($file, $instance_id) {
    $config = load_config();

    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload failed'];
    }

    if ($file['size'] > $config['app']['max_upload_size']) {
        return ['success' => false, 'error' => 'File too large'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $config['app']['allowed_logo_types'])) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }

    // Generate filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $instance_id . '_logo_' . time() . '.' . $ext;
    $destination = __DIR__ . '/../public/images/logos/' . $filename;

    // Ensure directory exists
    ensure_directory(dirname($destination));

    // Move file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'filename' => $filename];
    }

    return ['success' => false, 'error' => 'Failed to save file'];
}

/**
 * Session Mode Management
 */

/**
 * Get current instance mode from session
 */
function get_instance_mode() {
    if (!isset($_SESSION)) {
        session_start();
    }
    return $_SESSION['instance_mode'] ?? 'multi';
}

/**
 * Set instance mode in session
 */
function set_instance_mode($mode) {
    if (!isset($_SESSION)) {
        session_start();
    }
    $_SESSION['instance_mode'] = $mode;
}

/**
 * Get instances based on current mode
 */
function get_current_instances() {
    $config = load_config();
    $mode = get_instance_mode();

    if ($mode === 'single' && !empty($config['instances'])) {
        // Return only first enabled instance in single mode
        foreach ($config['instances'] as $instance) {
            if ($instance['enabled']) {
                return [$instance];
            }
        }
    }

    // Return all enabled instances in multi mode
    return array_filter($config['instances'], function($inst) {
        return $inst['enabled'];
    });
}

/**
 * Render navigation bar with mode switcher
 */
function render_navbar($current_instance = null) {
    $config = load_config();
    $mode = get_instance_mode();
    $instance_id = $current_instance['id'] ?? null;

    $mode_text = $mode === 'single' ? 'Single Instance' : 'Multi Instance';
    $mode_icon = $mode === 'single' ? 'fa-cube' : 'fa-cubes';

    echo '<nav class="navbar">';
    echo '<div class="navbar-content">';

    // Logo and title
    echo '<div class="navbar-brand">';
    echo '<i class="fas fa-shield-alt"></i>';
    echo '<span>' . htmlspecialchars($config['app']['name']) . '</span>';
    echo '</div>';

    // Current instance indicator
    if ($current_instance) {
        echo '<div class="navbar-instance">';
        echo '<i class="fas fa-server"></i>';
        echo '<span>' . htmlspecialchars($current_instance['name']) . '</span>';
        echo '</div>';
    }

    // Mode switcher and actions
    echo '<div class="navbar-actions">';

    // Mode switcher
    echo '<div class="mode-switcher">';
    echo '<button class="mode-btn" onclick="toggleMode()">';
    echo '<i class="fas ' . $mode_icon . '"></i>';
    echo '<span>' . $mode_text . '</span>';
    echo '</button>';
    echo '</div>';

    // Home button
    echo '<a href="index.php" class="navbar-btn">';
    echo '<i class="fas fa-home"></i>';
    echo '<span>Home</span>';
    echo '</a>';

    echo '</div>'; // navbar-actions
    echo '</div>'; // navbar-content
    echo '</nav>';

    // Add mode toggle script
    echo '<script>';
    echo 'function toggleMode() {';
    echo '  const currentMode = "' . $mode . '";';
    echo '  const newMode = currentMode === "single" ? "multi" : "single";';
    echo '  window.location.href = "index.php?switch_mode=" + newMode;';
    echo '}';
    echo '</script>';
}

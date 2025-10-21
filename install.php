<?php
/**
 * Blacklist Manager - Installation Script
 * Run this script to set up the application
 *
 * Usage: php install.php
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CLI only
if (php_sapi_name() !== 'cli') {
    die("This script must be run from command line\n");
}

// Colors for CLI output
class Colors {
    const RESET = "\033[0m";
    const RED = "\033[31m";
    const GREEN = "\033[32m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const MAGENTA = "\033[35m";
    const CYAN = "\033[36m";
    const BOLD = "\033[1m";
}

function print_header($text) {
    echo "\n" . Colors::BOLD . Colors::CYAN . str_repeat('=', 70) . Colors::RESET . "\n";
    echo Colors::BOLD . Colors::CYAN . $text . Colors::RESET . "\n";
    echo Colors::BOLD . Colors::CYAN . str_repeat('=', 70) . Colors::RESET . "\n\n";
}

function print_success($text) {
    echo Colors::GREEN . "✓ " . $text . Colors::RESET . "\n";
}

function print_error($text) {
    echo Colors::RED . "✗ " . $text . Colors::RESET . "\n";
}

function print_warning($text) {
    echo Colors::YELLOW . "⚠ " . $text . Colors::RESET . "\n";
}

function print_info($text) {
    echo Colors::BLUE . "ℹ " . $text . Colors::RESET . "\n";
}

// Start installation
print_header("Blacklist Manager Installation");

echo "Welcome to Blacklist Manager installer!\n";
echo "This script will help you set up the application.\n\n";

// Check PHP version
print_info("Checking system requirements...\n");

$php_version = phpversion();
if (version_compare($php_version, '7.4.0', '<')) {
    print_error("PHP 7.4 or higher required. Current version: $php_version");
    exit(1);
}
print_success("PHP version: $php_version");

// Check required extensions
$required_extensions = ['curl', 'json', 'mbstring', 'session'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
        print_error("Missing PHP extension: $ext");
    } else {
        print_success("PHP extension loaded: $ext");
    }
}

if (!empty($missing_extensions)) {
    print_error("\nPlease install missing extensions:");
    print_error("sudo apt install " . implode(' ', array_map(function($ext) {
        return "php-$ext";
    }, $missing_extensions)));
    exit(1);
}

// Check write permissions
print_info("\nChecking permissions...");

$base_dir = __DIR__;
$write_check = [
    'data',
    'public/images/logos',
    'config'
];

foreach ($write_check as $dir) {
    $path = $base_dir . '/' . $dir;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }

    if (is_writable($path)) {
        print_success("Writable: $dir");
    } else {
        print_warning("Not writable: $dir");
        print_info("  Run: chmod -R 777 $path");
    }
}

// Create data directories
print_info("\nCreating data directories...");

$data_dirs = ['prod', 'staging'];
foreach ($data_dirs as $dir) {
    $path = $base_dir . '/data/' . $dir;
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
        print_success("Created: data/$dir");
    } else {
        print_info("Already exists: data/$dir");
    }

    // Create instance files
    $files = ['blacklist.txt', 'whitelist.txt', 'output_blacklist.txt', 'sources.json'];
    foreach ($files as $file) {
        $file_path = $path . '/' . $file;
        if (!file_exists($file_path)) {
            touch($file_path);
            chmod($file_path, 0666);
            print_success("Created: data/$dir/$file");
        }
    }
}

// Setup configuration
print_info("\nSetting up configuration...");

$config_file = $base_dir . '/config/config.php';
$config_example = $base_dir . '/config/config.example.php';

if (!file_exists($config_file)) {
    if (file_exists($config_example)) {
        // Ask user for configuration
        echo "\nWould you like to customize the configuration now? [y/N]: ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);

        if (trim(strtolower($line)) === 'y') {
            echo "\nApplication Name [Blacklist Manager]: ";
            $handle = fopen("php://stdin", "r");
            $app_name = trim(fgets($handle));
            fclose($handle);
            if (empty($app_name)) $app_name = 'Blacklist Manager';

            echo "Timezone [UTC]: ";
            $handle = fopen("php://stdin", "r");
            $timezone = trim(fgets($handle));
            fclose($handle);
            if (empty($timezone)) $timezone = 'UTC';

            echo "Instance Mode (single/multi) [multi]: ";
            $handle = fopen("php://stdin", "r");
            $instance_mode = trim(fgets($handle));
            fclose($handle);
            if (empty($instance_mode)) $instance_mode = 'multi';

            // Create custom config
            $config_content = file_get_contents($config_example);
            $config_content = str_replace("'Blacklist Manager'", "'$app_name'", $config_content);
            $config_content = str_replace("'UTC'", "'$timezone'", $config_content);
            $config_content = str_replace("'instance_mode' => 'multi'", "'instance_mode' => '$instance_mode'", $config_content);

            file_put_contents($config_file, $config_content);
            print_success("Configuration file created with custom settings");
        } else {
            copy($config_example, $config_file);
            print_success("Configuration file created with default settings");
        }
    } else {
        print_error("Configuration example file not found");
        exit(1);
    }
} else {
    print_info("Configuration file already exists");
}

// Set permissions
print_info("\nSetting permissions...");

chmod($base_dir . '/data', 0777);
chmod($base_dir . '/public/images/logos', 0777);

// Make cron scripts executable
if (is_dir($base_dir . '/cron')) {
    $cron_files = glob($base_dir . '/cron/*.php');
    foreach ($cron_files as $cron_file) {
        chmod($cron_file, 0755);
    }
    print_success("Cron scripts made executable");
}

// Test configuration load
print_info("\nTesting configuration...");

try {
    $config = require $config_file;
    print_success("Configuration loaded successfully");

    print_info("  App Name: " . $config['app']['name']);
    print_info("  Instance Mode: " . $config['instance_mode']);
    print_info("  Instances: " . count($config['instances']));
} catch (Exception $e) {
    print_error("Configuration error: " . $e->getMessage());
    exit(1);
}

// Create .htaccess for Apache
print_info("\nCreating .htaccess file...");

$htaccess_content = <<<'HTACCESS'
Options -Indexes +FollowSymLinks
RewriteEngine On

# Redirect to index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]

# Security headers
<IfModule mod_headers.c>
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-Content-Type-Options "nosniff"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>

# Deny access to config and data directories
<IfModule mod_rewrite.c>
    RewriteRule ^(config|data|app|cron)/ - [F,L]
</IfModule>
HTACCESS;

file_put_contents($base_dir . '/public/.htaccess', $htaccess_content);
print_success("Created .htaccess file");

// Summary
print_header("Installation Complete!");

print_success("Blacklist Manager has been installed successfully!");
echo "\n";
print_info("Next steps:");
echo "  1. Configure your web server to point to: " . Colors::BOLD . "$base_dir/public" . Colors::RESET . "\n";
echo "  2. Access the application in your browser\n";
echo "  3. Set up cron jobs for automatic updates:\n";
echo Colors::YELLOW . "     */5 * * * * /usr/bin/php $base_dir/cron/sync_sources.php >> /var/log/blacklist-sync.log 2>&1" . Colors::RESET . "\n";
echo "  4. Review and customize config/config.php\n";
echo "\n";

print_info("For detailed setup instructions, see INSTALL.md");
echo "\n";

print_header("Thank you for installing Blacklist Manager!");

exit(0);

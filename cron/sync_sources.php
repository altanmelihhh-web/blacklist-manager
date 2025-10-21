<?php
/**
 * Blacklist Manager - Cron Job for Source Synchronization
 * This script automatically updates blacklist sources
 *
 * Add to crontab:
 * */5 * * * * /usr/bin/php /path/to/blacklist-manager/cron/sync_sources.php >> /var/log/blacklist-sync.log 2>&1
 */

// Set up environment
define('IS_CRON', true);
$start_time = microtime(true);

// Load required files
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/SourceManager.php';
require_once __DIR__ . '/../app/InstanceManager.php';

// Load configuration
$config = load_config();

// Set timezone
date_default_timezone_set($config['app']['timezone']);

// Check if cron is enabled
if (!$config['cron']['enabled']) {
    echo date('Y-m-d H:i:s') . " - Cron is disabled in configuration\n";
    exit(0);
}

// Check lock file to prevent concurrent execution
$lock_file = $config['cron']['lock_file'];
if (file_exists($lock_file)) {
    $lock_time = filemtime($lock_file);
    $max_age = $config['cron']['max_execution_time'];

    if (time() - $lock_time < $max_age) {
        echo date('Y-m-d H:i:s') . " - Another sync is already running\n";
        exit(0);
    } else {
        // Stale lock file, remove it
        unlink($lock_file);
    }
}

// Create lock file
file_put_contents($lock_file, getmypid());

// Log start
echo "\n" . str_repeat('=', 70) . "\n";
echo date('Y-m-d H:i:s') . " - Starting source synchronization\n";
echo str_repeat('=', 70) . "\n";

$total_updated = 0;
$total_entries = 0;
$errors = [];

try {
    // Get all enabled instances
    $instanceManager = new InstanceManager();
    $instances = $instanceManager->getEnabledInstances();

    echo "Found " . count($instances) . " enabled instance(s)\n\n";

    foreach ($instances as $instance) {
        echo "Processing instance: {$instance['name']} ({$instance['id']})\n";
        echo str_repeat('-', 70) . "\n";

        $sourceManager = new SourceManager($instance);

        // Get sources that need update
        $sources_to_update = $sourceManager->getSourcesNeedingUpdate();

        if (empty($sources_to_update)) {
            echo "  No sources need updating\n\n";
            continue;
        }

        echo "  Found " . count($sources_to_update) . " source(s) to update:\n";

        foreach ($sources_to_update as $source) {
            echo "    - {$source['name']} ({$source['url']})\n";
            echo "      Last update: " . ($source['last_update'] ?? 'never') . "\n";

            $result = $sourceManager->fetchSource($source['id']);

            if ($result['success']) {
                echo "      Status: ✓ SUCCESS - {$result['entries']} entries fetched\n";
                $total_updated++;
                $total_entries += $result['entries'];
            } else {
                echo "      Status: ✗ FAILED - {$result['error']}\n";
                $errors[] = "{$instance['name']} - {$source['name']}: {$result['error']}";
            }
            echo "\n";
        }
    }

    // Summary
    echo str_repeat('=', 70) . "\n";
    echo "SUMMARY:\n";
    echo "  Sources updated: $total_updated\n";
    echo "  Total entries fetched: $total_entries\n";
    echo "  Errors: " . count($errors) . "\n";

    if (!empty($errors)) {
        echo "\nERROR DETAILS:\n";
        foreach ($errors as $error) {
            echo "  - $error\n";
        }
    }

    $duration = round(microtime(true) - $start_time, 2);
    echo "\nExecution time: {$duration}s\n";
    echo date('Y-m-d H:i:s') . " - Synchronization completed\n";
    echo str_repeat('=', 70) . "\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    $errors[] = $e->getMessage();
} finally {
    // Remove lock file
    if (file_exists($lock_file)) {
        unlink($lock_file);
    }
}

// Exit with appropriate code
exit(empty($errors) ? 0 : 1);

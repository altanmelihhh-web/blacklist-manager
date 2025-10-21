<?php
/**
 * Blacklist Manager - Export
 */

require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/InstanceManager.php';
require_once __DIR__ . '/../app/BlacklistManager.php';

$instance_id = $_GET['instance'] ?? null;
$format = $_GET['format'] ?? 'csv';

if (!$instance_id) {
    die('Instance ID required');
}

$instanceManager = new InstanceManager();
$instance = $instanceManager->getInstance($instance_id);

if (!$instance) {
    die('Instance not found');
}

$blacklistManager = new BlacklistManager($instance);
$entries = $blacklistManager->getBlacklist();

$filename = $instance['slug'] . '_blacklist_' . date('Y-m-d') . '.' . $format;

switch ($format) {
    case 'csv':
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $csv = $blacklistManager->exportToCSV();
        echo $csv;
        break;

    case 'json':
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        echo json_encode($entries, JSON_PRETTY_PRINT);
        break;

    case 'txt':
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        foreach ($entries as $entry) {
            if (!empty($entry['ip']) && $entry['ip'] !== 'N/A') {
                echo $entry['ip'] . "\n";
            }
        }
        break;

    default:
        die('Invalid format');
}

exit;

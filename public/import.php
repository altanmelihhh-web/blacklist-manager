<?php
/**
 * Blacklist Manager - Import from File
 */

session_start();

require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/InstanceManager.php';
require_once __DIR__ . '/../app/BlacklistManager.php';

$config = load_config();

$instance_id = $_GET['instance'] ?? null;
if (!$instance_id) {
    redirect('index.php');
}

$instanceManager = new InstanceManager();
$instance = $instanceManager->getInstance($instance_id);

if (!$instance) {
    set_flash('Instance not found', 'error');
    redirect('index.php');
}

$blacklistManager = new BlacklistManager($instance);

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    $file = $_FILES['import_file'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);

        if ($ext === 'csv') {
            // Parse CSV
            $handle = fopen($file['tmp_name'], 'r');
            $entries = [];
            $header = fgetcsv($handle); // Skip header

            while (($data = fgetcsv($handle)) !== false) {
                if (count($data) >= 1) {
                    $entries[] = [
                        'ip' => $data[0] ?? '',
                        'comment' => $data[1] ?? '',
                        'fqdn' => $data[3] ?? '',
                        'jira' => $data[4] ?? ''
                    ];
                }
            }
            fclose($handle);

            $result = $blacklistManager->bulkImport($entries);
            set_flash("Imported {$result['success']} entries. " . count($result['errors']) . " errors.", 'success');

        } elseif ($ext === 'txt') {
            // Parse plain text (one IP per line)
            $content = file($file['tmp_name'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $entries = [];

            foreach ($content as $line) {
                $line = trim($line);
                if (!empty($line) && $line[0] !== '#') {
                    $entries[] = ['ip' => $line, 'comment' => 'Bulk import', 'fqdn' => '', 'jira' => ''];
                }
            }

            $result = $blacklistManager->bulkImport($entries);
            set_flash("Imported {$result['success']} entries", 'success');

        } else {
            set_flash('Only CSV and TXT files are supported', 'error');
        }
    } else {
        set_flash('Upload error', 'error');
    }

    redirect('dashboard.php?instance=' . $instance_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import - <?php echo htmlspecialchars($instance['name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1 class="header-title"><i class="fas fa-file-upload"></i> Import Blacklist</h1>
            <div class="header-actions">
                <a href="dashboard.php?instance=<?php echo $instance_id; ?>" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <div style="max-width: 700px; margin: 0 auto;">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Upload File</h2>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Select File (CSV or TXT)</label>
                            <input type="file" name="import_file" accept=".csv,.txt" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload and Import
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">File Format</h2>
                </div>
                <div class="card-body">
                    <h4>CSV Format:</h4>
                    <pre style="background: #f5f5f5; padding: 15px; border-radius: 4px;">IP,Comment,Date,FQDN,Jira
192.168.1.1/32,"Attack detected","2024-01-01","",JIRA-123
10.0.0.5/32,"Malicious activity","2024-01-02","bad.example.com",""</pre>

                    <h4 style="margin-top: 20px;">TXT Format:</h4>
                    <pre style="background: #f5f5f5; padding: 15px; border-radius: 4px;"># One IP per line
192.168.1.1/32
10.0.0.5/32
172.16.0.10/32</pre>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

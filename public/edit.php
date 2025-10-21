<?php
/**
 * Blacklist Manager - Edit Entry
 */

session_start();

require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/InstanceManager.php';
require_once __DIR__ . '/../app/BlacklistManager.php';

$config = load_config();
date_default_timezone_set($config['app']['timezone']);

$instance_id = $_GET['instance'] ?? null;
$ip_to_edit = $_GET['ip'] ?? null;

if (!$instance_id || !$ip_to_edit) {
    redirect('index.php');
}

$instanceManager = new InstanceManager();
$instance = $instanceManager->getInstance($instance_id);

if (!$instance) {
    set_flash('Instance not found', 'error');
    redirect('index.php');
}

$blacklistManager = new BlacklistManager($instance);

// Get entry
$entries = $blacklistManager->getBlacklist();
$entry = null;
foreach ($entries as $e) {
    if ($e['ip'] === $ip_to_edit) {
        $entry = $e;
        break;
    }
}

if (!$entry) {
    set_flash('Entry not found', 'error');
    redirect('dashboard.php?instance=' . $instance_id);
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $new_ip = trim($_POST['ip_address']);
    $comment = trim($_POST['comment'] ?? '');
    $fqdn = trim($_POST['fqdn'] ?? '');
    $jira = trim($_POST['jira'] ?? '');

    $result = $blacklistManager->updateBlacklistEntry($ip_to_edit, $new_ip, $comment, $fqdn, $jira);

    if ($result['success']) {
        set_flash('Entry updated successfully', 'success');
        redirect('dashboard.php?instance=' . $instance_id);
    } else {
        set_flash($result['error'], 'error');
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $blacklistManager->removeFromBlacklist($ip_to_edit);
    set_flash('Entry deleted successfully', 'success');
    redirect('dashboard.php?instance=' . $instance_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Entry - <?php echo htmlspecialchars($instance['name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1 class="header-title">Edit Entry</h1>
            <div class="header-actions">
                <a href="dashboard.php?instance=<?php echo $instance_id; ?>" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php
        $flash = get_flash();
        if ($flash):
        ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button class="close" onclick="this.parentElement.remove()">&times;</button>
            </div>
        <?php endif; ?>

        <div style="max-width: 600px; margin: 0 auto;">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-edit"></i> Edit Blacklist Entry</h2>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="form-group">
                            <label for="ip_address">IP Address *</label>
                            <input type="text" name="ip_address" id="ip_address"
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($entry['ip']); ?>"
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="fqdn">FQDN</label>
                            <input type="text" name="fqdn" id="fqdn"
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($entry['fqdn'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="comment">Comment</label>
                            <textarea name="comment" id="comment" class="form-control" rows="3"><?php echo htmlspecialchars($entry['comment'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="jira">Jira/Ticket</label>
                            <input type="text" name="jira" id="jira"
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($entry['jira'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label>Added Date</label>
                            <input type="text" class="form-control"
                                   value="<?php echo htmlspecialchars($entry['date'] ?? ''); ?>"
                                   readonly>
                        </div>

                        <div style="display: flex; gap: 10px;">
                            <button type="submit" name="update" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Entry
                            </button>
                            <button type="submit" name="delete" class="btn btn-danger"
                                    onclick="return confirm('Are you sure you want to delete this entry?')">
                                <i class="fas fa-trash"></i> Delete Entry
                            </button>
                            <a href="dashboard.php?instance=<?php echo $instance_id; ?>" class="btn" style="background: #95a5a6; color: white;">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php
/**
 * Blacklist Manager - Instance Settings
 */

session_start();

require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/InstanceManager.php';

$config = load_config();
date_default_timezone_set($config['app']['timezone']);

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

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update name
    if (isset($_POST['update_name'])) {
        $new_name = trim($_POST['name']);
        if ($instanceManager->updateInstanceName($instance_id, $new_name)) {
            set_flash('Instance name updated', 'success');
            redirect('settings.php?instance=' . $instance_id);
        }
    }

    // Update description
    if (isset($_POST['update_description'])) {
        $description = trim($_POST['description']);
        if ($instanceManager->updateInstanceDescription($instance_id, $description)) {
            set_flash('Description updated', 'success');
            redirect('settings.php?instance=' . $instance_id);
        }
    }

    // Upload logo
    if (isset($_POST['upload_logo']) && isset($_FILES['logo'])) {
        $result = upload_instance_logo($_FILES['logo'], $instance_id);
        if ($result['success']) {
            $instanceManager->updateInstanceLogo($instance_id, $result['filename']);
            set_flash('Logo uploaded successfully', 'success');
        } else {
            set_flash($result['error'], 'error');
        }
        redirect('settings.php?instance=' . $instance_id);
    }

    // Delete logo
    if (isset($_POST['delete_logo'])) {
        if ($instanceManager->deleteInstanceLogo($instance_id)) {
            set_flash('Logo deleted', 'success');
        }
        redirect('settings.php?instance=' . $instance_id);
    }

    // Add protected IP
    if (isset($_POST['add_protected'])) {
        $ip = trim($_POST['protected_ip']);
        if (validate_ip($ip)) {
            $instanceManager->addProtectedIP($instance_id, $ip);
            set_flash('Protected IP added', 'success');
        } else {
            set_flash('Invalid IP format', 'error');
        }
        redirect('settings.php?instance=' . $instance_id);
    }

    // Remove protected IP
    if (isset($_POST['remove_protected'])) {
        $ip = $_POST['ip'];
        $instanceManager->removeProtectedIP($instance_id, $ip);
        set_flash('Protected IP removed', 'success');
        redirect('settings.php?instance=' . $instance_id);
    }
}

// Reload instance
$instance = $instanceManager->getInstance($instance_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo htmlspecialchars($instance['name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1 class="header-title">
                <i class="fas fa-cog"></i> Instance Settings
            </h1>
            <div class="header-actions">
                <a href="dashboard.php?instance=<?php echo $instance_id; ?>" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Dashboard
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

        <div style="max-width: 900px; margin: 0 auto;">
            <!-- General Settings -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-info-circle"></i> General Settings</h2>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="form-group">
                            <label>Instance ID</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($instance['id']); ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label>Instance Name</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" name="name" class="form-control"
                                       value="<?php echo htmlspecialchars($instance['name']); ?>" required>
                                <button type="submit" name="update_name" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update
                                </button>
                            </div>
                        </div>
                    </form>

                    <form method="post">
                        <div class="form-group">
                            <label>Description</label>
                            <div style="display: flex; gap: 10px;">
                                <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($instance['description']); ?></textarea>
                                <button type="submit" name="update_description" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="form-group">
                        <label>Slug (Auto-generated)</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($instance['slug']); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label>Data Directory</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($instance['data_dir']); ?>" readonly>
                    </div>
                </div>
            </div>

            <!-- Logo Management -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-image"></i> Logo Management</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($instance['logo'])): ?>
                        <div style="margin-bottom: 20px; text-align: center;">
                            <img src="images/logos/<?php echo htmlspecialchars($instance['logo']); ?>"
                                 alt="Current Logo"
                                 style="max-width: 200px; max-height: 100px; border: 1px solid #ddd; padding: 10px; border-radius: 8px;">
                            <form method="post" style="margin-top: 10px;">
                                <button type="submit" name="delete_logo" class="btn btn-danger btn-sm"
                                        onclick="return confirm('Delete logo?')">
                                    <i class="fas fa-trash"></i> Delete Logo
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <p style="text-align: center; color: #7f8c8d; padding: 20px;">
                            No logo uploaded yet
                        </p>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Upload New Logo</label>
                            <input type="file" name="logo" accept="image/*" class="form-control">
                            <small style="color: #7f8c8d;">Supported: PNG, JPG, GIF, SVG (Max 5MB)</small>
                        </div>
                        <button type="submit" name="upload_logo" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload Logo
                        </button>
                    </form>
                </div>
            </div>

            <!-- Protected IPs -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-shield-alt"></i> Protected IP Blocks</h2>
                </div>
                <div class="card-body">
                    <p style="color: #7f8c8d; margin-bottom: 20px;">
                        These IPs/ranges cannot be blacklisted. Add your infrastructure IPs here.
                    </p>

                    <h4 style="margin-bottom: 10px;">Default Protected Blocks:</h4>
                    <ul style="margin-bottom: 20px;">
                        <?php foreach ($instance['protected_blocks'] as $block): ?>
                            <li><code><?php echo htmlspecialchars($block); ?></code></li>
                        <?php endforeach; ?>
                    </ul>

                    <h4 style="margin-bottom: 10px;">Custom Protected IPs:</h4>
                    <?php if (empty($instance['custom_protected'])): ?>
                        <p style="color: #7f8c8d; font-style: italic;">No custom protected IPs</p>
                    <?php else: ?>
                        <table class="data-table" style="margin-bottom: 20px;">
                            <thead>
                                <tr>
                                    <th>IP/CIDR</th>
                                    <th style="width: 100px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($instance['custom_protected'] as $ip): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($ip); ?></code></td>
                                        <td>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="ip" value="<?php echo htmlspecialchars($ip); ?>">
                                                <button type="submit" name="remove_protected" class="btn btn-sm btn-danger"
                                                        onclick="return confirm('Remove this IP?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>

                    <form method="post">
                        <div style="display: flex; gap: 10px;">
                            <input type="text" name="protected_ip" class="form-control"
                                   placeholder="192.168.1.0/24" required>
                            <button type="submit" name="add_protected" class="btn btn-success">
                                <i class="fas fa-plus"></i> Add Protected IP
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- File Information -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-file-alt"></i> File Information</h2>
                </div>
                <div class="card-body">
                    <table class="data-table">
                        <tr>
                            <td><strong>Blacklist File:</strong></td>
                            <td><code><?php echo htmlspecialchars($instance['blacklist_file']); ?></code></td>
                        </tr>
                        <tr>
                            <td><strong>Whitelist File:</strong></td>
                            <td><code><?php echo htmlspecialchars($instance['whitelist_file']); ?></code></td>
                        </tr>
                        <tr>
                            <td><strong>Output File:</strong></td>
                            <td><code><?php echo htmlspecialchars($instance['output_file']); ?></code></td>
                        </tr>
                        <tr>
                            <td><strong>Full Output Path:</strong></td>
                            <td><code><?php echo htmlspecialchars($instance['data_dir'] . '/' . $instance['output_file']); ?></code></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php if ($config['ui']['show_footer']): ?>
        <div class="footer">
            <p><?php echo $config['ui']['footer_text']; ?></p>
        </div>
    <?php endif; ?>
</body>
</html>

<?php
/**
 * Blacklist Manager - Source Management
 */

session_start();

require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/InstanceManager.php';
require_once __DIR__ . '/../app/SourceManager.php';

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

$sourceManager = new SourceManager($instance);

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add source
    if (isset($_POST['add_source'])) {
        $data = [
            'name' => trim($_POST['name']),
            'url' => trim($_POST['url']),
            'type' => $_POST['type'],
            'update_interval' => (int)$_POST['update_interval'],
            'description' => trim($_POST['description']),
            'enabled' => isset($_POST['enabled'])
        ];
        $sourceManager->addSource($data);
        set_flash('Source added successfully', 'success');
        redirect('sources.php?instance=' . $instance_id);
    }

    // Toggle source
    if (isset($_POST['toggle_source'])) {
        $source_id = $_POST['source_id'];
        $enabled = $_POST['enabled'] === '1';
        $sourceManager->toggleSource($source_id, $enabled);
        set_flash('Source ' . ($enabled ? 'enabled' : 'disabled'), 'success');
        redirect('sources.php?instance=' . $instance_id);
    }

    // Update source
    if (isset($_POST['update_source'])) {
        $source_id = $_POST['source_id'];
        $result = $sourceManager->fetchSource($source_id);
        if ($result['success']) {
            set_flash('Source updated: ' . $result['entries'] . ' entries fetched', 'success');
        } else {
            set_flash('Update failed: ' . $result['error'], 'error');
        }
        redirect('sources.php?instance=' . $instance_id);
    }

    // Delete source
    if (isset($_POST['delete_source'])) {
        $source_id = $_POST['source_id'];
        $sourceManager->deleteSource($source_id);
        set_flash('Source deleted', 'success');
        redirect('sources.php?instance=' . $instance_id);
    }

    // Update all
    if (isset($_POST['update_all'])) {
        $results = $sourceManager->updateAllSources();
        $success_count = count(array_filter($results, fn($r) => $r['success']));
        set_flash("Updated $success_count sources", 'success');
        redirect('sources.php?instance=' . $instance_id);
    }
}

$sources = $sourceManager->getAllSources();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Source Management - <?php echo htmlspecialchars($instance['name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1 class="header-title">
                <i class="fas fa-cloud-download-alt"></i> Automatic Sources
            </h1>
            <div class="header-actions">
                <form method="post" style="display:inline;">
                    <button type="submit" name="update_all" class="btn btn-success">
                        <i class="fas fa-sync"></i> Update All Sources
                    </button>
                </form>
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

        <div class="container-flex">
            <div class="main-content">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Configured Sources</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($sources)): ?>
                            <p class="text-center" style="padding: 40px; color: #7f8c8d;">
                                No sources configured yet. Add your first source using the form on the right.
                            </p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>URL</th>
                                            <th>Type</th>
                                            <th>Interval</th>
                                            <th>Entries</th>
                                            <th>Last Update</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sources as $source): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($source['name']); ?></strong></td>
                                                <td style="font-size: 11px; max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                                    <a href="<?php echo htmlspecialchars($source['url']); ?>" target="_blank">
                                                        <?php echo htmlspecialchars($source['url']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo htmlspecialchars($source['type']); ?></td>
                                                <td><?php echo round($source['update_interval'] / 3600, 1); ?>h</td>
                                                <td><?php echo number_format($source['entry_count']); ?></td>
                                                <td style="font-size: 12px;">
                                                    <?php echo $source['last_update'] ?? 'Never'; ?>
                                                </td>
                                                <td>
                                                    <?php if ($source['enabled']): ?>
                                                        <span class="badge badge-success">Enabled</span>
                                                    <?php else: ?>
                                                        <span class="badge" style="background: #95a5a6;">Disabled</span>
                                                    <?php endif; ?>
                                                    <?php if ($source['last_status'] === 'success'): ?>
                                                        <span class="badge badge-success">✓</span>
                                                    <?php elseif ($source['last_status'] === 'failed'): ?>
                                                        <span class="badge badge-danger">✗</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <form method="post" style="display: inline;">
                                                        <input type="hidden" name="source_id" value="<?php echo $source['id']; ?>">
                                                        <button type="submit" name="update_source" class="btn btn-sm btn-info" title="Update Now">
                                                            <i class="fas fa-sync"></i>
                                                        </button>
                                                    </form>
                                                    <form method="post" style="display: inline;">
                                                        <input type="hidden" name="source_id" value="<?php echo $source['id']; ?>">
                                                        <input type="hidden" name="enabled" value="<?php echo $source['enabled'] ? '0' : '1'; ?>">
                                                        <button type="submit" name="toggle_source" class="btn btn-sm btn-warning" title="Toggle">
                                                            <i class="fas fa-power-off"></i>
                                                        </button>
                                                    </form>
                                                    <form method="post" style="display: inline;">
                                                        <input type="hidden" name="source_id" value="<?php echo $source['id']; ?>">
                                                        <button type="submit" name="delete_source" class="btn btn-sm btn-danger"
                                                                onclick="return confirm('Delete this source?')" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="sidebar">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-plus"></i> Add Source</h3>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label>Name *</label>
                                <input type="text" name="name" class="form-control" required
                                       placeholder="e.g., Emerging Threats">
                            </div>

                            <div class="form-group">
                                <label>URL *</label>
                                <input type="url" name="url" class="form-control" required
                                       placeholder="https://example.com/blacklist.txt">
                            </div>

                            <div class="form-group">
                                <label>Type *</label>
                                <select name="type" class="form-control" required>
                                    <option value="plain">Plain Text</option>
                                    <option value="ipset">IPSet Format</option>
                                    <option value="netset">Netset Format</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Update Interval (seconds)</label>
                                <select name="update_interval" class="form-control">
                                    <option value="3600">1 Hour</option>
                                    <option value="21600">6 Hours</option>
                                    <option value="43200">12 Hours</option>
                                    <option value="86400" selected>24 Hours</option>
                                    <option value="604800">7 Days</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" class="form-control" rows="2"></textarea>
                            </div>

                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="enabled" checked>
                                    Enable this source
                                </label>
                            </div>

                            <button type="submit" name="add_source" class="btn btn-primary btn-block">
                                <i class="fas fa-plus"></i> Add Source
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle"></i> Popular Sources</h3>
                    </div>
                    <div class="card-body" style="font-size: 12px;">
                        <p><strong>FireHOL Level 1:</strong><br>
                        <code style="font-size: 10px;">https://raw.githubusercontent.com/firehol/blocklist-ipsets/master/firehol_level1.netset</code></p>

                        <p><strong>CI Badguys:</strong><br>
                        <code style="font-size: 10px;">https://raw.githubusercontent.com/firehol/blocklist-ipsets/master/ci_badguys.ipset</code></p>

                        <p><strong>Emerging Threats:</strong><br>
                        <code style="font-size: 10px;">https://rules.emergingthreats.net/fwrules/emerging-Block-IPs.txt</code></p>
                    </div>
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

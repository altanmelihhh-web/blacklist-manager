<?php
/**
 * Blacklist Manager - Whitelist Management
 */

session_start();

require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/InstanceManager.php';
require_once __DIR__ . '/../app/BlacklistManager.php';

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

$blacklistManager = new BlacklistManager($instance);

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_whitelist'])) {
        $ip = trim($_POST['ip_address'] ?? '');
        $comment = trim($_POST['comment'] ?? '');
        $fqdn = trim($_POST['fqdn'] ?? '');
        $jira = trim($_POST['jira'] ?? '');

        $result = $blacklistManager->addToWhitelist($ip, $comment, $fqdn, $jira);
        if ($result['success']) {
            set_flash('Entry added to whitelist', 'success');
        } else {
            set_flash($result['error'], 'error');
        }
        redirect('whitelist.php?instance=' . $instance_id);
    }

    if (isset($_POST['delete_selected']) && isset($_POST['selected_ips'])) {
        $deleted = 0;
        foreach ($_POST['selected_ips'] as $ip) {
            $blacklistManager->removeFromWhitelist($ip);
            $deleted++;
        }
        set_flash("$deleted entries deleted", 'success');
        redirect('whitelist.php?instance=' . $instance_id);
    }
}

// Get entries
$entries = $blacklistManager->getWhitelist();
$search = $_GET['search'] ?? '';
if ($search) {
    $entries = array_filter($entries, function($entry) use ($search) {
        return stripos($entry['ip'], $search) !== false ||
               stripos($entry['comment'], $search) !== false ||
               stripos($entry['fqdn'], $search) !== false;
    });
}

// Pagination
$page = (int)($_GET['page'] ?? 1);
$per_page = (int)($_GET['per_page'] ?? 25);
$total = count($entries);
$total_pages = ceil($total / $per_page);
$page = max(1, min($page, $total_pages));
$start = ($page - 1) * $per_page;
$displayed = array_slice($entries, $start, $per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Whitelist - <?php echo htmlspecialchars($instance['name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1 class="header-title">
                <i class="fas fa-shield-alt"></i> Whitelist Management
            </h1>
            <div class="header-actions">
                <a href="dashboard.php?instance=<?php echo $instance_id; ?>" class="btn btn-primary">
                    <i class="fas fa-ban"></i> Blacklist
                </a>
                <a href="index.php" class="btn btn-info">
                    <i class="fas fa-home"></i> Home
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
                        <h2 class="card-title">Protected IPs & Domains</h2>
                    </div>
                    <div class="card-body">
                        <!-- Search -->
                        <div class="search-bar">
                            <form method="get">
                                <input type="hidden" name="instance" value="<?php echo $instance_id; ?>">
                                <table class="search-table">
                                    <tr>
                                        <td style="width:100%">
                                            <input type="text" name="search" class="form-control"
                                                   placeholder="Search..."
                                                   value="<?php echo htmlspecialchars($search); ?>">
                                        </td>
                                        <td>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search"></i> Search
                                            </button>
                                        </td>
                                    </tr>
                                </table>
                            </form>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <form method="post">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" id="select-all" onclick="toggleAll(this)"></th>
                                            <th>IP/CIDR</th>
                                            <th>Comment</th>
                                            <th>FQDN</th>
                                            <th>Jira</th>
                                            <th>Date Added</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($displayed)): ?>
                                            <tr>
                                                <td colspan="7" class="center">No whitelist entries</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($displayed as $entry): ?>
                                                <tr>
                                                    <td class="center">
                                                        <input type="checkbox" name="selected_ips[]"
                                                               value="<?php echo htmlspecialchars($entry['ip']); ?>">
                                                    </td>
                                                    <td><?php echo htmlspecialchars($entry['ip']); ?></td>
                                                    <td><?php echo htmlspecialchars($entry['comment'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($entry['fqdn'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($entry['jira'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($entry['date'] ?? ''); ?></td>
                                                    <td class="center">
                                                        <a href="edit_whitelist.php?instance=<?php echo $instance_id; ?>&ip=<?php echo urlencode($entry['ip']); ?>"
                                                           class="btn btn-sm btn-info">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>

                                <?php if (!empty($displayed)): ?>
                                    <div style="margin-top: 15px;">
                                        <button type="submit" name="delete_selected" class="btn btn-danger"
                                                onclick="return confirm('Delete selected entries?')">
                                            <i class="fas fa-trash"></i> Delete Selected
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?instance=<?php echo $instance_id; ?>&page=<?php echo $i; ?>&per_page=<?php echo $per_page; ?>"
                                       class="page-link <?php echo $i == $page ? 'current' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>

                        <div style="margin-top: 10px; color: #7f8c8d;">
                            Total: <strong><?php echo number_format($total); ?></strong> entries
                        </div>
                    </div>
                </div>
            </div>

            <div class="sidebar">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-plus"></i> Add to Whitelist</h3>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label>IP Address</label>
                                <input type="text" name="ip_address" class="form-control"
                                       placeholder="192.168.1.1/32">
                            </div>

                            <div class="form-group">
                                <label>FQDN</label>
                                <input type="text" name="fqdn" class="form-control"
                                       placeholder="trusted.example.com">
                            </div>

                            <div class="form-group">
                                <label>Comment</label>
                                <textarea name="comment" class="form-control" rows="3"></textarea>
                            </div>

                            <div class="form-group">
                                <label>Jira/Ticket</label>
                                <input type="text" name="jira" class="form-control">
                            </div>

                            <button type="submit" name="add_whitelist" class="btn btn-success btn-block">
                                <i class="fas fa-plus"></i> Add Entry
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle"></i> About Whitelist</h3>
                    </div>
                    <div class="card-body">
                        <p style="font-size: 13px; color: #7f8c8d;">
                            Whitelist contains IPs and domains that should <strong>never</strong> be blacklisted.
                            These entries are protected and will be excluded from all blacklist operations.
                        </p>
                        <hr style="margin: 15px 0;">
                        <p style="font-size: 13px; color: #7f8c8d;">
                            <strong>Protected by default:</strong><br>
                            <?php foreach ($instance['protected_blocks'] as $block): ?>
                                • <?php echo htmlspecialchars($block); ?><br>
                            <?php endforeach; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleAll(source) {
            document.querySelectorAll('input[name="selected_ips[]"]').forEach(cb => {
                cb.checked = source.checked;
            });
        }
    </script>
</body>
</html>

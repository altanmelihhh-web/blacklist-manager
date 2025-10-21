<?php
/**
 * Blacklist Manager - Dashboard
 * Full blacklist management interface
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load dependencies
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/InstanceManager.php';
require_once __DIR__ . '/../app/BlacklistManager.php';
require_once __DIR__ . '/../app/SourceManager.php';

// Load config
$config = load_config();
date_default_timezone_set($config['app']['timezone']);

// Get instance
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
$sourceManager = new SourceManager($instance);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Add to blacklist
    if (isset($_POST['add_blacklist'])) {
        $ip = trim($_POST['ip_address'] ?? '');
        $comment = trim($_POST['comment'] ?? '');
        $fqdn = trim($_POST['fqdn'] ?? '');
        $jira = trim($_POST['jira'] ?? '');

        if (empty($ip) && empty($fqdn)) {
            set_flash('Please enter an IP address or FQDN', 'error');
        } else {
            $result = $blacklistManager->addToBlacklist($ip, $comment, $fqdn, $jira);
            if ($result['success']) {
                set_flash('Entry added successfully', 'success');
            } else {
                set_flash($result['error'], 'error');
            }
        }
        redirect('dashboard.php?instance=' . $instance_id);
    }

    // Bulk delete
    if (isset($_POST['delete_selected']) && isset($_POST['selected_ips'])) {
        $deleted = 0;
        foreach ($_POST['selected_ips'] as $ip) {
            $blacklistManager->removeFromBlacklist($ip);
            $deleted++;
        }
        set_flash("$deleted entries deleted successfully", 'success');
        redirect('dashboard.php?instance=' . $instance_id);
    }

    // Sync to output
    if (isset($_POST['sync_output'])) {
        $count = $blacklistManager->regenerateOutput();
        set_flash("Output file regenerated with $count entries", 'success');
        redirect('dashboard.php?instance=' . $instance_id);
    }
}

// Get search and pagination params
$search = $_GET['search'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$per_page = (int)($_GET['per_page'] ?? $config['ui']['default_items_per_page']);
$list_filter = $_GET['list_filter'] ?? 'manual';

// Get entries
if ($search) {
    $entries = $blacklistManager->searchBlacklist($search);
} else {
    $entries = $blacklistManager->getBlacklist();
}

// Add source entries if showing all
$all_entries = [];
if ($list_filter === 'all') {
    $source_entries = $sourceManager->getAllCachedEntries();
    foreach ($entries as $entry) {
        $all_entries[] = ['data' => $entry, 'source' => 'Manual', 'editable' => true];
    }
    foreach ($source_entries as $source_entry) {
        $all_entries[] = [
            'data' => ['ip' => $source_entry['ip'], 'comment' => '', 'fqdn' => '', 'jira' => '', 'date' => ''],
            'source' => $source_entry['source'],
            'editable' => false
        ];
    }
} else {
    foreach ($entries as $entry) {
        $all_entries[] = ['data' => $entry, 'source' => 'Manual', 'editable' => true];
    }
}

// Pagination
$total_items = count($all_entries);
$total_pages = ceil($total_items / $per_page);
$page = max(1, min($page, $total_pages));
$start = ($page - 1) * $per_page;
$displayed_entries = array_slice($all_entries, $start, $per_page);

// Get stats
$stats = $blacklistManager->getStats();

$page_title = $instance['name'] . ' - Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div>
                <h1 class="header-title">
                    <?php if (!empty($instance['logo'])): ?>
                        <img src="images/logos/<?php echo htmlspecialchars($instance['logo']); ?>" alt="Logo" class="logo">
                    <?php endif; ?>
                    <?php echo htmlspecialchars($instance['name']); ?>
                </h1>
                <p style="margin-top: 5px; opacity: 0.9;"><?php echo htmlspecialchars($instance['description']); ?></p>
            </div>
            <div class="header-actions">
                <a href="whitelist.php?instance=<?php echo $instance_id; ?>" class="btn btn-success">
                    <i class="fas fa-shield-alt"></i> Whitelist
                </a>
                <a href="sources.php?instance=<?php echo $instance_id; ?>" class="btn btn-info">
                    <i class="fas fa-cloud-download-alt"></i> Sources
                </a>
                <a href="settings.php?instance=<?php echo $instance_id; ?>" class="btn btn-warning">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Home
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Flash Messages -->
        <?php
        $flash = get_flash();
        if ($flash):
        ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button class="close" onclick="this.parentElement.remove()">&times;</button>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['blacklist_count']); ?></div>
                <div class="stat-label">Blacklist Entries</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['whitelist_count']); ?></div>
                <div class="stat-label">Whitelist Entries</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['output_count']); ?></div>
                <div class="stat-label">Output Entries</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo count($sourceManager->getEnabledSources()); ?></div>
                <div class="stat-label">Active Sources</div>
            </div>
        </div>

        <div class="container-flex">
            <!-- Main Content -->
            <div class="main-content">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-ban"></i> Blacklist Management
                        </h2>
                        <form method="post" style="margin: 0;">
                            <button type="submit" name="sync_output" class="btn btn-sm btn-info">
                                <i class="fas fa-sync"></i> Regenerate Output
                            </button>
                        </form>
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
                                                   placeholder="Search IP, FQDN, comment..."
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

                        <!-- Action Bar -->
                        <div class="action-bar">
                            <div class="filter-section">
                                <form method="get">
                                    <input type="hidden" name="instance" value="<?php echo $instance_id; ?>">
                                    <label for="list_filter">Show:</label>
                                    <select name="list_filter" id="list_filter" class="form-control"
                                            onchange="this.form.submit()">
                                        <option value="manual" <?php echo $list_filter === 'manual' ? 'selected' : ''; ?>>Manual Only</option>
                                        <option value="all" <?php echo $list_filter === 'all' ? 'selected' : ''; ?>>All Lists</option>
                                    </select>
                                </form>
                            </div>

                            <div class="per-page-section">
                                <form method="get">
                                    <input type="hidden" name="instance" value="<?php echo $instance_id; ?>">
                                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                    <input type="hidden" name="list_filter" value="<?php echo $list_filter; ?>">
                                    <label for="per_page">Per Page:</label>
                                    <select name="per_page" id="per_page" class="form-control"
                                            onchange="this.form.submit()">
                                        <?php foreach ($config['ui']['items_per_page'] as $option): ?>
                                            <option value="<?php echo $option; ?>"
                                                    <?php echo $option == $per_page ? 'selected' : ''; ?>>
                                                <?php echo $option; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <form method="post">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" id="select-all" onclick="toggleAll(this)"></th>
                                            <th>IP Address</th>
                                            <th>Comment</th>
                                            <th>FQDN</th>
                                            <th>Jira</th>
                                            <th>Date</th>
                                            <th>Source</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($displayed_entries)): ?>
                                            <tr>
                                                <td colspan="8" class="center">No entries found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($displayed_entries as $item): ?>
                                                <?php $entry = $item['data']; ?>
                                                <tr>
                                                    <td class="center">
                                                        <?php if ($item['editable']): ?>
                                                            <input type="checkbox" name="selected_ips[]"
                                                                   value="<?php echo htmlspecialchars($entry['ip']); ?>">
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($entry['ip']); ?></td>
                                                    <td><?php echo htmlspecialchars($entry['comment'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($entry['fqdn'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($entry['jira'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($entry['date'] ?? ''); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo $item['editable'] ? 'info' : 'success'; ?>">
                                                            <?php echo htmlspecialchars($item['source']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="center">
                                                        <?php if ($item['editable']): ?>
                                                            <a href="edit.php?instance=<?php echo $instance_id; ?>&ip=<?php echo urlencode($entry['ip']); ?>"
                                                               class="btn btn-sm btn-info">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </a>
                                                        <?php else: ?>
                                                            <span style="color: #7f8c8d;">Read-only</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>

                                <?php if (!empty($displayed_entries)): ?>
                                    <div style="margin-top: 15px;">
                                        <button type="submit" name="delete_selected" class="btn btn-danger"
                                                onclick="return confirm('Are you sure you want to delete selected entries?')">
                                            <i class="fas fa-trash"></i> Delete Selected
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?instance=<?php echo $instance_id; ?>&page=<?php echo $page-1; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&list_filter=<?php echo $list_filter; ?>"
                                       class="page-link">&laquo; Previous</a>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="?instance=<?php echo $instance_id; ?>&page=<?php echo $i; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&list_filter=<?php echo $list_filter; ?>"
                                       class="page-link <?php echo $i == $page ? 'current' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <a href="?instance=<?php echo $instance_id; ?>&page=<?php echo $page+1; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&list_filter=<?php echo $list_filter; ?>"
                                       class="page-link">Next &raquo;</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div style="margin-top: 10px; color: #7f8c8d; font-style: italic;">
                            Total: <strong><?php echo number_format($total_items); ?></strong> entries
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Add Entry -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-plus-circle"></i> Add Entry</h3>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label for="ip_address">IP Address (CIDR supported)</label>
                                <input type="text" name="ip_address" id="ip_address"
                                       class="form-control" placeholder="192.168.1.1/32">
                            </div>

                            <div class="form-group">
                                <label for="fqdn">FQDN (Optional)</label>
                                <input type="text" name="fqdn" id="fqdn"
                                       class="form-control" placeholder="malicious.example.com">
                            </div>

                            <div class="form-group">
                                <label for="comment">Comment</label>
                                <textarea name="comment" id="comment"
                                          class="form-control" rows="3"
                                          placeholder="Reason for blacklisting..."></textarea>
                            </div>

                            <div class="form-group">
                                <label for="jira">Jira/Ticket</label>
                                <input type="text" name="jira" id="jira"
                                       class="form-control" placeholder="TICKET-123">
                            </div>

                            <button type="submit" name="add_blacklist" class="btn btn-primary btn-block">
                                <i class="fas fa-plus"></i> Add to Blacklist
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-bolt"></i> Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <a href="import.php?instance=<?php echo $instance_id; ?>" class="btn btn-success btn-block mb-2">
                            <i class="fas fa-file-upload"></i> Import from Excel
                        </a>
                        <a href="export.php?instance=<?php echo $instance_id; ?>&format=csv" class="btn btn-info btn-block mb-2">
                            <i class="fas fa-file-download"></i> Export CSV
                        </a>
                        <a href="export.php?instance=<?php echo $instance_id; ?>&format=json" class="btn btn-info btn-block">
                            <i class="fas fa-file-code"></i> Export JSON
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php if ($config['ui']['show_footer']): ?>
        <div class="footer">
            <p><?php echo $config['ui']['footer_text']; ?></p>
        </div>
    <?php endif; ?>

    <script>
        function toggleAll(source) {
            const checkboxes = document.querySelectorAll('input[name="selected_ips[]"]');
            checkboxes.forEach(checkbox => checkbox.checked = source.checked);
        }
    </script>
</body>
</html>

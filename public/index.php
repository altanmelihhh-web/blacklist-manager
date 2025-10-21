<?php
/**
 * Blacklist Manager - Main Entry Point
 */

session_start();

// Load dependencies
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/InstanceManager.php';

// Load configuration
$config = load_config();
date_default_timezone_set($config['app']['timezone']);

// Get instance manager
$instanceManager = new InstanceManager();
$instance_mode = $instanceManager->getInstanceMode();
$instances = $instanceManager->getEnabledInstances();

// Handle mode switching
if (isset($_POST['switch_mode'])) {
    $new_mode = $_POST['mode'] === 'single' ? 'single' : 'multi';
    if ($instanceManager->setInstanceMode($new_mode)) {
        set_flash('Instance mode changed to ' . $new_mode, 'success');
        redirect('/public/index.php');
    }
}

// Get selected instance
$selected_instance = null;
if (isset($_GET['instance'])) {
    $selected_instance = $instanceManager->getInstance($_GET['instance']);
} elseif ($instance_mode === 'single' && count($instances) > 0) {
    // Auto-select first instance in single mode
    $selected_instance = reset($instances);
    redirect('/public/index.php?instance=' . $selected_instance['id']);
}

$page_title = $config['app']['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --success: #27ae60;
            --danger: #e74c3c;
            --warning: #f39c12;
            --light: #ecf0f1;
            --dark: #34495e;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .header h1 {
            color: var(--primary);
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .header p {
            color: #7f8c8d;
            font-size: 1.1em;
        }

        .mode-selector {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .mode-selector h3 {
            margin-bottom: 15px;
            color: var(--primary);
        }

        .mode-buttons {
            display: flex;
            gap: 10px;
        }

        .mode-btn {
            flex: 1;
            padding: 15px;
            border: 2px solid var(--secondary);
            background: white;
            color: var(--secondary);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1em;
        }

        .mode-btn:hover {
            background: var(--secondary);
            color: white;
        }

        .mode-btn.active {
            background: var(--secondary);
            color: white;
        }

        .instances-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .instance-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .instance-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .instance-logo {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            background: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 2em;
            color: var(--secondary);
        }

        .instance-logo img {
            max-width: 100%;
            max-height: 100%;
            border-radius: 10px;
        }

        .instance-name {
            font-size: 1.3em;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .instance-desc {
            color: #7f8c8d;
            font-size: 0.9em;
            margin-bottom: 15px;
        }

        .instance-stats {
            display: flex;
            gap: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--light);
        }

        .stat {
            flex: 1;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5em;
            font-weight: 700;
            color: var(--secondary);
        }

        .stat-label {
            font-size: 0.8em;
            color: #95a5a6;
            text-transform: uppercase;
        }

        .welcome-banner {
            background: white;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .welcome-banner i {
            font-size: 4em;
            color: var(--secondary);
            margin-bottom: 20px;
        }

        .welcome-banner h2 {
            color: var(--primary);
            margin-bottom: 15px;
        }

        .welcome-banner p {
            color: #7f8c8d;
            margin-bottom: 25px;
            font-size: 1.1em;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: var(--secondary);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1em;
        }

        .btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .footer {
            text-align: center;
            color: white;
            margin-top: 40px;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-shield-alt"></i> <?php echo $page_title; ?></h1>
            <p>Advanced IP Blacklist & Whitelist Management System</p>
        </div>

        <!-- Flash Messages -->
        <?php
        $flash = get_flash();
        if ($flash):
        ?>
            <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <!-- Mode Selector -->
        <div class="mode-selector">
            <h3><i class="fas fa-sliders-h"></i> Instance Mode</h3>
            <form method="post" id="modeForm">
                <div class="mode-buttons">
                    <button type="submit" name="switch_mode" value="single"
                            class="mode-btn <?php echo $instance_mode === 'single' ? 'active' : ''; ?>">
                        <i class="fas fa-cube"></i><br>
                        Single Instance<br>
                        <small>Manage one environment</small>
                    </button>
                    <button type="submit" name="switch_mode" value="multi"
                            class="mode-btn <?php echo $instance_mode === 'multi' ? 'active' : ''; ?>">
                        <i class="fas fa-cubes"></i><br>
                        Multi Instance<br>
                        <small>Manage multiple environments</small>
                    </button>
                </div>
                <input type="hidden" name="mode" value="">
            </form>
        </div>

        <!-- Instances Grid -->
        <?php if (empty($instances)): ?>
            <div class="welcome-banner">
                <i class="fas fa-rocket"></i>
                <h2>Welcome to Blacklist Manager!</h2>
                <p>No instances configured yet. Create your first instance to get started.</p>
                <a href="settings.php" class="btn">
                    <i class="fas fa-plus"></i> Create Instance
                </a>
            </div>
        <?php else: ?>
            <div class="instances-grid">
                <?php foreach ($instances as $instance): ?>
                    <a href="dashboard.php?instance=<?php echo $instance['id']; ?>" class="instance-card">
                        <div class="instance-logo">
                            <?php if (!empty($instance['logo'])): ?>
                                <img src="images/logos/<?php echo htmlspecialchars($instance['logo']); ?>"
                                     alt="<?php echo htmlspecialchars($instance['name']); ?>">
                            <?php else: ?>
                                <i class="fas fa-server"></i>
                            <?php endif; ?>
                        </div>
                        <div class="instance-name"><?php echo htmlspecialchars($instance['name']); ?></div>
                        <div class="instance-desc"><?php echo htmlspecialchars($instance['description']); ?></div>
                        <div class="instance-stats">
                            <div class="stat">
                                <div class="stat-value">-</div>
                                <div class="stat-label">Blacklist</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value">-</div>
                                <div class="stat-label">Whitelist</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value">-</div>
                                <div class="stat-label">Sources</div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <?php if ($config['ui']['show_footer']): ?>
            <div class="footer">
                <p><?php echo $config['ui']['footer_text']; ?></p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.querySelectorAll('.mode-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector('input[name="mode"]').value = this.value;
                this.closest('form').submit();
            });
        });
    </script>
</body>
</html>

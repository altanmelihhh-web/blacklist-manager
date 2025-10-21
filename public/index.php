<?php
/**
 * Blacklist Manager - Main Entry Point
 * Mode Selection and Instance Overview
 */

session_start();

// Load dependencies
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/InstanceManager.php';

// Load configuration
$config = load_config();
date_default_timezone_set($config['app']['timezone']);

// Handle mode switching
if (isset($_GET['switch_mode'])) {
    $new_mode = $_GET['switch_mode'] === 'single' ? 'single' : 'multi';
    set_instance_mode($new_mode);
    set_flash('Switched to ' . ucfirst($new_mode) . ' Instance Mode', 'success');
    redirect('index.php');
}

// Get current mode and instances
$mode = get_instance_mode();
$instances = get_current_instances();

// Single mode auto-redirect to dashboard
if ($mode === 'single' && count($instances) === 1) {
    $single_instance = reset($instances);
    redirect('dashboard.php?instance=' . $single_instance['id']);
}

// Get instance manager for stats
$instanceManager = new InstanceManager();

$page_title = $config['app']['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
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
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Header */
        .header {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
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

        /* Mode Switcher */
        .mode-switcher-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .mode-switcher-title {
            font-size: 1.5em;
            color: var(--primary);
            margin-bottom: 20px;
            text-align: center;
        }

        .mode-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .mode-card {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 3px solid transparent;
        }

        .mode-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .mode-card.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #ffd700;
        }

        .mode-card i {
            font-size: 3em;
            margin-bottom: 15px;
            display: block;
        }

        .mode-card h3 {
            font-size: 1.5em;
            margin-bottom: 10px;
        }

        .mode-card p {
            font-size: 0.9em;
            opacity: 0.9;
        }

        .mode-card.active p,
        .mode-card.active h3 {
            color: white;
        }

        /* Instance Grid */
        .instances-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .instance-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .instance-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .instance-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--secondary), var(--success));
        }

        .instance-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .instance-logo {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            margin-right: 15px;
            object-fit: cover;
            background: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2em;
            color: var(--primary);
        }

        .instance-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 10px;
        }

        .instance-info h3 {
            color: var(--primary);
            font-size: 1.3em;
            margin-bottom: 5px;
        }

        .instance-info p {
            color: #7f8c8d;
            font-size: 0.9em;
        }

        .instance-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }

        .stat {
            text-align: center;
            padding: 15px;
            background: var(--light);
            border-radius: 8px;
        }

        .stat-value {
            font-size: 1.8em;
            font-weight: bold;
            color: var(--primary);
        }

        .stat-label {
            font-size: 0.9em;
            color: #7f8c8d;
            margin-top: 5px;
        }

        .instance-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }

        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: var(--secondary);
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        /* Flash Messages */
        .flash-messages {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            max-width: 400px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideIn 0.3s ease-out;
        }

        .alert-success {
            background: var(--success);
            color: white;
        }

        .alert-error {
            background: var(--danger);
            color: white;
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.8em;
            }

            .mode-buttons {
                grid-template-columns: 1fr;
            }

            .instances-grid {
                grid-template-columns: 1fr;
            }

            .instance-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Flash Messages -->
    <?php if ($flash = get_flash()): ?>
        <div class="flash-messages">
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-shield-alt"></i> <?php echo htmlspecialchars($config['app']['name']); ?></h1>
            <p>Manage IP blacklists and whitelists across multiple environments</p>
        </div>

        <!-- Mode Switcher -->
        <div class="mode-switcher-container">
            <h2 class="mode-switcher-title">
                <i class="fas fa-sliders-h"></i> Select Operating Mode
            </h2>
            <div class="mode-buttons">
                <a href="?switch_mode=single" class="mode-card <?php echo $mode === 'single' ? 'active' : ''; ?>">
                    <i class="fas fa-cube"></i>
                    <h3>Single Instance</h3>
                    <p>Manage one environment at a time. Perfect for focused work.</p>
                </a>
                <a href="?switch_mode=multi" class="mode-card <?php echo $mode === 'multi' ? 'active' : ''; ?>">
                    <i class="fas fa-cubes"></i>
                    <h3>Multi Instance</h3>
                    <p>Switch between multiple environments. Great for managing different systems.</p>
                </a>
            </div>
        </div>

        <!-- Instances Grid -->
        <?php if (count($instances) > 0): ?>
            <div class="instances-grid">
                <?php foreach ($instances as $instance): ?>
                    <?php
                        $blacklistManager = new \BlacklistManager($instance);
                        $blacklist_count = count($blacklistManager->getBlacklist());
                        $whitelist_count = count($blacklistManager->getWhitelist());
                    ?>
                    <div class="instance-card" onclick="window.location.href='dashboard.php?instance=<?php echo urlencode($instance['id']); ?>'">
                        <div class="instance-header">
                            <div class="instance-logo">
                                <?php if (!empty($instance['logo'])): ?>
                                    <img src="images/logos/<?php echo htmlspecialchars($instance['logo']); ?>" alt="Logo">
                                <?php else: ?>
                                    <i class="fas fa-server"></i>
                                <?php endif; ?>
                            </div>
                            <div class="instance-info">
                                <h3><?php echo htmlspecialchars($instance['name']); ?></h3>
                                <p><?php echo htmlspecialchars($instance['description']); ?></p>
                            </div>
                        </div>

                        <div class="instance-stats">
                            <div class="stat">
                                <div class="stat-value"><?php echo number_format($blacklist_count); ?></div>
                                <div class="stat-label">Blacklisted</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value"><?php echo number_format($whitelist_count); ?></div>
                                <div class="stat-label">Whitelisted</div>
                            </div>
                        </div>

                        <div class="instance-actions">
                            <a href="dashboard.php?instance=<?php echo urlencode($instance['id']); ?>" class="btn btn-primary">
                                <i class="fas fa-tachometer-alt"></i> Manage
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="background: white; padding: 40px; border-radius: 10px; text-align: center;">
                <i class="fas fa-exclamation-circle" style="font-size: 3em; color: var(--warning); margin-bottom: 20px;"></i>
                <h2>No Instances Configured</h2>
                <p>Please configure at least one instance in config/config.php</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-close flash messages after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>

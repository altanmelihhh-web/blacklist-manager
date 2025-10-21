<?php
/**
 * Blacklist Manager - Configuration File
 *
 * Copy this file to config.php and customize for your environment
 */

return [
    // Database Configuration (optional - uses file-based storage by default)
    'database' => [
        'enabled' => false,
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'blacklist_manager',
        'user' => 'blacklist_user',
        'password' => 'your_password_here',
        'charset' => 'utf8mb4'
    ],

    // Application Settings
    'app' => [
        'name' => 'Blacklist Manager',
        'timezone' => 'Europe/Istanbul',
        'debug' => false,
        'max_upload_size' => 5242880, // 5MB in bytes
        'allowed_logo_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml']
    ],

    // Instance Mode: 'single' or 'multi'
    'instance_mode' => 'multi',

    // Instances Configuration
    // You can define multiple instances (environments)
    'instances' => [
        [
            'id' => 'instance1',
            'name' => 'Production Environment',
            'slug' => 'production',
            'logo' => '', // Will be set via UI
            'enabled' => true,
            'description' => 'Main production blacklist',

            // IP blocks to protect (cannot be blacklisted)
            'protected_blocks' => [
                '10.0.0.0/8',
                '172.16.0.0/12',
                '192.168.0.0/16'
            ],

            // Custom protected IPs (your infrastructure)
            'custom_protected' => [],

            // Paths
            'data_dir' => __DIR__ . '/../data/instance1',
            'blacklist_file' => 'blacklist.txt',
            'whitelist_file' => 'whitelist.txt',
            'output_file' => 'output_blacklist.txt'
        ],
        [
            'id' => 'instance2',
            'name' => 'Staging Environment',
            'slug' => 'staging',
            'logo' => '',
            'enabled' => true,
            'description' => 'Staging environment blacklist',

            'protected_blocks' => [
                '10.0.0.0/8',
                '172.16.0.0/12',
                '192.168.0.0/16'
            ],

            'custom_protected' => [],

            'data_dir' => __DIR__ . '/../data/instance2',
            'blacklist_file' => 'blacklist.txt',
            'whitelist_file' => 'whitelist.txt',
            'output_file' => 'output_blacklist.txt'
        ]
    ],

    // Automatic Source Lists
    // These are public threat feeds that can be automatically synced
    'default_sources' => [
        [
            'name' => 'CI Badguys',
            'url' => 'https://raw.githubusercontent.com/firehol/blocklist-ipsets/master/ci_badguys.ipset',
            'enabled' => true,
            'type' => 'ipset',
            'update_interval' => 86400, // 24 hours in seconds
            'description' => 'Known bad IPs from CI Army'
        ],
        [
            'name' => 'FireHOL Level1',
            'url' => 'https://raw.githubusercontent.com/firehol/blocklist-ipsets/master/firehol_level1.netset',
            'enabled' => true,
            'type' => 'netset',
            'update_interval' => 86400,
            'description' => 'FireHOL Level 1 - most reliable threats'
        ],
        [
            'name' => 'ThreatStop',
            'url' => 'https://rules.emergingthreats.net/fwrules/emerging-Block-IPs.txt',
            'enabled' => false,
            'type' => 'plain',
            'update_interval' => 3600, // 1 hour
            'description' => 'Emerging Threats blocklist'
        ]
    ],

    // Cron Configuration
    'cron' => [
        'enabled' => true,
        'log_file' => __DIR__ . '/../data/cron.log',
        'lock_file' => __DIR__ . '/../data/cron.lock',
        'max_execution_time' => 300 // 5 minutes
    ],

    // Session Configuration
    'session' => [
        'name' => 'BLACKLIST_MANAGER_SESSION',
        'lifetime' => 7200, // 2 hours
        'path' => '/',
        'secure' => false, // Set to true if using HTTPS
        'httponly' => true
    ],

    // Security
    'security' => [
        'csrf_protection' => true,
        'allowed_hosts' => [], // Empty = allow all, or specify allowed hostnames
        'rate_limit' => [
            'enabled' => false,
            'max_requests' => 100,
            'time_window' => 60 // seconds
        ]
    ],

    // Export Settings
    'export' => [
        'excel_template' => __DIR__ . '/../data/template.xlsx',
        'formats' => ['txt', 'csv', 'json', 'xlsx']
    ],

    // UI Settings
    'ui' => [
        'items_per_page' => [10, 25, 50, 100],
        'default_items_per_page' => 25,
        'theme' => 'default',
        'show_footer' => true,
        'footer_text' => '© 2024 Blacklist Manager. Open Source Project.'
    ]
];

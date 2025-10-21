# 🛡️ Blacklist Manager

A powerful, web-based IP blacklist and whitelist management system with multi-instance support, automatic threat feed synchronization, and an intuitive user interface.

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-blue)
![Status](https://img.shields.io/badge/status-stable-green)

## ✨ Features

### 🎯 Core Features
- **Multi-Instance Support**: Manage multiple environments (Production, Staging, etc.) from a single interface
- **Automatic Source Synchronization**: Fetch and update blacklists from public threat feeds automatically
- **Manual Management**: Add, edit, and remove IP addresses and domains manually
- **Whitelist Support**: Protect specific IPs from being blacklisted
- **CIDR Support**: Full support for IP ranges in CIDR notation
- **FQDN Support**: Blacklist/whitelist by domain names

### 🎨 User Interface
- **Modern Design**: Clean, responsive interface that works on all devices
- **Logo Customization**: Upload custom logos for each instance
- **Instance Renaming**: Easily rename instances to match your environments
- **Real-time Statistics**: View blacklist/whitelist counts and status

### 🔄 Automation
- **Cron Integration**: Automatic source updates via cron jobs
- **Configurable Intervals**: Set custom update frequencies for each source
- **Status Monitoring**: Track last update time and success/failure status

### 📊 Import/Export
- **Excel Support**: Bulk import/export via Excel files
- **CSV Export**: Export data in CSV format
- **JSON Export**: Export data in JSON format
- **Plain Text**: Simple IP-per-line format for firewall integration

### 🔒 Security
- **Protected IPs**: Prevent accidental blacklisting of your infrastructure
- **Private IP Detection**: Automatically reject private IP ranges
- **CSRF Protection**: Built-in cross-site request forgery protection
- **Input Validation**: Comprehensive validation of all inputs

## 📋 Requirements

- PHP 7.4 or higher
- PHP Extensions:
  - curl
  - json
  - mbstring
  - session
- Web server (Apache, Nginx, etc.)
- Optional: Composer for dependency management

## 🚀 Quick Start

### 1. Clone or Download

```bash
cd /var/www
git clone https://github.com/yourusername/blacklist-manager.git
cd blacklist-manager
```

### 2. Configure

```bash
cp config/config.example.php config/config.php
nano config/config.php
```

### 3. Set Permissions

```bash
chmod -R 755 /var/www/blacklist-manager
chmod -R 777 data/
chmod -R 777 public/images/logos/
```

### 4. Run Installer

```bash
php install.php
```

### 5. Access Web Interface

Open your browser and navigate to:
```
http://your-server/blacklist-manager/public/
```

## 📖 Detailed Installation

See [INSTALL.md](INSTALL.md) for comprehensive installation instructions including:
- Apache/Nginx configuration
- PHP-FPM setup
- SSL/TLS configuration
- Cron job setup
- Troubleshooting

## 🎯 Usage

### Managing Instances

#### Single Instance Mode
Perfect for managing one environment:
1. Click "Single Instance" mode on the homepage
2. Your instance will be automatically selected
3. Start managing your blacklist

#### Multi-Instance Mode
Manage multiple environments simultaneously:
1. Click "Multi Instance" mode on the homepage
2. Each instance card shows statistics and status
3. Click an instance to manage it

### Adding IPs to Blacklist

#### Manual Addition
1. Navigate to your instance dashboard
2. Use the "Add Entry" form
3. Enter IP address (supports CIDR notation)
4. Optionally add comment, FQDN, or Jira ticket number
5. Click "Add to Blacklist"

#### Bulk Import via Excel
1. Download the Excel template
2. Fill in your IP addresses
3. Upload the completed file
4. Review import results

### Configuring Automatic Sources

1. Navigate to "Sources" tab
2. Click "Add New Source"
3. Enter details:
   - **Name**: Friendly name for the source
   - **URL**: Direct URL to the blacklist feed
   - **Type**: Format of the feed (ipset, netset, plain)
   - **Update Interval**: How often to fetch (in seconds)
4. Enable the source
5. Wait for automatic sync or click "Update Now"

### Setting Up Cron Jobs

Add to your crontab:

```bash
# Update sources every 5 minutes
*/5 * * * * /usr/bin/php /var/www/blacklist-manager/cron/sync_sources.php >> /var/log/blacklist-sync.log 2>&1

# Daily cleanup at 3 AM
0 3 * * * /usr/bin/php /var/www/blacklist-manager/cron/cleanup.php >> /var/log/blacklist-cleanup.log 2>&1
```

### Customizing Instance Settings

1. Click the settings icon on your instance
2. **Change Name**: Update instance display name
3. **Upload Logo**: Add a custom logo (PNG, JPG, GIF, SVG)
4. **Update Description**: Modify instance description
5. **Manage Protected IPs**: Add/remove IPs that can't be blacklisted

## 🔧 Configuration

### Main Configuration File

Edit `config/config.php` to customize:

```php
return [
    'app' => [
        'name' => 'Your Company Blacklist Manager',
        'timezone' => 'America/New_York',
        'debug' => false,
    ],

    'instance_mode' => 'multi', // 'single' or 'multi'

    'instances' => [
        [
            'id' => 'prod',
            'name' => 'Production',
            'description' => 'Production firewall blacklist',
            // ... more settings
        ]
    ],

    'default_sources' => [
        [
            'name' => 'Emerging Threats',
            'url' => 'https://rules.emergingthreats.net/fwrules/emerging-Block-IPs.txt',
            'enabled' => true,
            'update_interval' => 3600, // 1 hour
        ]
    ]
];
```

### Instance Structure

Each instance has its own:
- **Data directory**: Stores blacklist/whitelist files
- **Source cache**: Cached threat feed data
- **Protected IPs**: Infrastructure IPs that can't be blacklisted
- **Output file**: Clean list for firewall consumption

## 🌐 Integration Examples

### Firewall Integration

#### iptables

```bash
#!/bin/bash
# Sync blacklist to iptables
BLACKLIST_FILE="/var/www/blacklist-manager/data/prod/output_blacklist.txt"

# Create ipset if not exists
ipset create -exist blacklist hash:net

# Flush existing entries
ipset flush blacklist

# Add entries from file
while IFS= read -r ip; do
    ipset add blacklist "$ip" 2>/dev/null
done < "$BLACKLIST_FILE"

# Block traffic
iptables -I INPUT -m set --match-set blacklist src -j DROP
```

#### nginx

```nginx
# In your nginx.conf or site config
geo $is_blacklisted {
    default 0;
    include /var/www/blacklist-manager/data/prod/output_blacklist.txt;
}

server {
    if ($is_blacklisted) {
        return 403;
    }
}
```

### API Integration

```php
// Example: Check if IP is blacklisted
<?php
require_once 'app/helpers.php';
require_once 'app/BlacklistManager.php';

$instance = get_instance('prod');
$manager = new BlacklistManager($instance);

$ip_to_check = '1.2.3.4';
$entries = $manager->searchBlacklist($ip_to_check);

if (!empty($entries)) {
    echo "IP is blacklisted!";
} else {
    echo "IP is not blacklisted";
}
```

## 📁 Directory Structure

```
blacklist-manager/
├── app/                      # Core application logic
│   ├── helpers.php          # Helper functions
│   ├── InstanceManager.php  # Instance management
│   ├── BlacklistManager.php # Blacklist operations
│   └── SourceManager.php    # Source management
├── config/                   # Configuration files
│   ├── config.php           # Main configuration
│   └── config.example.php   # Example configuration
├── cron/                     # Cron scripts
│   └── sync_sources.php     # Automatic sync script
├── data/                     # Data storage (git-ignored)
│   ├── prod/                # Production instance data
│   ├── staging/             # Staging instance data
│   └── *.log                # Log files
├── public/                   # Web-accessible files
│   ├── index.php            # Main entry point
│   ├── dashboard.php        # Instance dashboard
│   ├── settings.php         # Settings page
│   ├── sources.php          # Source management
│   ├── css/                 # Stylesheets
│   ├── js/                  # JavaScript files
│   └── images/              # Images and logos
└── docs/                     # Documentation
    ├── INSTALL.md           # Installation guide
    └── API.md               # API documentation
```

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- Default threat feeds from [FireHOL IP Lists](https://github.com/firehol/blocklist-ipsets)
- Icons from [Font Awesome](https://fontawesome.com/)
- Inspired by various open-source security projects

## 📧 Support

- **Issues**: [GitHub Issues](https://github.com/yourusername/blacklist-manager/issues)
- **Discussions**: [GitHub Discussions](https://github.com/yourusername/blacklist-manager/discussions)
- **Email**: support@yourcompany.com

## 🗺️ Roadmap

- [ ] API endpoints for external integration
- [ ] Two-factor authentication
- [ ] Email notifications for changes
- [ ] Geo-IP blocking
- [ ] Advanced analytics dashboard
- [ ] Docker container support
- [ ] Database backend option
- [ ] Multi-language support

## ⚠️ Security Note

This tool is designed to help manage network security policies. Always:
- Review changes before applying to production
- Test blacklist rules in a staging environment first
- Keep protected IP lists up to date
- Monitor logs for false positives
- Have a rollback plan

## 💡 Tips & Best Practices

1. **Start with Single Instance**: If new to the system, start in single instance mode
2. **Regular Backups**: Backup your `data/` directory regularly
3. **Monitor Logs**: Check cron logs to ensure sources are updating
4. **Staging First**: Test changes in staging before applying to production
5. **Comment Everything**: Always add comments when manually adding IPs
6. **Use Jira Integration**: Link entries to tickets for accountability

---

Made with ❤️ for the security community

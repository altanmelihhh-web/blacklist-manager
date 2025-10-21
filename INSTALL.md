# 📦 Installation Guide

Complete installation guide for Blacklist Manager

## Table of Contents

- [System Requirements](#system-requirements)
- [Installation Methods](#installation-methods)
- [Web Server Configuration](#web-server-configuration)
- [Cron Setup](#cron-setup)
- [Post-Installation](#post-installation)
- [Troubleshooting](#troubleshooting)

## System Requirements

### Minimum Requirements

- **OS**: Linux (Ubuntu 20.04+, Debian 10+, CentOS 7+)
- **PHP**: 7.4 or higher
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Disk Space**: 500MB minimum (more for large blacklists)
- **RAM**: 512MB minimum

### Required PHP Extensions

```bash
php-curl
php-json
php-mbstring
php-session
php-xml (for Excel support)
```

### Optional but Recommended

```bash
php-opcache    # For performance
php-zip        # For Excel template downloads
composer       # For dependency management
```

## Installation Methods

### Method 1: Automatic Installation (Recommended)

```bash
# Download the project
cd /var/www
git clone https://github.com/yourusername/blacklist-manager.git
cd blacklist-manager

# Run the installer
php install.php

# Follow the prompts
```

The installer will:
1. Check system requirements
2. Create necessary directories
3. Set proper permissions
4. Initialize configuration
5. Create sample instances
6. Test web server configuration

### Method 2: Manual Installation

#### Step 1: Download

```bash
cd /var/www
git clone https://github.com/yourusername/blacklist-manager.git
cd blacklist-manager
```

Or download and extract the ZIP file:

```bash
cd /var/www
wget https://github.com/yourusername/blacklist-manager/archive/main.zip
unzip main.zip
mv blacklist-manager-main blacklist-manager
cd blacklist-manager
```

#### Step 2: Create Configuration

```bash
cp config/config.example.php config/config.php
nano config/config.php
```

Edit the configuration file to match your environment:

```php
<?php
return [
    'app' => [
        'name' => 'Your Company Blacklist Manager',
        'timezone' => 'Your/Timezone', // e.g., 'America/New_York'
        'debug' => false, // Set to false in production
    ],

    'instance_mode' => 'multi', // or 'single'

    'instances' => [
        [
            'id' => 'prod',
            'name' => 'Production',
            'slug' => 'production',
            'enabled' => true,
            'description' => 'Production environment blacklist',
            'protected_blocks' => [
                '10.0.0.0/8',
                '172.16.0.0/12',
                '192.168.0.0/16',
                '127.0.0.0/8'
            ],
            'custom_protected' => [
                // Add your infrastructure IPs here
                // '203.0.113.0/24',
            ],
            'data_dir' => __DIR__ . '/../data/prod',
            'blacklist_file' => 'blacklist.txt',
            'whitelist_file' => 'whitelist.txt',
            'output_file' => 'output_blacklist.txt'
        ]
    ],

    'cron' => [
        'enabled' => true,
        'log_file' => __DIR__ . '/../data/cron.log',
        'lock_file' => __DIR__ . '/../data/cron.lock',
        'max_execution_time' => 300
    ]
];
```

#### Step 3: Create Data Directories

```bash
mkdir -p data/{prod,staging}
mkdir -p data/prod
mkdir -p data/staging
mkdir -p public/images/logos
touch data/prod/{blacklist.txt,whitelist.txt,output_blacklist.txt}
touch data/staging/{blacklist.txt,whitelist.txt,output_blacklist.txt}
```

#### Step 4: Set Permissions

```bash
# Make web server user (usually www-data or nginx) owner
chown -R www-data:www-data /var/www/blacklist-manager

# Set directory permissions
find /var/www/blacklist-manager -type d -exec chmod 755 {} \;
find /var/www/blacklist-manager -type f -exec chmod 644 {} \;

# Make data directory writable
chmod -R 777 /var/www/blacklist-manager/data
chmod -R 777 /var/www/blacklist-manager/public/images/logos

# Make cron scripts executable
chmod +x /var/www/blacklist-manager/cron/*.php
```

## Web Server Configuration

### Apache Configuration

#### Option 1: Virtual Host (Recommended)

Create `/etc/apache2/sites-available/blacklist-manager.conf`:

```apache
<VirtualHost *:80>
    ServerName blacklist.yourcompany.com
    ServerAdmin admin@yourcompany.com

    DocumentRoot /var/www/blacklist-manager/public

    <Directory /var/www/blacklist-manager/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted

        # Redirect everything to index.php except existing files
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^ index.php [QSA,L]
        </IfModule>
    </Directory>

    # Deny access to sensitive directories
    <Directory /var/www/blacklist-manager/config>
        Require all denied
    </Directory>

    <Directory /var/www/blacklist-manager/data>
        Require all denied
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/blacklist-manager-error.log
    CustomLog ${APACHE_LOG_DIR}/blacklist-manager-access.log combined
</VirtualHost>
```

Enable and restart:

```bash
sudo a2ensite blacklist-manager
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### Option 2: Subdirectory

Create `.htaccess` in `/var/www/blacklist-manager/public/`:

```apache
Options -Indexes +FollowSymLinks
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

Access via: `http://yourserver.com/blacklist-manager/public/`

### Nginx Configuration

Create `/etc/nginx/sites-available/blacklist-manager`:

```nginx
server {
    listen 80;
    server_name blacklist.yourcompany.com;

    root /var/www/blacklist-manager/public;
    index index.php index.html;

    access_log /var/log/nginx/blacklist-manager-access.log;
    error_log /var/log/nginx/blacklist-manager-error.log;

    # Deny access to sensitive directories
    location ~ ^/(config|data|app|cron)/ {
        deny all;
        return 404;
    }

    # PHP handling
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock; # Adjust PHP version
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Rewrite rules
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Deny dotfiles
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }
}
```

Enable and restart:

```bash
sudo ln -s /etc/nginx/sites-available/blacklist-manager /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
sudo systemctl restart php7.4-fpm
```

### SSL/TLS Configuration (Recommended)

#### Using Let's Encrypt (Free)

```bash
# Install certbot
sudo apt install certbot python3-certbot-apache  # For Apache
# OR
sudo apt install certbot python3-certbot-nginx   # For Nginx

# Obtain certificate
sudo certbot --apache -d blacklist.yourcompany.com  # For Apache
# OR
sudo certbot --nginx -d blacklist.yourcompany.com   # For Nginx

# Auto-renewal test
sudo certbot renew --dry-run
```

#### Manual SSL Configuration

For Apache, add to your VirtualHost:

```apache
<VirtualHost *:443>
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    SSLCertificateChainFile /path/to/chain.crt

    # ... rest of configuration
</VirtualHost>
```

For Nginx:

```nginx
server {
    listen 443 ssl http2;

    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;

    # ... rest of configuration
}
```

## Cron Setup

### Setting Up Automatic Updates

Edit crontab:

```bash
crontab -e
```

Add these lines:

```cron
# Blacklist Manager - Update sources every 5 minutes
*/5 * * * * /usr/bin/php /var/www/blacklist-manager/cron/sync_sources.php >> /var/log/blacklist-sync.log 2>&1

# Blacklist Manager - Daily log cleanup at 3 AM
0 3 * * * find /var/www/blacklist-manager/data -name "*.log" -mtime +30 -delete

# Blacklist Manager - Weekly backup at Sunday 2 AM
0 2 * * 0 tar -czf /backup/blacklist-manager-$(date +\%Y\%m\%d).tar.gz /var/www/blacklist-manager/data
```

### Verify Cron is Working

```bash
# Check cron logs
tail -f /var/log/blacklist-sync.log

# Check if cron is running
ps aux | grep cron

# Test manual execution
/usr/bin/php /var/www/blacklist-manager/cron/sync_sources.php
```

## Post-Installation

### 1. Test Installation

Visit your installation URL:
```
http://blacklist.yourcompany.com
```

You should see the Blacklist Manager homepage.

### 2. Create First Instance (if not auto-created)

1. Go to Settings
2. Click "Add New Instance"
3. Fill in details
4. Save

### 3. Add Protected IPs

1. Select your instance
2. Go to Settings
3. Add your infrastructure IPs to "Custom Protected"
4. Save

### 4. Configure Sources

1. Go to "Sources" tab
2. Review default sources
3. Enable desired sources
4. Click "Update All Sources"

### 5. Test Adding IPs

1. Go to Dashboard
2. Try adding a test IP to blacklist
3. Verify it appears in the output file
4. Try adding a protected IP (should fail)

## Troubleshooting

### Permission Issues

```bash
# Reset permissions
cd /var/www/blacklist-manager
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
sudo chmod -R 777 data/
sudo chmod -R 777 public/images/logos/
```

### PHP Errors

Enable error display temporarily:

```php
// In config/config.php
'debug' => true,
```

Check PHP error log:
```bash
sudo tail -f /var/log/php7.4-fpm.log  # Adjust path for your system
```

### Web Server Issues

**Apache:**
```bash
# Check syntax
sudo apache2ctl configtest

# Check logs
sudo tail -f /var/log/apache2/error.log
```

**Nginx:**
```bash
# Check syntax
sudo nginx -t

# Check logs
sudo tail -f /var/log/nginx/error.log
```

### Cron Not Running

```bash
# Check cron service
sudo systemctl status cron

# Check system mail for cron errors
sudo tail -f /var/mail/root

# Test cron manually
sudo -u www-data /usr/bin/php /var/www/blacklist-manager/cron/sync_sources.php
```

### Can't Upload Logo

```bash
# Check upload permissions
ls -la /var/www/blacklist-manager/public/images/logos/

# Should show:
drwxrwxrwx 2 www-data www-data

# Fix if needed:
sudo chmod 777 /var/www/blacklist-manager/public/images/logos/
```

### Sources Not Updating

1. Check if cron is running
2. Verify internet connectivity from server
3. Check source URL is accessible
4. Review cron logs for errors
5. Try manual update from UI

## Upgrade Guide

### Backing Up

```bash
# Backup data
sudo tar -czf blacklist-manager-backup-$(date +%Y%m%d).tar.gz /var/www/blacklist-manager/data

# Backup config
sudo cp /var/www/blacklist-manager/config/config.php /var/www/blacklist-manager/config/config.php.backup
```

### Performing Upgrade

```bash
cd /var/www/blacklist-manager
git pull origin main

# Or if using zip:
# Download new version and extract over existing (keeping data/ and config/)

# Reset permissions
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
sudo chmod -R 777 data/

# Clear cache if exists
rm -rf data/cache/*
```

## Security Hardening

### 1. Restrict Access by IP

**Apache** (.htaccess):
```apache
Order Deny,Allow
Deny from all
Allow from 192.168.1.0/24
Allow from 203.0.113.0/24
```

**Nginx**:
```nginx
allow 192.168.1.0/24;
allow 203.0.113.0/24;
deny all;
```

### 2. Enable HTTP Authentication

```bash
# Create password file
sudo htpasswd -c /etc/apache2/.htpasswd admin

# Add to Apache config
<Directory /var/www/blacklist-manager/public>
    AuthType Basic
    AuthName "Blacklist Manager"
    AuthUserFile /etc/apache2/.htpasswd
    Require valid-user
</Directory>
```

### 3. Disable Directory Listing

Already handled in configuration above.

### 4. Keep Software Updated

```bash
# Update system packages
sudo apt update && sudo apt upgrade

# Update PHP
sudo apt install php7.4 php7.4-cli php7.4-fpm php7.4-curl php7.4-json
```

## Getting Help

- **Documentation**: Check README.md
- **Issues**: https://github.com/yourusername/blacklist-manager/issues
- **Discussions**: https://github.com/yourusername/blacklist-manager/discussions

---

Installation complete! 🎉

Visit your installation and start managing your blacklists.

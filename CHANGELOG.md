# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-01-XX

### Added
- Initial release
- Multi-instance support for managing multiple environments
- Single instance mode for simple deployments
- Automatic source synchronization from public threat feeds
- Manual IP/FQDN blacklist/whitelist management
- CIDR notation support for IP ranges
- Logo upload and customization per instance
- Instance renaming functionality
- Protected IP blocks to prevent accidental blacklisting
- Cron job for automatic source updates
- Excel import/export functionality
- CSV export functionality
- JSON export functionality
- Modern, responsive web interface
- Source management UI
- Dashboard with statistics
- CSRF protection
- Input validation and sanitization
- Comprehensive documentation (README, INSTALL, CONTRIBUTING)
- Automated installation script
- Configuration management
- Session management
- Flash message system

### Security
- Protected IP validation
- Private IP range detection
- Input sanitization
- CSRF token implementation
- Secure file upload handling

## [Unreleased]

### Planned Features
- API endpoints for external integration
- Two-factor authentication
- Email notifications
- Geo-IP blocking
- Advanced analytics dashboard
- Docker container support
- Database backend option
- Multi-language support
- Rate limiting
- API key authentication
- Webhook support
- Audit logging
- User roles and permissions

### Known Issues
- None reported yet

---

## Version History

### Version 1.0.0 - Initial Release
First stable release of Blacklist Manager with core functionality:
- Multi/Single instance modes
- Automatic source updates
- Manual management
- Import/Export capabilities
- Modern UI

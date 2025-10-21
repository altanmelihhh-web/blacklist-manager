# Contributing to Blacklist Manager

First off, thank you for considering contributing to Blacklist Manager! 🎉

## Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check existing issues. When creating a bug report, include as many details as possible:

- **Use a clear and descriptive title**
- **Describe the exact steps to reproduce the problem**
- **Provide specific examples**
- **Describe the behavior you observed and what you expected**
- **Include screenshots if relevant**
- **Note your environment** (OS, PHP version, web server, etc.)

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion:

- **Use a clear and descriptive title**
- **Provide a detailed description of the suggested enhancement**
- **Explain why this enhancement would be useful**
- **List some examples of how it would be used**

### Pull Requests

1. Fork the repository
2. Create a new branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Commit your changes (`git commit -m 'Add some amazing feature'`)
5. Push to the branch (`git push origin feature/amazing-feature`)
6. Open a Pull Request

#### Pull Request Guidelines

- Follow the existing code style
- Update documentation as needed
- Add tests if applicable
- Ensure all tests pass
- Keep pull requests focused on a single feature/fix

## Development Setup

```bash
git clone https://github.com/yourusername/blacklist-manager.git
cd blacklist-manager
cp config/config.example.php config/config.php
php install.php
```

## Coding Standards

- Follow PSR-12 coding standard
- Use meaningful variable and function names
- Comment complex logic
- Keep functions small and focused
- Avoid deep nesting

## Testing

Before submitting a pull request:

1. Test manually in both single and multi-instance modes
2. Test with various IP formats (single IP, CIDR, IPv6)
3. Test upload functionality
4. Test cron scripts
5. Check for PHP errors

## Documentation

- Update README.md if adding new features
- Add code comments for complex logic
- Update INSTALL.md if installation steps change
- Add examples for new features

## Questions?

Feel free to open an issue with your question or contact the maintainers.

Thank you for contributing! 🙌

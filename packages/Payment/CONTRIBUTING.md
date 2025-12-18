# Contributing to Nexus\Payment

Thank you for your interest in contributing to Nexus\Payment! This document provides guidelines for contributions.

## Code of Conduct

Please read and follow our [Code of Conduct](CODE_OF_CONDUCT.md).

## Development Setup

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   ```
3. Run tests:
   ```bash
   composer test
   ```

## Coding Standards

This package follows the [Nexus Coding Guidelines](../../CODING_GUIDELINES.md):

- PHP 8.3+ required
- Use `declare(strict_types=1);` in all files
- All classes must be `final readonly` unless there's a specific reason
- All dependencies must be interfaces, not concrete classes
- Use constructor property promotion
- Use native PHP enums
- Use `match` expressions instead of `switch`

## Pull Request Process

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Make your changes
4. Write/update tests
5. Ensure all tests pass: `composer test`
6. Run static analysis: `composer phpstan`
7. Submit a pull request

## Testing

- All public methods must have unit tests
- Test edge cases and error conditions
- Use mocks for external dependencies
- Aim for >80% code coverage

## Documentation

- Update README.md if adding new features
- Add docblocks to all public methods
- Include usage examples for new interfaces

## Questions?

Open an issue for discussion before starting major changes.

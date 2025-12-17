# Contributing to Nexus\Sanctions

Thank you for considering contributing to the Nexus Sanctions package! This document outlines the process and guidelines for contributing.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [How Can I Contribute?](#how-can-i-contribute)
- [Development Setup](#development-setup)
- [Code Style Guidelines](#code-style-guidelines)
- [Testing Requirements](#testing-requirements)
- [Commit Message Format](#commit-message-format)
- [Pull Request Process](#pull-request-process)

---

## Code of Conduct

This project adheres to the Contributor Covenant [Code of Conduct](CODE_OF_CONDUCT.md). By participating, you are expected to uphold this code.

---

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check existing issues. When creating a bug report, include:

- **Clear descriptive title**
- **Detailed description** of the issue
- **Steps to reproduce** the behavior
- **Expected behavior**
- **Actual behavior**
- **Environment details** (PHP version, OS, etc.)
- **Code samples** demonstrating the issue

### Suggesting Enhancements

Enhancement suggestions are welcome! Please:

- Use a clear and descriptive title
- Provide a detailed description of the proposed enhancement
- Explain why this enhancement would be useful
- Include code examples if applicable

### Pull Requests

We actively welcome your pull requests:

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes following our guidelines
4. Add or update tests as needed
5. Ensure all tests pass
6. Commit your changes (see commit message format below)
7. Push to your branch (`git push origin feature/amazing-feature`)
8. Open a Pull Request

---

## Development Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/nexus/sanctions.git
   cd sanctions
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Run tests:**
   ```bash
   vendor/bin/phpunit
   ```

---

## Code Style Guidelines

This package follows **PSR-12** coding standards with additional requirements:

### PHP Requirements

- **PHP 8.3+** required
- Use `declare(strict_types=1);` at the top of every file
- Use **constructor property promotion**
- All classes must be `final readonly` (services) or use per-property `readonly` (value objects)
- Use **native PHP enums** (not class constants)
- Use **match expressions** instead of switch statements

### Type Safety

- All method parameters must have type hints
- All methods must declare return types
- Use `?Type` for nullable types
- No `mixed` types allowed

### Naming Conventions

- **Classes:** PascalCase
- **Methods:** camelCase
- **Constants:** UPPER_SNAKE_CASE
- **Variables:** camelCase
- **Interfaces:** Suffix with `Interface`
- **Exceptions:** Suffix with `Exception`

### Documentation

- All public methods must have DocBlocks
- Include `@param`, `@return`, `@throws` tags
- Use type hints in DocBlocks
- Provide description and examples for complex logic

**Example:**
```php
<?php

declare(strict_types=1);

namespace Nexus\Sanctions\Services;

use Psr\Log\LoggerInterface;

/**
 * Manages sanctions screening operations
 */
final readonly class SanctionsScreener implements SanctionsScreenerInterface
{
    public function __construct(
        private SanctionsRepositoryInterface $repository,
        private LoggerInterface $logger
    ) {}
    
    /**
     * Screen a party against sanctions lists
     * 
     * @param PartyInterface $party Party to screen
     * @param array<SanctionsList> $lists Lists to check
     * @return ScreeningResult Screening results
     * @throws InvalidPartyException If party data is invalid
     */
    public function screen(PartyInterface $party, array $lists): ScreeningResult
    {
        // Implementation
    }
}
```

---

## Testing Requirements

### Coverage Requirements

- **Minimum 80% code coverage** for all new code
- All public methods must have tests
- Test edge cases and error scenarios

### Test Structure

- Use PHPUnit for testing
- Place tests in `tests/Unit/` directory
- One test class per service/class
- Use descriptive test method names: `test_what_is_being_tested_expected_outcome()`

### Test Guidelines

- Use mocks for external dependencies
- Test both success and failure scenarios
- Use data providers for multiple test cases
- Keep tests focused and independent

**Example:**
```php
public function test_exact_match_returns_exact_strength(): void
{
    $repository = $this->createMock(SanctionsRepositoryInterface::class);
    $repository->expects($this->once())
        ->method('searchByName')
        ->willReturn([/* ... */]);
    
    $screener = new SanctionsScreener($repository, new NullLogger());
    $result = $screener->screen($party, [SanctionsList::OFAC_SDN]);
    
    $this->assertTrue($result->hasMatches());
    $this->assertSame(MatchStrength::EXACT, $result->getStrongestMatch());
}
```

---

## Commit Message Format

We follow **Conventional Commits** specification:

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

- **feat:** New feature
- **fix:** Bug fix
- **docs:** Documentation changes
- **style:** Code style changes (formatting, missing semicolons, etc.)
- **refactor:** Code refactoring
- **test:** Adding or updating tests
- **chore:** Maintenance tasks

### Examples

```
feat(screening): Add batch processing support

Implement batch screening method that processes multiple parties
in a single operation with per-party error isolation.

Closes #123
```

```
fix(fuzzy-matching): Correct Soundex score calculation

Fixed bug where Soundex boost was applied incorrectly when
similarity score was already 1.0.

Fixes #456
```

---

## Pull Request Process

1. **Update documentation:**
   - Update README.md if adding features
   - Add entries to CHANGELOG.md
   - Update inline docblocks

2. **Ensure tests pass:**
   ```bash
   vendor/bin/phpunit
   ```

3. **Check code quality:**
   - Run PHPStan (if available): `vendor/bin/phpstan analyse`
   - Verify PSR-12 compliance

4. **PR Description:**
   - Describe what changes were made and why
   - Reference related issues
   - Include examples if adding features
   - List any breaking changes

5. **Review Process:**
   - Maintainers will review your PR
   - Address feedback and update as needed
   - Once approved, your PR will be merged

---

## Questions?

If you have questions about contributing:

- Check existing issues and documentation
- Open a new issue with your question
- Tag it with `question` label

---

**Thank you for contributing to Nexus\Sanctions!**

*Last Updated: December 16, 2025*

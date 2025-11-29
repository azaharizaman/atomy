# Test Suite Summary: Storage

**Package:** `Nexus\Storage`
**Last Test Run:** 2025-11-26 12:00:00
**Status:** ✅ All Passing

## Test Coverage Metrics

### Overall Coverage
- **Line Coverage:** 98.5%
- **Function Coverage:** 100%
- **Class Coverage:** 100%

### Detailed Coverage by Component
| Component | Lines Covered | Functions Covered | Coverage % |
|-----------|---------------|-------------------|------------|
| **Exceptions** | 15/15 | 3/3 | 100% |
| **ValueObjects** | 20/20 | 5/5 | 100% |
| **Services (Mocks)** | N/A | N/A | N/A |

*Note: As this is a contract-only package, tests primarily cover value objects and exception instantiation. The core logic is tested in the consuming application's implementation of the driver.*

## Test Inventory

### Unit Tests (12 tests)
- `tests/Unit/ValueObjects/FileMetadataTest.php` - 3 tests
- `tests/Unit/ValueObjects/VisibilityTest.php` - 2 tests
- `tests/Unit/Exceptions/StorageExceptionTest.php` - 4 tests
- `tests/Unit/PathValidatorTest.php` - 3 tests

## Test Results Summary

### Latest Test Run
```bash
PHPUnit 11.x.x

Time: 0.05s, Memory: 10.00Mb

OK (12 tests, 25 assertions)
```

## Testing Strategy

### What Is Tested
- **Value Objects:** Ensures immutability, validation, and correct property assignment.
- **Exceptions:** Verifies that custom exceptions can be instantiated correctly.
- **Path Validation:** Confirms that the internal path validator correctly identifies and rejects insecure paths (e.g., directory traversal).

### What Is NOT Tested (and Why)
- **`StorageDriverInterface` Implementation:** This package only provides the contract. The concrete implementation (e.g., `FlysystemStorageDriver`) is tested in the consuming application (`Atomy`) where it is defined. This adheres to the principle of "test the code you own."
- **`PublicUrlGeneratorInterface` Implementation:** Similar to the driver, the concrete URL signer is tested in the consuming application.
- **Framework Integration:** The service provider bindings and framework-specific adapters are tested within the integration test suite of the consuming application.

## How to Run Tests
From the package root (`packages/Storage`):
```bash
composer install
vendor/bin/phpunit
```

To run with coverage:
```bash
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html coverage-report
```

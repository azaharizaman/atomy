# Nexus\Storage

**Framework-agnostic file storage abstraction for the Nexus ERP system.**

## Overview

`Nexus\Storage` provides a single set of contracts to abstract the underlying file system (local disk, S3, Azure Blob Storage, etc.). This package is a pure PHP utility that defines **what** storage operations are needed, not **how** they are implemented.

## Core Philosophy

- ✅ **Pure PHP Interfaces** - No framework dependencies
- ✅ **Stream-First Design** - Efficient handling of large files
- ✅ **Driver Pattern** - Swap storage backends without code changes
- ✅ **Security-Focused** - Built-in path validation and URL signing

## Installation

```bash
composer require nexus/storage
```

## Core Contracts

### StorageDriverInterface

The primary interface for file operations:

```php
use Nexus\Storage\Contracts\StorageDriverInterface;

$driver->put('invoices/2024/invoice-001.pdf', $stream);
$driver->get('invoices/2024/invoice-001.pdf');
$driver->exists('invoices/2024/invoice-001.pdf');
$driver->delete('invoices/2024/invoice-001.pdf');
$driver->createDirectory('invoices/2024');
$driver->listFiles('invoices/2024');
```

### PublicUrlGeneratorInterface

For generating secure, time-limited URLs:

```php
use Nexus\Storage\Contracts\PublicUrlGeneratorInterface;

$url = $urlGenerator->getTemporaryUrl('private/contract.pdf', 3600);
// Returns: https://s3.amazonaws.com/bucket/private/contract.pdf?signature=...
```

## Value Objects

### Visibility

```php
use Nexus\Storage\ValueObjects\Visibility;

$visibility = Visibility::Public;
$visibility = Visibility::Private;
```

### FileMetadata

```php
use Nexus\Storage\ValueObjects\FileMetadata;

$metadata = new FileMetadata(
    path: 'documents/file.pdf',
    size: 1024000,
    mimeType: 'application/pdf',
    lastModified: new \DateTimeImmutable()
);
```

## Exception Handling

All exceptions extend `Nexus\Storage\Exceptions\StorageException`:

```php
use Nexus\Storage\Exceptions\FileNotFoundException;
use Nexus\Storage\Exceptions\InvalidPathException;
use Nexus\Storage\Exceptions\StorageException;

try {
    $driver->get('missing.pdf');
} catch (FileNotFoundException $e) {
    // Handle missing file
} catch (StorageException $e) {
    // Handle general storage errors
}
```

## Implementation in Applications

This package only provides contracts. Applications like `Nexus\Atomy` implement these contracts:

```php
// In Atomy's AppServiceProvider.php
$this->app->singleton(StorageDriverInterface::class, FlysystemDriver::class);
$this->app->singleton(PublicUrlGeneratorInterface::class, S3UrlSigner::class);
```

## Requirements Compliance

This package implements the following requirements from `REQUIREMENTS.csv`:

- **FR-STO-101**: StorageDriverInterface with core methods
- **FR-STO-102**: Stream handling for large files
- **FR-STO-103**: Visibility control
- **FR-STO-104**: PublicUrlGeneratorInterface
- **FR-STO-105**: Directory operations

See `REQUIREMENTS.csv` for complete specification.

## Architecture

```
┌─────────────────────────────────────┐
│    Your Application (Atomy)         │
│  ┌─────────────────────────────┐   │
│  │   FlysystemDriver           │   │
│  │   (implements               │   │
│  │    StorageDriverInterface)  │   │
│  └─────────────────────────────┘   │
└─────────────────────────────────────┘
              ▲
              │ implements
              │
┌─────────────────────────────────────┐
│     Nexus\Storage (this package)    │
│  ┌─────────────────────────────┐   │
│  │   StorageDriverInterface    │   │
│  │   (pure PHP interface)      │   │
│  └─────────────────────────────┘   │
└─────────────────────────────────────┘
```

## 📖 Documentation

### Package Documentation
- [Getting Started Guide](docs/getting-started.md)
- [API Reference](docs/api-reference.md)
- [Integration Guide](docs/integration-guide.md)
- [Examples](docs/examples/)

### Additional Resources
- `IMPLEMENTATION_SUMMARY.md` - Implementation progress
- `REQUIREMENTS.md` - Requirements
- `TEST_SUITE_SUMMARY.md` - Tests
- `VALUATION_MATRIX.md` - Valuation


## License

MIT License - see LICENSE file for details.

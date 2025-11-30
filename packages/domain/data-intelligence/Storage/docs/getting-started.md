# Getting Started with Nexus Storage

## Prerequisites

- PHP 8.3 or higher
- Composer

## Installation

```bash
composer require nexus/storage:"*@dev"
```

## When to Use This Package

This package is designed for:
- ✅ Abstracting file storage logic (local, S3, etc.) from your application.
- ✅ Handling file uploads, downloads, and manipulations in a framework-agnostic way.
- ✅ Generating secure public and temporary (signed) URLs for files.
- ✅ Ensuring consistent file and directory management across different environments.

Do NOT use this package for:
- ❌ Direct database interactions.
- ❌ Application-specific business logic.

## Core Concepts

### StorageDriverInterface
This is the heart of the package. It defines a universal contract for all file system operations like `put`, `get`, `exists`, and `delete`. Your application will provide a concrete implementation of this interface, often using a library like Flysystem to adapt to different storage backends (e.g., local disk, AWS S3, Azure Blob Storage).

### PublicUrlGeneratorInterface
This interface defines the contract for generating URLs to your stored files. It separates the concern of URL generation from basic file operations, allowing for different strategies (e.g., simple public URLs for a local driver vs. signed, temporary URLs for S3).

### Visibility
A simple enum (`public` or `private`) to control the accessibility of your files, which the underlying driver implementation should enforce.

## Basic Configuration

### Step 1: Implement Required Interfaces
Your application must provide concrete implementations for the package's interfaces. Here is a conceptual example using a local filesystem driver.

```php
// Example of implementing the storage driver
namespace App\Storage;

use Nexus\Storage\Contracts\StorageDriverInterface;
use League\Flysystem\FilesystemOperator;

final readonly class MyLocalDriver implements StorageDriverInterface
{
    public function __construct(private FilesystemOperator $filesystem) {}

    // Implement all methods from StorageDriverInterface...
    public function put(string $path, $contents): void
    {
        $this->filesystem->writeStream($path, $contents);
    }
    
    public function get(string $path)
    {
        return $this->filesystem->readStream($path);
    }
    
    // ... and so on for exists, delete, etc.
}
```

### Step 2: Bind Interfaces in Service Provider
In your dependency injection container, bind the interfaces to your concrete implementations.

```php
// Laravel example in a ServiceProvider
use Nexus\Storage\Contracts\StorageDriverInterface;
use App\Storage\MyLocalDriver;

$this->app->singleton(
    StorageDriverInterface::class,
    MyLocalDriver::class
);
```

### Step 3: Use the Package in Your Services
Inject the interface into your services and use it without knowing the underlying implementation details.

```php
use Nexus\Storage\Contracts\StorageDriverInterface;

final readonly class DocumentManager
{
    public function __construct(
        private StorageDriverInterface $storage
    ) {}

    public function uploadDocument(string $path, $stream): void
    {
        $this->storage->put($path, $stream);
    }
}
```

## Your First Integration

Here’s a complete, simple example of uploading and retrieving a file.

```php
<?php
use Nexus\Storage\Contracts\StorageDriverInterface;

// 1. Get the service from your DI container
/** @var StorageDriverInterface $storage */
$storage = $container->get(StorageDriverInterface::class);

$path = 'my-first-document.txt';
$content = 'Hello, Nexus Storage!';

// 2. Create a stream from the content
$stream = fopen('php://memory', 'r+');
fwrite($stream, $content);
rewind($stream);

// 3. Use the driver to store the file
$storage->put($path, $stream);
echo "File uploaded to {$path}\n";

// 4. Retrieve the file
$retrievedStream = $storage->get($path);
$retrievedContent = stream_get_contents($retrievedStream);
fclose($retrievedStream);

echo "Retrieved content: '{$retrievedContent}'\n";

// 5. Clean up
$storage->delete($path);
echo "File deleted.\n";
```

## Next Steps

- Read the [API Reference](api-reference.md) for detailed interface documentation.
- Check the [Integration Guide](integration-guide.md) for framework-specific examples (Laravel, Symfony).
- See the [Examples](examples/) directory for more code samples.

## Troubleshooting

### Common Issues

**Issue 1: `FileNotFoundException` is thrown when trying to read a file.**
- **Cause:** The file does not exist at the specified path, or you have a typo in the path.
- **Solution:** Ensure the file exists using `$storage->exists($path)` before attempting to read it. Double-check the path for correctness.

**Issue 2: `InvalidPathException` is thrown.**
- **Cause:** The file path contains invalid characters or attempts directory traversal (e.g., `../`). The package includes security to prevent this.
- **Solution:** Sanitize all user-provided paths and ensure they conform to valid file path formats.

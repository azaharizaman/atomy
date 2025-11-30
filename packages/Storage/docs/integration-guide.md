# Integration Guide: Nexus Storage

This guide provides detailed examples of how to integrate the `Nexus\Storage` package into popular PHP frameworks like Laravel and Symfony. The core principle is to bind the package's interfaces (`StorageDriverInterface`, `PublicUrlGeneratorInterface`) to concrete implementations in your application's service container.

---

## Laravel Integration

Laravel's native `Storage` facade is built on top of Flysystem, making integration straightforward. We will create a wrapper that adapts Laravel's existing filesystem to our package's contract.

### Step 1: Install Package
```bash
composer require nexus/storage:"*@dev"
```

### Step 2: Create the Storage Driver Implementation
Create a class that implements `StorageDriverInterface` and wraps Laravel's `Filesystem` instance.

**File:** `app/Storage/FlysystemStorageDriver.php`
```php
<?php

namespace App\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Nexus\Storage\Contracts\StorageDriverInterface;
use Nexus\Storage\Exceptions\FileNotFoundException;
use Nexus\Storage\ValueObjects\FileMetadata;
use Nexus\Storage\ValueObjects\Visibility;

final readonly class FlysystemStorageDriver implements StorageDriverInterface
{
    public function __construct(private Filesystem $disk) {}

    public function put(string $path, $contents, array $config = []): void
    {
        $this->disk->put($path, $contents, $config);
    }

    public function get(string $path)
    {
        if (!$this->exists($path)) {
            throw new FileNotFoundException($path);
        }
        return $this->disk->readStream($path);
    }

    public function exists(string $path): bool
    {
        return $this->disk->exists($path);
    }

    public function delete(string $path): void
    {
        if (!$this->exists($path)) {
            throw new FileNotFoundException($path);
        }
        $this->disk->delete($path);
    }

    public function getMetadata(string $path): FileMetadata
    {
        return new FileMetadata(
            path: $path,
            size: $this->disk->size($path),
            mimeType: $this->disk->mimeType($path),
            lastModified: \DateTimeImmutable::createFromFormat('U', $this->disk->lastModified($path))
        );
    }
    
    public function getVisibility(string $path): Visibility
    {
        return $this->disk->getVisibility($path) === 'public' ? Visibility::Public : Visibility::Private;
    }

    public function setVisibility(string $path, Visibility $visibility): void
    {
        $this->disk->setVisibility($path, $visibility->value);
    }

    // ... implement other methods (copy, move, listFiles, etc.)
}
```

### Step 3: Create the URL Generator Implementation
If you are using a cloud driver like S3, you can implement the `PublicUrlGeneratorInterface`.

**File:** `app/Storage/S3UrlGenerator.php`
```php
<?php

namespace App\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Nexus\Storage\Contracts\PublicUrlGeneratorInterface;

final readonly class S3UrlGenerator implements PublicUrlGeneratorInterface
{
    public function __construct(private Filesystem $disk) {}

    public function getPublicUrl(string $path): string
    {
        return $this->disk->url($path);
    }

    public function getTemporaryUrl(string $path, \DateInterval $expiration): string
    {
        $now = new \DateTimeImmutable();
        $expiresAt = $now->add($expiration);
        return $this->disk->temporaryUrl($path, $expiresAt);
    }
    
    // ... implement other methods
}
```

### Step 4: Create a Service Provider
Bind the interfaces to your concrete classes in a new service provider.

**File:** `app/Providers/NexusStorageServiceProvider.php`
```php
<?php

namespace App\Providers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Nexus\Storage\Contracts\PublicUrlGeneratorInterface;
use Nexus\Storage\Contracts\StorageDriverInterface;
use App\Storage\FlysystemStorageDriver;
use App\Storage\S3UrlGenerator;

class NexusStorageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind the storage driver
        $this->app->singleton(StorageDriverInterface::class, function ($app) {
            // Use your default storage disk from filesystems.php
            $disk = Storage::disk(config('filesystems.default'));
            return new FlysystemStorageDriver($disk);
        });

        // Bind the URL generator (example for S3)
        $this->app->singleton(PublicUrlGeneratorInterface::class, function ($app) {
            $disk = Storage::disk('s3'); // Assuming you have an 's3' disk configured
            return new S3UrlGenerator($disk);
        });
    }
}
```

### Step 5: Register the Service Provider
Add your new provider to `config/app.php`:
```php
'providers' => [
    // ...
    App\Providers\NexusStorageServiceProvider::class,
],
```

### Step 6: Use in a Controller or Service
Now you can inject the interfaces anywhere in your application.
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Nexus\Storage\Contracts\StorageDriverInterface;

class FileUploadController extends Controller
{
    public function __construct(
        private readonly StorageDriverInterface $storage
    ) {}

    public function upload(Request $request)
    {
        $path = $request->file('document')->store('documents', 'local');
        
        // If you need to use the Nexus interface with the uploaded file
        $this->storage->put(
            'nexus-uploads/' . basename($path),
            fopen(storage_path('app/' . $path), 'r')
        );

        return response()->json(['status' => 'uploaded']);
    }
}
```

---

## Symfony Integration

Symfony integration follows a similar pattern of creating adapter classes and configuring them in `services.yaml`.

### Step 1: Install Package
```bash
composer require nexus/storage:"*@dev"
```

### Step 2: Create the Storage Driver Implementation
Create an adapter for Symfony's filesystem component or a library like FlysystemBundle.

**File:** `src/Storage/FlysystemStorageDriver.php`
```php
<?php
// (Implementation would be similar to the Laravel example,
// but would wrap a Flysystem\FilesystemOperator instance directly)
```

### Step 3: Configure Services
In `config/services.yaml`, bind the interfaces to your implementations.

```yaml
services:
    # Default configuration for all services
    _defaults:
        autowire: true
        autoconfigure: true

    # Your concrete implementation of the storage driver
    App\Storage\FlysystemStorageDriver:
        arguments:
            # Assuming you have a Flysystem service configured
            $disk: '@flysystem.storage.default'

    # Bind the Nexus interface to your implementation
    Nexus\Storage\Contracts\StorageDriverInterface:
        alias: App\Storage\FlysystemStorageDriver

    # Do the same for the URL generator
    App\Storage\S3UrlGenerator:
        arguments:
            $disk: '@flysystem.storage.s3'
            
    Nexus\Storage\Contracts\PublicUrlGeneratorInterface:
        alias: App\Storage\S3UrlGenerator
```

### Step 4: Use in a Controller or Service
Inject the interface and use it.
```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Nexus\Storage\Contracts\StorageDriverInterface;

class FileUploadController extends AbstractController
{
    public function __construct(
        private readonly StorageDriverInterface $storage
    ) {}

    public function upload(Request $request): JsonResponse
    {
        $uploadedFile = $request->files->get('document');
        
        if ($uploadedFile) {
            $stream = fopen($uploadedFile->getRealPath(), 'r');
            $this->storage->put('uploads/' . $uploadedFile->getClientOriginalName(), $stream);
            fclose($stream);
        }

        return $this->json(['status' => 'uploaded']);
    }
}
```

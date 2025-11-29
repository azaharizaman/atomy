# API Reference: Nexus Storage

This document provides a detailed reference for all public interfaces, value objects, and exceptions in the `Nexus\Storage` package.

---

## Interfaces

### `StorageDriverInterface`

**Location:** `src/Contracts/StorageDriverInterface.php`

**Purpose:** This is the primary contract for all file storage and manipulation operations. It defines a standard API that can be implemented by various storage backends (e.g., local disk, S3, Azure).

**Methods:**

#### `put()`
Writes a new file using a stream.
```php
public function put(string $path, $contents, array $config = []): void;
```
- **Description:** Writes the given contents (resource stream) to the specified path. If the file already exists, it will be overwritten.
- **Parameters:**
  - `$path` (string): The path where the file should be stored.
  - `$contents` (resource): A readable stream resource.
  - `$config` (array): Optional driver-specific configuration (e.g., visibility).
- **Throws:** `StorageException` on failure.

#### `get()`
Retrieves a file as a stream.
```php
public function get(string $path);
```
- **Description:** Returns a readable stream resource for the file at the given path.
- **Returns:** `resource` - A stream resource.
- **Throws:** `FileNotFoundException` if the file does not exist.

#### `exists()`
Checks if a file exists.
```php
public function exists(string $path): bool;
```
- **Returns:** `bool` - `true` if the file exists, `false` otherwise.

#### `delete()`
Deletes a file.
```php
public function delete(string $path): void;
```
- **Throws:** `FileNotFoundException` if the file does not exist.

#### `getMetadata()`
Retrieves metadata for a file.
```php
public function getMetadata(string $path): FileMetadata;
```
- **Returns:** `FileMetadata` - A value object containing size, MIME type, and last modified timestamp.
- **Throws:** `FileNotFoundException` if the file does not exist.

#### `getVisibility()`
Gets the visibility of a file.
```php
public function getVisibility(string $path): Visibility;
```
- **Returns:** `Visibility` - An enum (`Public` or `Private`).
- **Throws:** `FileNotFoundException` if the file does not exist.

#### `setVisibility()`
Sets the visibility of a file.
```php
public function setVisibility(string $path, Visibility $visibility): void;
```
- **Parameters:**
  - `$path` (string): The path to the file.
  - `$visibility` (Visibility): The new visibility (`Visibility::Public` or `Visibility::Private`).
- **Throws:** `FileNotFoundException` if the file does not exist.

#### `createDirectory()`
Creates a directory.
```php
public function createDirectory(string $path, array $config = []): void;
```
- **Description:** Ensures that a directory exists. If it doesn't, it will be created recursively.

#### `deleteDirectory()`
Deletes a directory.
```php
public function deleteDirectory(string $path): void;
```
- **Description:** Deletes a directory and all its contents.

#### `listFiles()`
Lists files in a directory.
```php
/**
 * @return array<FileMetadata>
 */
public function listFiles(string $path = '', bool $recursive = false): array;
```
- **Returns:** `array<FileMetadata>` - An array of `FileMetadata` objects for each file in the directory.

#### `copy()`
Copies a file to a new location.
```php
public function copy(string $source, string $destination, array $config = []): void;
```
- **Throws:** `FileNotFoundException` if the source file does not exist.

#### `move()`
Moves a file to a new location.
```php
public function move(string $source, string $destination, array $config = []): void;
```
- **Throws:** `FileNotFoundException` if the source file does not exist.

---

### `PublicUrlGeneratorInterface`

**Location:** `src/Contracts/PublicUrlGeneratorInterface.php`

**Purpose:** Defines the contract for generating public-facing URLs for stored files. This is separated from `StorageDriverInterface` to allow for different URL generation strategies (e.g., simple paths vs. signed URLs).

**Methods:**

#### `getPublicUrl()`
Gets a permanent public URL for a file.
```php
public function getPublicUrl(string $path): string;
```
- **Description:** Returns a publicly accessible, permanent URL for a file. This should only be used for files with `public` visibility.
- **Throws:** `StorageException` if the driver does not support public URLs.

#### `getTemporaryUrl()`
Gets a temporary, signed URL for a file.
```php
public function getTemporaryUrl(string $path, \DateInterval $expiration): string;
```
- **Description:** Returns a time-limited, signed URL that provides temporary access to a private file.
- **Parameters:**
  - `$path` (string): The path to the private file.
  - `$expiration` (\DateInterval): How long the URL should be valid for.
- **Throws:** `StorageException` if the driver does not support temporary URLs.

#### `getTemporaryUrlUntil()`
Gets a temporary, signed URL valid until a specific time.
```php
public function getTemporaryUrlUntil(string $path, \DateTimeImmutable $expiration): string;
```
- **Description:** Similar to `getTemporaryUrl`, but specifies an exact expiration timestamp.

#### `supportsPublicUrls()`
Checks if the driver supports public URLs.
```php
public function supportsPublicUrls(): bool;
```

#### `supportsTemporaryUrls()`
Checks if the driver supports temporary URLs.
```php
public function supportsTemporaryUrls(): bool;
```

---

## Value Objects

### `FileMetadata`
**Location:** `src/ValueObjects/FileMetadata.php`
- **Purpose:** A read-only data transfer object representing a file's metadata.
- **Properties:**
  - `path` (string): The full path of the file.
  - `size` (int): The file size in bytes.
  - `mimeType` (string|null): The MIME type of the file.
  - `lastModified` (\DateTimeImmutable|null): The last modified timestamp.

### `Visibility`
**Location:** `src/ValueObjects/Visibility.php`
- **Purpose:** A native PHP enum representing file visibility.
- **Cases:**
  - `Public`: The file is publicly accessible.
  - `Private`: The file requires special permissions to access.

---

## Exceptions

All exceptions extend the base `Nexus\Storage\Exceptions\StorageException`.

- **`FileNotFoundException`**: Thrown when an operation is attempted on a non-existent file.
- **`InvalidPathException`**: Thrown when a path is malformed or contains security risks (e.g., `..`).
- **`StorageException`**: A generic exception for any other storage-related error.

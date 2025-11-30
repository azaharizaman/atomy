<?php

declare(strict_types=1);

/**
 * Basic Usage Example: Nexus Storage
 *
 * This example demonstrates the fundamental file operations:
 * 1. Writing a file (put)
 * 2. Checking for existence (exists)
 * 3. Reading a file (get)
 * 4. Deleting a file (delete)
 */

use Nexus\Storage\Contracts\StorageDriverInterface;
use Nexus\Storage\Exceptions\FileNotFoundException;
use Psr\Http\Message\StreamInterface;

// Assume $driver is an implementation of StorageDriverInterface,
// injected into your service via dependency injection.
// For example, in a Laravel application:
// $driver = app(StorageDriverInterface::class);

/**
 * @param StorageDriverInterface $driver
 * @param resource $stream
 */
function run_basic_example(StorageDriverInterface $driver, $stream): void
{
    $path = 'examples/basic-usage.txt';

    echo "--- Basic Usage Example ---\n";

    // 1. Write a file
    echo "1. Writing file to '{$path}'...\n";
    $driver->put($path, $stream);
    echo "   File written successfully.\n\n";

    // 2. Check if the file exists
    echo "2. Checking if '{$path}' exists...\n";
    if ($driver->exists($path)) {
        echo "   File exists.\n\n";
    } else {
        echo "   File does not exist. (Error)\n\n";
    }

    // 3. Read the file's contents
    echo "3. Reading file '{$path}'...\n";
    try {
        $contents = $driver->get($path);
        // In a real application, you would handle the stream.
        // For this example, we'll just get the contents as a string.
        $stringContent = stream_get_contents($contents);
        fclose($contents);

        echo "   File content: \"{$stringContent}\"\n\n";
    } catch (FileNotFoundException $e) {
        echo "   Could not read file: {$e->getMessage()}\n\n";
    }

    // 4. Get file metadata
    echo "4. Getting metadata for '{$path}'...\n";
    $metadata = $driver->getMetadata($path);
    echo "   - Path: " . $metadata->path . "\n";
    echo "   - Size: " . $metadata->size . " bytes\n";
    echo "   - MIME Type: " . $metadata->mimeType . "\n";
    echo "   - Last Modified: " . $metadata->lastModified->format('Y-m-d H:i:s') . "\n\n";


    // 5. Delete the file
    echo "5. Deleting file '{$path}'...\n";
    $driver->delete($path);
    echo "   File deleted.\n\n";

    // 6. Verify deletion
    echo "6. Verifying deletion of '{$path}'...\n";
    if (!$driver->exists($path)) {
        echo "   File no longer exists.\n";
    } else {
        echo "   File still exists. (Error)\n";
    }

    echo "--- End of Example ---\n";
}

// To run this example, you would need a concrete implementation of StorageDriverInterface
// and a stream resource.
//
// Example setup (not part of the library):
//
// use League\Flysystem\Filesystem;
// use League\Flysystem\Local\LocalFilesystemAdapter;
// use App\Storage\FlysystemStorageDriver; // Your implementation
//
// $adapter = new LocalFilesystemAdapter(__DIR__ . '/../storage_root');
// $filesystem = new Filesystem($adapter);
// $driver = new FlysystemStorageDriver($filesystem);
//
// $content = 'Hello, Nexus Storage!';
// $stream = fopen('php://memory', 'r+');
// fwrite($stream, $content);
// rewind($stream);
//
// run_basic_example($driver, $stream);
//
// fclose($stream);

?>

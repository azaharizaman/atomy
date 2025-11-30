<?php

declare(strict_types=1);

/**
 * Advanced Usage Example: Nexus Storage
 *
 * This example demonstrates more advanced features:
 * 1. Directory manipulation (create, list, delete)
 * 2. File manipulation (copy, move)
 * 3. Visibility control (public vs. private)
 * 4. Public and temporary URL generation
 */

use Nexus\Storage\Contracts\PublicUrlGeneratorInterface;
use Nexus\Storage\Contracts\StorageDriverInterface;
use Nexus\Storage\ValueObjects\Visibility;

// Assume $driver is an implementation of StorageDriverInterface
// and $urlGenerator is an implementation of PublicUrlGeneratorInterface.

function run_advanced_example(StorageDriverInterface $driver, PublicUrlGeneratorInterface $urlGenerator): void
{
    $directory = 'examples/advanced';
    $originalPath = "{$directory}/original.txt";
    $copyPath = "{$directory}/copy.txt";
    $movePath = "{$directory}/moved.txt";

    echo "--- Advanced Usage Example ---\n";

    // 1. Directory and File Setup
    echo "1. Setting up directory and file...\n";
    $driver->createDirectory($directory);
    $driver->put($originalPath, 'This is the original file.');
    echo "   Directory '{$directory}' and file '{$originalPath}' created.\n\n";

    // 2. List files in a directory
    echo "2. Listing files in '{$directory}'...\n";
    $files = $driver->listFiles($directory);
    echo "   Files found:\n";
    foreach ($files as $file) {
        echo "   - {$file->path}\n";
    }
    echo "\n";

    // 3. Copy a file
    echo "3. Copying '{$originalPath}' to '{$copyPath}'...\n";
    $driver->copy($originalPath, $copyPath);
    if ($driver->exists($copyPath)) {
        echo "   File copied successfully.\n\n";
    }

    // 4. Move (rename) a file
    echo "4. Moving '{$copyPath}' to '{$movePath}'...\n";
    $driver->move($copyPath, $movePath);
    if ($driver->exists($movePath) && !$driver->exists($copyPath)) {
        echo "   File moved successfully.\n\n";
    }

    // 5. Visibility Control
    echo "5. Setting visibility for '{$originalPath}'...\n";
    $driver->setVisibility($originalPath, Visibility::Public);
    $visibility = $driver->getVisibility($originalPath);
    echo "   File visibility is now: " . $visibility->value . "\n\n";

    // 6. URL Generation
    echo "6. Generating URLs for '{$originalPath}'...\n";
    if ($urlGenerator->supportsPublicUrls()) {
        $publicUrl = $urlGenerator->getPublicUrl($originalPath);
        echo "   - Public URL: {$publicUrl}\n";
    } else {
        echo "   - Public URLs not supported by this driver.\n";
    }

    if ($urlGenerator->supportsTemporaryUrls()) {
        $temporaryUrl = $urlGenerator->getTemporaryUrl($originalPath, new \DateInterval('PT1H')); // 1 hour validity
        echo "   - Temporary (Signed) URL (valid for 1 hour): {$temporaryUrl}\n";
    } else {
        echo "   - Temporary URLs not supported by this driver.\n";
    }
    echo "\n";


    // Cleanup
    echo "Cleaning up created files and directory...\n";
    $driver->deleteDirectory($directory);
    echo "Directory '{$directory}' deleted.\n";

    echo "--- End of Example ---\n";
}

// To run this example, you would need concrete implementations.
//
// Example setup (not part of the library):
//
// use League\Flysystem\Filesystem;
// use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
// use Aws\S3\S3Client;
// use App\Storage\FlysystemStorageDriver;
// use App\Storage\S3PublicUrlGenerator;
//
// $s3Client = new S3Client([...]);
// $adapter = new AwsS3V3Adapter($s3Client, 'your-bucket-name');
// $filesystem = new Filesystem($adapter);
// $driver = new FlysystemStorageDriver($filesystem);
// $urlGenerator = new S3PublicUrlGenerator($s3Client, 'your-bucket-name');
//
// run_advanced_example($driver, $urlGenerator);

?>

<?php

declare(strict_types=1);

namespace Nexus\Laravel\DataExchangeOperations\Adapters;

use Nexus\DataExchangeOperations\Contracts\StoragePortInterface;
use Nexus\Storage\Contracts\StorageDriverInterface;

final readonly class StoragePortAdapter implements StoragePortInterface
{
    public function __construct(private StorageDriverInterface $storage) {}

    public function store(string $destinationPath, string $sourcePath): array
    {
        $stream = fopen($sourcePath, 'rb');
        if ($stream === false) {
            throw new \RuntimeException(sprintf('Unable to read source file: %s', $sourcePath));
        }

        try {
            $this->storage->put($destinationPath, $stream);
        } finally {
            fclose($stream);
        }

        $metadata = $this->storage->getMetadata($destinationPath);

        return [
            'uri' => $destinationPath,
            'size_bytes' => $metadata->size,
        ];
    }

    public function delete(string $path): void
    {
        if ($this->storage->exists($path)) {
            $this->storage->delete($path);
        }
    }

    public function exists(string $path): bool
    {
        if (is_file($path)) {
            return true;
        }

        return $this->storage->exists($path);
    }
}

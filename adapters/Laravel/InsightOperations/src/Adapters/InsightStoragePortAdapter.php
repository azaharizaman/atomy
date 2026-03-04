<?php

declare(strict_types=1);

namespace Nexus\Laravel\InsightOperations\Adapters;

use Nexus\InsightOperations\Contracts\InsightStoragePortInterface;
use Nexus\Storage\Contracts\StorageDriverInterface;

final readonly class InsightStoragePortAdapter implements InsightStoragePortInterface
{
    public function __construct(private StorageDriverInterface $storage) {}

    public function put(string $path, mixed $content): void
    {
        $this->storage->put($path, $content);
    }
}

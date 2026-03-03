<?php

declare(strict_types=1);

namespace Nexus\Document\Contracts;

use Nexus\Document\ValueObjects\Visibility;

interface StorageDriverInterface
{
    /** @param resource $stream */
    public function put(string $path, $stream, Visibility $visibility): void;

    /** @return resource */
    public function get(string $path);

    public function delete(string $path): void;

    public function getTemporaryUrl(string $path, int $ttlSeconds): string;
}

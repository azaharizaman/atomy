<?php

declare(strict_types=1);

namespace Nexus\DataExchangeOperations\Contracts;

interface StoragePortInterface
{
    /**
     * @return array{uri:string,size_bytes:int}
     */
    public function store(string $destinationPath, string $sourcePath): array;

    public function delete(string $path): void;

    public function exists(string $path): bool;
}

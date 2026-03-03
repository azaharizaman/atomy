<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Contracts;

interface InsightStoragePortInterface
{
    /**
     * @param resource|string $content
     */
    public function put(string $path, mixed $content): void;
}

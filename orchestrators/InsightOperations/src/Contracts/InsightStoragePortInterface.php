<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Contracts;

interface InsightStoragePortInterface
{
    /**
     * @param mixed $content
     */
    public function put(string $path, mixed $content): void;
}

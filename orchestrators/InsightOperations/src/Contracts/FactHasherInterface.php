<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Contracts;

interface FactHasherInterface
{
    /**
     * @param array<string, mixed> $facts
     */
    public function hash(array $facts): string;
}

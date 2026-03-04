<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

interface FeatureSetInterface
{
    /** @return array<string, float|int|string|null> */
    public function getFeatures(): array;

    public function getSchemaVersion(): string;

    /** @return array<string, mixed> */
    public function getMetadata(): array;
}

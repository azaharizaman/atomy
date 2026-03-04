<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\Contracts;

interface FeatureSetInterface
{
    /** @return array<string,mixed> */
    public function getFeatures(): array;
    public function getSchemaVersion(): string;
    /** @return array<string,mixed> */
    public function getMetadata(): array;
}

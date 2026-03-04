<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\ValueObjects;

use Nexus\ProcurementML\Contracts\FeatureSetInterface;

final readonly class FeatureSet implements FeatureSetInterface
{
    /**
     * @param array<string,mixed> $features
     * @param array<string,mixed> $metadata
     */
    public function __construct(
        private array $features,
        private string $schemaVersion,
        private array $metadata = [],
    ) {}

    public function getFeatures(): array
    {
        return $this->features;
    }

    public function getSchemaVersion(): string
    {
        return $this->schemaVersion;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}

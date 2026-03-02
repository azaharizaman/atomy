<?php

declare(strict_types=1);

namespace Nexus\ESGRegulatory\Services;

use Nexus\ESGRegulatory\Contracts\RegulatoryRegistryInterface;

/**
 * Service for mapping sustainability metrics to regulatory framework tags.
 */
final class FrameworkMapper implements RegulatoryRegistryInterface
{
    /** @var array<string, array<string, array<string>>> */
    private array $registry = [];

    /**
     * @inheritDoc
     */
    public function getTags(string $metricId, string $framework): array
    {
        return $this->registry[$framework][$metricId] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function registerMappings(string $framework, array $mappings): void
    {
        if (!isset($this->registry[$framework])) {
            $this->registry[$framework] = [];
        }

        $this->registry[$framework] = array_merge($this->registry[$framework], $mappings);
    }

    /**
     * @inheritDoc
     */
    public function getSupportedFrameworks(): array
    {
        return array_keys($this->registry);
    }
}

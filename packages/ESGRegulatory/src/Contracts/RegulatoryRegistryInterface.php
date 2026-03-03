<?php

declare(strict_types=1);

namespace Nexus\ESGRegulatory\Contracts;

/**
 * Interface for the Regulatory Registry.
 * 
 * Maps internal sustainability metrics to international regulatory framework tags.
 */
interface RegulatoryRegistryInterface
{
    /**
     * Map a metric to a set of regulatory framework tags.
     * 
     * @param string $metricId The unique identifier of the metric (e.g., 'carbon_emissions_scope_1')
     * @param string $framework The framework name (e.g., 'CSRD', 'NSRF', 'IFRS_S2')
     * 
     * @return array<string> List of framework-specific tags (e.g., ['ESRS E1-6', 'GRI 305-1'])
     */
    public function getTags(string $metricId, string $framework): array;

    /**
     * Register a new mapping set for a framework.
     * 
     * @param string $framework
     * @param array<string, array<string>> $mappings Map of metricId to tags
     */
    public function registerMappings(string $framework, array $mappings): void;

    /**
     * Get all supported frameworks in the registry.
     * 
     * @return array<string>
     */
    public function getSupportedFrameworks(): array;
}

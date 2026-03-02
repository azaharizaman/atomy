<?php

declare(strict_types=1);

namespace Nexus\ESGRegulatory\ValueObjects;

/**
 * Static registry of standard ESG regulatory framework mappings.
 */
final readonly class StandardMappings
{
    public const CSRD_2025 = [
        'carbon_emissions_scope_1' => ['ESRS E1-6', 'Gross Scope 1 GHG emissions'],
        'carbon_emissions_scope_2' => ['ESRS E1-6', 'Gross Scope 2 GHG emissions'],
        'energy_consumption' => ['ESRS E1-5', 'Energy consumption and mix'],
        'water_consumption' => ['ESRS E3-4', 'Water consumption'],
    ];

    public const NSRF_V1_0 = [
        'carbon_emissions_scope_1' => ['NSRF:Emission', 'Direct Emissions'],
        'carbon_emissions_scope_2' => ['NSRF:Emission', 'Indirect Emissions'],
        'water_consumption' => ['NSRF:Water', 'Water Usage'],
    ];
}

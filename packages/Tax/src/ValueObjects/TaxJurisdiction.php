<?php

declare(strict_types=1);

namespace Nexus\Tax\ValueObjects;

use Nexus\Tax\Enums\TaxLevel;

/**
 * Tax Jurisdiction: Hierarchical tax jurisdiction structure
 * 
 * Represents a geographic jurisdiction that can levy taxes.
 * Supports hierarchical structures (Federal → State → County → City).
 * 
 * Immutable and validated on construction.
 */
final readonly class TaxJurisdiction
{
    /**
     * @param string $code Jurisdiction code (e.g., "US-CA-SF", "EU-DE-BY")
     * @param string $name Human-readable name (e.g., "San Francisco, California, USA")
     * @param TaxLevel $level Jurisdiction level
     * @param string $countryCode ISO 3166-1 alpha-2 country code
     * @param string|null $stateCode State/province code (if applicable)
     * @param string|null $countyCode County code (if applicable)
     * @param string|null $cityCode City code (if applicable)
     * @param TaxJurisdiction|null $parent Parent jurisdiction in hierarchy
     * @param array<string, mixed> $metadata Optional custom metadata
     */
    public function __construct(
        public string $code,
        public string $name,
        public TaxLevel $level,
        public string $countryCode,
        public ?string $stateCode = null,
        public ?string $countyCode = null,
        public ?string $cityCode = null,
        public ?TaxJurisdiction $parent = null,
        public array $metadata = [],
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->code)) {
            throw new \InvalidArgumentException('Jurisdiction code cannot be empty');
        }

        if (empty($this->name)) {
            throw new \InvalidArgumentException('Jurisdiction name cannot be empty');
        }

        if (!preg_match('/^[A-Z]{2}$/', $this->countryCode)) {
            throw new \InvalidArgumentException("Country code must be 2-letter ISO format: {$this->countryCode}");
        }
    }

    /**
     * Get full hierarchical path (e.g., "USA → California → San Francisco")
     */
    public function getHierarchyPath(): string
    {
        $path = [$this->name];
        $current = $this->parent;

        while ($current !== null) {
            array_unshift($path, $current->name);
            $current = $current->parent;
        }

        return implode(' → ', $path);
    }

    /**
     * Check if this jurisdiction is within another jurisdiction
     */
    public function isWithin(TaxJurisdiction $jurisdiction): bool
    {
        $current = $this->parent;

        while ($current !== null) {
            if ($current->code === $jurisdiction->code) {
                return true;
            }
            $current = $current->parent;
        }

        return false;
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'level' => $this->level->value,
            'country_code' => $this->countryCode,
            'state_code' => $this->stateCode,
            'county_code' => $this->countyCode,
            'city_code' => $this->cityCode,
            'parent_code' => $this->parent?->code,
            'hierarchy_path' => $this->getHierarchyPath(),
            'metadata' => $this->metadata,
        ];
    }
}

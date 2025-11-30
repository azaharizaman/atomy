<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\ValueObjects;

/**
 * Represents a compliance standard for financial reporting.
 */
final readonly class ComplianceStandard
{
    /**
     * @param array<string, mixed> $requirements
     */
    public function __construct(
        private string $code,
        private string $name,
        private string $version,
        private array $requirements = []
    ) {}

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRequirements(): array
    {
        return $this->requirements;
    }

    /**
     * Create IFRS standard.
     */
    public static function ifrs(): self
    {
        return new self(
            'IFRS',
            'International Financial Reporting Standards',
            'current',
            [
                'fair_value_required' => true,
                'comprehensive_income' => true,
            ]
        );
    }

    /**
     * Create US GAAP standard.
     */
    public static function usGaap(): self
    {
        return new self(
            'US-GAAP',
            'United States Generally Accepted Accounting Principles',
            'current',
            [
                'segment_reporting' => true,
                'earnings_per_share' => true,
            ]
        );
    }

    /**
     * Create MFRS standard (Malaysian Financial Reporting Standards).
     */
    public static function mfrs(): self
    {
        return new self(
            'MFRS',
            'Malaysian Financial Reporting Standards',
            'current',
            [
                'based_on_ifrs' => true,
                'local_modifications' => true,
            ]
        );
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'version' => $this->version,
            'requirements' => $this->requirements,
        ];
    }
}

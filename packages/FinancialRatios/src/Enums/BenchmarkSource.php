<?php

declare(strict_types=1);

namespace Nexus\FinancialRatios\Enums;

/**
 * Sources for ratio benchmarks
 */
enum BenchmarkSource: string
{
    case INDUSTRY_AVERAGE = 'industry_average';
    case SECTOR_MEDIAN = 'sector_median';
    case PEER_GROUP = 'peer_group';
    case HISTORICAL_COMPANY = 'historical_company';
    case CUSTOM_TARGET = 'custom_target';
    case REGULATORY_MINIMUM = 'regulatory_minimum';
    case BEST_IN_CLASS = 'best_in_class';
    case MARKET_AVERAGE = 'market_average';

    /**
     * Get human-readable label
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::INDUSTRY_AVERAGE => 'Industry Average',
            self::SECTOR_MEDIAN => 'Sector Median',
            self::PEER_GROUP => 'Peer Group',
            self::HISTORICAL_COMPANY => 'Historical (Company)',
            self::CUSTOM_TARGET => 'Custom Target',
            self::REGULATORY_MINIMUM => 'Regulatory Minimum',
            self::BEST_IN_CLASS => 'Best in Class',
            self::MARKET_AVERAGE => 'Market Average',
        };
    }

    /**
     * Get description of the benchmark source
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::INDUSTRY_AVERAGE => 'Average ratio across all companies in the industry',
            self::SECTOR_MEDIAN => 'Median ratio value for the sector',
            self::PEER_GROUP => 'Average from a defined peer group of comparable companies',
            self::HISTORICAL_COMPANY => 'Historical average for the same company',
            self::CUSTOM_TARGET => 'Management-defined target ratio',
            self::REGULATORY_MINIMUM => 'Minimum ratio required by regulators',
            self::BEST_IN_CLASS => 'Top quartile performance in the industry',
            self::MARKET_AVERAGE => 'Average across all publicly traded companies',
        };
    }
}

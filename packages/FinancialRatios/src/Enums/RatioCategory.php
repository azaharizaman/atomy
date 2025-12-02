<?php

declare(strict_types=1);

namespace Nexus\FinancialRatios\Enums;

/**
 * Categories of financial ratios.
 */
enum RatioCategory: string
{
    case LIQUIDITY = 'liquidity';
    case PROFITABILITY = 'profitability';
    case LEVERAGE = 'leverage';
    case EFFICIENCY = 'efficiency';
    case CASH_FLOW = 'cash_flow';
    case MARKET = 'market';

    /**
     * Get a human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::LIQUIDITY => 'Liquidity Ratios',
            self::PROFITABILITY => 'Profitability Ratios',
            self::LEVERAGE => 'Leverage Ratios',
            self::EFFICIENCY => 'Efficiency Ratios',
            self::CASH_FLOW => 'Cash Flow Ratios',
            self::MARKET => 'Market Ratios',
        };
    }

    /**
     * Get a description of what this category measures.
     */
    public function description(): string
    {
        return match ($this) {
            self::LIQUIDITY => 'Ability to meet short-term obligations',
            self::PROFITABILITY => 'Ability to generate profit from operations',
            self::LEVERAGE => 'Level of debt relative to equity and assets',
            self::EFFICIENCY => 'Effectiveness of asset utilization',
            self::CASH_FLOW => 'Quality and sustainability of cash flows',
            self::MARKET => 'Market valuation and investor metrics',
        };
    }
}

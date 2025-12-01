<?php

declare(strict_types=1);

namespace Nexus\FinancialRatios\Enums;

/**
 * Types of financial ratios
 */
enum RatioType: string
{
    // Liquidity Ratios
    case CURRENT_RATIO = 'current_ratio';
    case QUICK_RATIO = 'quick_ratio';
    case CASH_RATIO = 'cash_ratio';
    case WORKING_CAPITAL_RATIO = 'working_capital_ratio';

    // Profitability Ratios
    case GROSS_PROFIT_MARGIN = 'gross_profit_margin';
    case OPERATING_PROFIT_MARGIN = 'operating_profit_margin';
    case NET_PROFIT_MARGIN = 'net_profit_margin';
    case RETURN_ON_ASSETS = 'return_on_assets';
    case RETURN_ON_EQUITY = 'return_on_equity';
    case RETURN_ON_INVESTED_CAPITAL = 'return_on_invested_capital';

    // Leverage Ratios
    case DEBT_TO_EQUITY = 'debt_to_equity';
    case DEBT_TO_ASSETS = 'debt_to_assets';
    case INTEREST_COVERAGE = 'interest_coverage';
    case DEBT_SERVICE_COVERAGE = 'debt_service_coverage';
    case EQUITY_MULTIPLIER = 'equity_multiplier';

    // Efficiency Ratios
    case ASSET_TURNOVER = 'asset_turnover';
    case INVENTORY_TURNOVER = 'inventory_turnover';
    case RECEIVABLES_TURNOVER = 'receivables_turnover';
    case PAYABLES_TURNOVER = 'payables_turnover';
    case FIXED_ASSET_TURNOVER = 'fixed_asset_turnover';
    case DAYS_SALES_OUTSTANDING = 'days_sales_outstanding';
    case DAYS_INVENTORY_OUTSTANDING = 'days_inventory_outstanding';
    case DAYS_PAYABLES_OUTSTANDING = 'days_payables_outstanding';
    case CASH_CONVERSION_CYCLE = 'cash_conversion_cycle';

    // Cash Flow Ratios
    case OPERATING_CASH_FLOW_RATIO = 'operating_cash_flow_ratio';
    case FREE_CASH_FLOW_TO_EQUITY = 'free_cash_flow_to_equity';
    case CASH_FLOW_TO_DEBT = 'cash_flow_to_debt';
    case CASH_FLOW_MARGIN = 'cash_flow_margin';

    // Market Ratios
    case EARNINGS_PER_SHARE = 'earnings_per_share';
    case PRICE_TO_EARNINGS = 'price_to_earnings';
    case PRICE_TO_BOOK = 'price_to_book';
    case DIVIDEND_YIELD = 'dividend_yield';
    case DIVIDEND_PAYOUT = 'dividend_payout';
    case MARKET_TO_BOOK = 'market_to_book';

    // DuPont Analysis
    case DUPONT_ROE = 'dupont_roe';

    /**
     * Get the category this ratio belongs to
     */
    public function getCategory(): RatioCategory
    {
        return match ($this) {
            self::CURRENT_RATIO,
            self::QUICK_RATIO,
            self::CASH_RATIO,
            self::WORKING_CAPITAL_RATIO => RatioCategory::LIQUIDITY,

            self::GROSS_PROFIT_MARGIN,
            self::OPERATING_PROFIT_MARGIN,
            self::NET_PROFIT_MARGIN,
            self::RETURN_ON_ASSETS,
            self::RETURN_ON_EQUITY,
            self::RETURN_ON_INVESTED_CAPITAL => RatioCategory::PROFITABILITY,

            self::DEBT_TO_EQUITY,
            self::DEBT_TO_ASSETS,
            self::INTEREST_COVERAGE,
            self::DEBT_SERVICE_COVERAGE,
            self::EQUITY_MULTIPLIER => RatioCategory::LEVERAGE,

            self::ASSET_TURNOVER,
            self::INVENTORY_TURNOVER,
            self::RECEIVABLES_TURNOVER,
            self::PAYABLES_TURNOVER,
            self::FIXED_ASSET_TURNOVER,
            self::DAYS_SALES_OUTSTANDING,
            self::DAYS_INVENTORY_OUTSTANDING,
            self::DAYS_PAYABLES_OUTSTANDING,
            self::CASH_CONVERSION_CYCLE => RatioCategory::EFFICIENCY,

            self::OPERATING_CASH_FLOW_RATIO,
            self::FREE_CASH_FLOW_TO_EQUITY,
            self::CASH_FLOW_TO_DEBT,
            self::CASH_FLOW_MARGIN => RatioCategory::CASH_FLOW,

            self::EARNINGS_PER_SHARE,
            self::PRICE_TO_EARNINGS,
            self::PRICE_TO_BOOK,
            self::DIVIDEND_YIELD,
            self::DIVIDEND_PAYOUT,
            self::MARKET_TO_BOOK => RatioCategory::MARKET,

            self::DUPONT_ROE => RatioCategory::PROFITABILITY,
        };
    }

    /**
     * Get human-readable label
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::CURRENT_RATIO => 'Current Ratio',
            self::QUICK_RATIO => 'Quick Ratio (Acid Test)',
            self::CASH_RATIO => 'Cash Ratio',
            self::WORKING_CAPITAL_RATIO => 'Working Capital Ratio',
            self::GROSS_PROFIT_MARGIN => 'Gross Profit Margin',
            self::OPERATING_PROFIT_MARGIN => 'Operating Profit Margin',
            self::NET_PROFIT_MARGIN => 'Net Profit Margin',
            self::RETURN_ON_ASSETS => 'Return on Assets (ROA)',
            self::RETURN_ON_EQUITY => 'Return on Equity (ROE)',
            self::RETURN_ON_INVESTED_CAPITAL => 'Return on Invested Capital (ROIC)',
            self::DEBT_TO_EQUITY => 'Debt to Equity Ratio',
            self::DEBT_TO_ASSETS => 'Debt to Assets Ratio',
            self::INTEREST_COVERAGE => 'Interest Coverage Ratio',
            self::DEBT_SERVICE_COVERAGE => 'Debt Service Coverage Ratio',
            self::EQUITY_MULTIPLIER => 'Equity Multiplier',
            self::ASSET_TURNOVER => 'Asset Turnover',
            self::INVENTORY_TURNOVER => 'Inventory Turnover',
            self::RECEIVABLES_TURNOVER => 'Receivables Turnover',
            self::PAYABLES_TURNOVER => 'Payables Turnover',
            self::FIXED_ASSET_TURNOVER => 'Fixed Asset Turnover',
            self::DAYS_SALES_OUTSTANDING => 'Days Sales Outstanding (DSO)',
            self::DAYS_INVENTORY_OUTSTANDING => 'Days Inventory Outstanding (DIO)',
            self::DAYS_PAYABLES_OUTSTANDING => 'Days Payables Outstanding (DPO)',
            self::CASH_CONVERSION_CYCLE => 'Cash Conversion Cycle',
            self::OPERATING_CASH_FLOW_RATIO => 'Operating Cash Flow Ratio',
            self::FREE_CASH_FLOW_TO_EQUITY => 'Free Cash Flow to Equity',
            self::CASH_FLOW_TO_DEBT => 'Cash Flow to Debt Ratio',
            self::CASH_FLOW_MARGIN => 'Cash Flow Margin',
            self::EARNINGS_PER_SHARE => 'Earnings Per Share (EPS)',
            self::PRICE_TO_EARNINGS => 'Price to Earnings (P/E)',
            self::PRICE_TO_BOOK => 'Price to Book (P/B)',
            self::DIVIDEND_YIELD => 'Dividend Yield',
            self::DIVIDEND_PAYOUT => 'Dividend Payout Ratio',
            self::MARKET_TO_BOOK => 'Market to Book Ratio',
            self::DUPONT_ROE => 'DuPont ROE Analysis',
        };
    }
}

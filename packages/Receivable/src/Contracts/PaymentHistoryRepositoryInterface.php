<?php

declare(strict_types=1);

namespace Nexus\Receivable\Contracts;

use DateTimeImmutable;

/**
 * Payment history repository interface
 * 
 * Provides aggregated customer payment behavior data for prediction models.
 */
interface PaymentHistoryRepositoryInterface
{
    /**
     * Get average days to pay for customer
     * 
     * @param string $customerId Customer identifier
     * @param int $months Number of months for calculation
     * @return float Average days from due date to payment (negative = early)
     */
    public function getAverageDaysToPay(string $customerId, int $months = 12): float;
    
    /**
     * Get on-time payment rate for customer
     * 
     * @param string $customerId Customer identifier
     * @param int $months Number of months for calculation
     * @return float Percentage 0.0-100.0 of invoices paid by due date
     */
    public function getOnTimePaymentRate(string $customerId, int $months = 12): float;
    
    /**
     * Get payment consistency score
     * 
     * Lower coefficient of variation = more consistent payment timing.
     * 
     * @param string $customerId Customer identifier
     * @return float Consistency score 0.0-1.0 (1.0 = perfectly consistent)
     */
    public function getPaymentConsistencyScore(string $customerId): float;
    
    /**
     * Get credit utilization ratio
     * 
     * @param string $customerId Customer identifier
     * @return float Current balance / credit limit (0.0-1.0+)
     */
    public function getCreditUtilizationRatio(string $customerId): float;
    
    /**
     * Get overdue balance ratio
     * 
     * @param string $customerId Customer identifier
     * @return float Overdue amount / total receivables (0.0-1.0)
     */
    public function getOverdueBalanceRatio(string $customerId): float;
    
    /**
     * Get customer lifetime value
     * 
     * @param string $customerId Customer identifier
     * @return float Total revenue from customer to date
     */
    public function getCustomerLifetimeValue(string $customerId): float;
    
    /**
     * Get customer tenure in months
     * 
     * @param string $customerId Customer identifier
     * @return int Months since first transaction
     */
    public function getCustomerTenureMonths(string $customerId): int;
    
    /**
     * Get early payment discount capture rate
     * 
     * @param string $customerId Customer identifier
     * @return float Percentage 0.0-100.0 of discounts taken
     */
    public function getEarlyDiscountCaptureRate(string $customerId): float;
    
    /**
     * Get industry payment benchmark (DSO)
     * 
     * @param string $industryCode Industry classification
     * @return float Average days sales outstanding for industry
     */
    public function getIndustryPaymentBenchmark(string $industryCode): float;
    
    /**
     * Get seasonal cash flow factor for current period
     * 
     * @param DateTimeImmutable $date Date to check
     * @return float Multiplier 0.5-1.5 based on seasonal patterns
     */
    public function getSeasonalCashFlowFactor(DateTimeImmutable $date): float;
    
    /**
     * Get customer dispute frequency
     * 
     * @param string $customerId Customer identifier
     * @param int $months Number of months for calculation
     * @return int Number of invoice disputes
     */
    public function getDisputeFrequency(string $customerId, int $months = 12): int;
}

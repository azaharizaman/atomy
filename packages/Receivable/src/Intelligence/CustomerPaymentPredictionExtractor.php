<?php

declare(strict_types=1);

namespace Nexus\Receivable\Intelligence;

use DateTimeImmutable;
use Nexus\Intelligence\Contracts\FeatureExtractorInterface;
use Nexus\Intelligence\Contracts\FeatureSetInterface;
use Nexus\Intelligence\ValueObjects\FeatureSet;
use Nexus\Receivable\Contracts\PaymentHistoryRepositoryInterface;
use Nexus\Receivable\Contracts\CustomerRepositoryInterface;
use Nexus\Currency\Contracts\ExchangeRateServiceInterface;

/**
 * Customer payment prediction extractor
 * 
 * Extracts 20 features to predict actual payment date vs. invoice due date.
 * Uses historical payment behavior, credit metrics, and relationship data.
 * 
 * Schema v1.0 - Initial implementation
 */
final readonly class CustomerPaymentPredictionExtractor implements FeatureExtractorInterface
{
    private const SCHEMA_VERSION = '1.0';
    
    public function __construct(
        private PaymentHistoryRepositoryInterface $paymentHistory,
        private CustomerRepositoryInterface $customerRepository,
        private ExchangeRateServiceInterface $exchangeRate
    ) {}
    
    /**
     * {@inheritDoc}
     */
    public function extract(object $entity): FeatureSetInterface
    {
        // Expected entity: customer_invoice with customer_id, amount, due_date, etc.
        $customerId = $entity->customer_id ?? throw new \InvalidArgumentException('Missing customer_id');
        $invoiceAmount = (float) ($entity->amount ?? 0.0);
        $dueDate = $entity->due_date ?? new DateTimeImmutable();
        $currency = $entity->currency ?? 'MYR';
        
        // Get customer details
        $customer = $this->customerRepository->findById($customerId);
        if ($customer === null) {
            throw new \InvalidArgumentException("Customer {$customerId} not found");
        }
        
        $features = [
            // Historical Payment Behavior (4 features)
            'avg_days_to_pay' => $this->paymentHistory->getAverageDaysToPay($customerId),
            'payment_consistency_score' => $this->paymentHistory->getPaymentConsistencyScore($customerId),
            'on_time_payment_rate' => $this->paymentHistory->getOnTimePaymentRate($customerId),
            'early_payment_frequency' => $this->paymentHistory->getEarlyDiscountCaptureRate($customerId),
            
            // Customer Financial Health (4 features)
            'credit_utilization_ratio' => $this->paymentHistory->getCreditUtilizationRatio($customerId),
            'overdue_balance_ratio' => $this->paymentHistory->getOverdueBalanceRatio($customerId),
            'credit_limit' => $customer->getCreditLimit(),
            'dispute_frequency' => (float) $this->paymentHistory->getDisputeFrequency($customerId),
            
            // Invoice Characteristics (4 features)
            'invoice_amount' => $invoiceAmount,
            'payment_term_days' => $this->calculatePaymentTermDays($entity),
            'early_discount_available' => $this->hasEarlyDiscount($entity) ? 1.0 : 0.0,
            'currency_volatility' => $this->getCurrencyVolatility($currency),
            
            // Relationship Metrics (3 features)
            'customer_lifetime_value' => $this->paymentHistory->getCustomerLifetimeValue($customerId),
            'customer_tenure_months' => (float) $this->paymentHistory->getCustomerTenureMonths($customerId),
            'salesperson_relationship_score' => $this->getSalespersonScore($customer),
            
            // External Factors (3 features)
            'industry_payment_benchmark' => $this->paymentHistory->getIndustryPaymentBenchmark(
                $customer->getIndustryCode()
            ),
            'seasonal_cash_flow_factor' => $this->paymentHistory->getSeasonalCashFlowFactor($dueDate),
            'month_end_proximity' => $this->getMonthEndProximity($dueDate),
            
            // Engineered Features (2 features)
            'payment_urgency_score' => 0.0, // Calculated below
            'collection_difficulty_estimate' => 0.0, // Calculated below
        ];
        
        // Calculate composite scores
        $features['payment_urgency_score'] = $this->calculatePaymentUrgency($features);
        $features['collection_difficulty_estimate'] = $this->calculateCollectionDifficulty($features);
        
        $metadata = [
            'entity_type' => 'customer_invoice',
            'customer_id' => $customerId,
            'invoice_amount' => $invoiceAmount,
            'due_date' => $dueDate->format('Y-m-d'),
            'extracted_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
        
        return new FeatureSet($features, self::SCHEMA_VERSION, $metadata);
    }
    
    /**
     * {@inheritDoc}
     */
    public function getFeatureKeys(): array
    {
        return [
            'avg_days_to_pay',
            'payment_consistency_score',
            'on_time_payment_rate',
            'early_payment_frequency',
            'credit_utilization_ratio',
            'overdue_balance_ratio',
            'credit_limit',
            'dispute_frequency',
            'invoice_amount',
            'payment_term_days',
            'early_discount_available',
            'currency_volatility',
            'customer_lifetime_value',
            'customer_tenure_months',
            'salesperson_relationship_score',
            'industry_payment_benchmark',
            'seasonal_cash_flow_factor',
            'month_end_proximity',
            'payment_urgency_score',
            'collection_difficulty_estimate',
        ];
    }
    
    /**
     * {@inheritDoc}
     */
    public function getSchemaVersion(): string
    {
        return self::SCHEMA_VERSION;
    }
    
    /**
     * Calculate payment term days from invoice
     * 
     * @param object $entity Invoice entity
     * @return float Payment term in days
     */
    private function calculatePaymentTermDays(object $entity): float
    {
        // Parse payment term (e.g., "Net 30", "Net 60")
        $paymentTerm = $entity->payment_term ?? 'Net 30';
        
        if (preg_match('/(\d+)/', $paymentTerm, $matches)) {
            return (float) $matches[1];
        }
        
        return 30.0; // Default
    }
    
    /**
     * Check if invoice has early payment discount
     * 
     * @param object $entity Invoice entity
     * @return bool True if discount available
     */
    private function hasEarlyDiscount(object $entity): bool
    {
        return ($entity->early_discount_percentage ?? 0.0) > 0.0;
    }
    
    /**
     * Get currency volatility factor
     * 
     * @param string $currency Currency code
     * @return float Volatility score 0.0-1.0
     */
    private function getCurrencyVolatility(string $currency): float
    {
        if ($currency === 'MYR') {
            return 0.0; // Base currency, no volatility
        }
        
        try {
            // Get 30-day volatility from exchange rate service
            return $this->exchangeRate->getVolatility($currency, 'MYR', 30);
        } catch (\Throwable $e) {
            return 0.1; // Default low volatility
        }
    }
    
    /**
     * Get salesperson relationship score
     * 
     * @param object $customer Customer entity
     * @return float Relationship score 0.0-1.0
     */
    private function getSalespersonScore(object $customer): float
    {
        // This would query salesperson effectiveness metrics
        return $customer->salesperson_relationship_score ?? 0.5;
    }
    
    /**
     * Get proximity to month end (affects payment timing)
     * 
     * @param DateTimeImmutable $dueDate Invoice due date
     * @return float Days from month end (negative = before, positive = after)
     */
    private function getMonthEndProximity(DateTimeImmutable $dueDate): float
    {
        $lastDayOfMonth = (int) $dueDate->format('t');
        $currentDay = (int) $dueDate->format('d');
        
        return (float) ($currentDay - $lastDayOfMonth);
    }
    
    /**
     * Calculate payment urgency composite score
     * 
     * @param array<string, float> $features Extracted features
     * @return float Urgency score 0.0-1.0
     */
    private function calculatePaymentUrgency(array $features): float
    {
        // High urgency = high utilization + overdue balance + low payment rate
        $utilization = $features['credit_utilization_ratio'] * 0.4;
        $overdue = $features['overdue_balance_ratio'] * 0.3;
        $paymentRate = (100.0 - $features['on_time_payment_rate']) / 100.0 * 0.3;
        
        return min($utilization + $overdue + $paymentRate, 1.0);
    }
    
    /**
     * Calculate collection difficulty estimate
     * 
     * @param array<string, float> $features Extracted features
     * @return float Difficulty score 0.0-1.0
     */
    private function calculateCollectionDifficulty(array $features): float
    {
        // Difficulty factors: late payments + disputes + poor consistency
        $lateness = ($features['avg_days_to_pay'] > 0) ? min($features['avg_days_to_pay'] / 60.0, 1.0) * 0.4 : 0.0;
        $disputes = min($features['dispute_frequency'] / 5.0, 1.0) * 0.3;
        $inconsistency = (1.0 - $features['payment_consistency_score']) * 0.3;
        
        return min($lateness + $disputes + $inconsistency, 1.0);
    }
}

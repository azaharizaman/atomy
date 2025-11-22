<?php

declare(strict_types=1);

namespace Nexus\Intelligence\Tests\Unit;

use DateTimeImmutable;
use Nexus\Intelligence\Extractors\CustomerPaymentPredictionExtractor;
use Nexus\Intelligence\Contracts\PaymentHistoryRepositoryInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('intelligence')]
#[Group('extractors')]
#[Group('receivable')]
final class CustomerPaymentPredictionExtractorTest extends TestCase
{
    private PaymentHistoryRepositoryInterface $repository;
    private CustomerPaymentPredictionExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(PaymentHistoryRepositoryInterface::class);
        $this->extractor = new CustomerPaymentPredictionExtractor($this->repository);
    }

    #[Test]
    public function it_extracts_all_20_features_successfully(): void
    {
        // Arrange: Mock repository responses
        $this->repository->method('getAveragePaymentDelayDays')->willReturn(5.2);
        $this->repository->method('getStdDevPaymentDelayDays')->willReturn(3.1);
        $this->repository->method('getOnTimePaymentRate')->willReturn(0.85);
        $this->repository->method('getLatePaymentRate')->willReturn(0.15);
        $this->repository->method('getAverageDaysToPay')->willReturn(32.5);
        $this->repository->method('getInvoiceCount30d')->willReturn(6);
        $this->repository->method('getInvoiceCount90d')->willReturn(18);
        $this->repository->method('getInvoiceCount365d')->willReturn(72);
        $this->repository->method('getPaidInvoiceCount90d')->willReturn(16);
        $this->repository->method('getOverdueInvoiceCount')->willReturn(2);
        $this->repository->method('getTotalOutstandingAmount')->willReturn(15000.00);
        $this->repository->method('getOverdueAmount')->willReturn(3000.00);
        $this->repository->method('getCreditLimit')->willReturn(50000.00);
        $this->repository->method('getCreditUtilizationRatio')->willReturn(0.30);
        $this->repository->method('getCustomerTenureDays')->willReturn(730);
        $this->repository->method('getLifetimeValue')->willReturn(250000.00);
        $this->repository->method('getLastPaymentDate')->willReturn(new DateTimeImmutable('2024-11-15'));
        $this->repository->method('hasDisputedInvoices')->willReturn(false);
        $this->repository->method('getAverageInvoiceAmount')->willReturn(5000.00);
        $this->repository->method('getPaymentMethodStability')->willReturn(0.95);

        $context = [
            'customer_id' => 'CUST-001',
            'invoice_amount' => 8000.00,
            'due_date' => new DateTimeImmutable('2024-12-22'),
            'invoice_date' => new DateTimeImmutable('2024-11-22'),
        ];

        // Act
        $features = $this->extractor->extract($context);

        // Assert: Verify all 20 features are present
        $this->assertCount(20, $features);
        
        // Verify payment behavior features
        $this->assertArrayHasKey('avg_payment_delay_days', $features);
        $this->assertSame(5.2, $features['avg_payment_delay_days']);
        
        $this->assertArrayHasKey('std_dev_payment_delay', $features);
        $this->assertSame(3.1, $features['std_dev_payment_delay']);
        
        $this->assertArrayHasKey('on_time_payment_rate', $features);
        $this->assertSame(0.85, $features['on_time_payment_rate']);
        
        $this->assertArrayHasKey('late_payment_rate', $features);
        $this->assertSame(0.15, $features['late_payment_rate']);
        
        // Verify activity features
        $this->assertArrayHasKey('invoice_count_30d', $features);
        $this->assertSame(6, $features['invoice_count_30d']);
        
        $this->assertArrayHasKey('invoice_count_90d', $features);
        $this->assertSame(18, $features['invoice_count_90d']);
        
        // Verify credit health features
        $this->assertArrayHasKey('total_outstanding_amount', $features);
        $this->assertSame(15000.00, $features['total_outstanding_amount']);
        
        $this->assertArrayHasKey('overdue_amount', $features);
        $this->assertSame(3000.00, $features['overdue_amount']);
        
        $this->assertArrayHasKey('credit_utilization_ratio', $features);
        $this->assertSame(0.30, $features['credit_utilization_ratio']);
        
        // Verify relationship features
        $this->assertArrayHasKey('customer_tenure_days', $features);
        $this->assertSame(730, $features['customer_tenure_days']);
        
        $this->assertArrayHasKey('lifetime_value', $features);
        $this->assertSame(250000.00, $features['lifetime_value']);
        
        // Verify derived features
        $this->assertArrayHasKey('days_since_last_payment', $features);
        $this->assertSame(7, $features['days_since_last_payment']);
        
        $this->assertArrayHasKey('payment_consistency_score', $features);
        $this->assertGreaterThan(0, $features['payment_consistency_score']);
        
        $this->assertArrayHasKey('predicted_days_to_pay', $features);
        $this->assertGreaterThan(0, $features['predicted_days_to_pay']);
    }

    #[Test]
    public function it_handles_missing_customer_id_gracefully(): void
    {
        $context = [
            'invoice_amount' => 5000.00,
            'due_date' => new DateTimeImmutable('2024-12-22'),
        ];

        $this->repository->expects($this->never())->method('getAveragePaymentDelayDays');

        $features = $this->extractor->extract($context);

        $this->assertIsArray($features);
        $this->assertEmpty($features);
    }

    #[Test]
    #[DataProvider('paymentRateProvider')]
    public function it_calculates_payment_consistency_score_correctly(
        float $onTimeRate,
        float $stdDev,
        float $expectedScore
    ): void {
        $this->repository->method('getAveragePaymentDelayDays')->willReturn(5.0);
        $this->repository->method('getStdDevPaymentDelayDays')->willReturn($stdDev);
        $this->repository->method('getOnTimePaymentRate')->willReturn($onTimeRate);
        $this->repository->method('getLatePaymentRate')->willReturn(1.0 - $onTimeRate);
        $this->repository->method('getAverageDaysToPay')->willReturn(30.0);
        $this->repository->method('getInvoiceCount30d')->willReturn(5);
        $this->repository->method('getInvoiceCount90d')->willReturn(15);
        $this->repository->method('getInvoiceCount365d')->willReturn(60);
        $this->repository->method('getPaidInvoiceCount90d')->willReturn(14);
        $this->repository->method('getOverdueInvoiceCount')->willReturn(1);
        $this->repository->method('getTotalOutstandingAmount')->willReturn(10000.00);
        $this->repository->method('getOverdueAmount')->willReturn(2000.00);
        $this->repository->method('getCreditLimit')->willReturn(50000.00);
        $this->repository->method('getCreditUtilizationRatio')->willReturn(0.20);
        $this->repository->method('getCustomerTenureDays')->willReturn(365);
        $this->repository->method('getLifetimeValue')->willReturn(100000.00);
        $this->repository->method('getLastPaymentDate')->willReturn(new DateTimeImmutable('2024-11-20'));
        $this->repository->method('hasDisputedInvoices')->willReturn(false);
        $this->repository->method('getAverageInvoiceAmount')->willReturn(5000.00);
        $this->repository->method('getPaymentMethodStability')->willReturn(0.90);

        $context = [
            'customer_id' => 'CUST-001',
            'invoice_amount' => 5000.00,
            'due_date' => new DateTimeImmutable('2024-12-22'),
            'invoice_date' => new DateTimeImmutable('2024-11-22'),
        ];

        $features = $this->extractor->extract($context);

        $this->assertEqualsWithDelta($expectedScore, $features['payment_consistency_score'], 0.05);
    }

    public static function paymentRateProvider(): array
    {
        return [
            'Excellent payer (high on-time, low variance)' => [0.95, 1.0, 0.90],
            'Good payer (good on-time, moderate variance)' => [0.80, 3.0, 0.70],
            'Average payer (moderate on-time, high variance)' => [0.60, 5.0, 0.50],
            'Poor payer (low on-time, high variance)' => [0.30, 7.0, 0.30],
        ];
    }

    #[Test]
    public function it_handles_new_customer_with_no_history(): void
    {
        $this->repository->method('getAveragePaymentDelayDays')->willReturn(0.0);
        $this->repository->method('getStdDevPaymentDelayDays')->willReturn(0.0);
        $this->repository->method('getOnTimePaymentRate')->willReturn(0.0);
        $this->repository->method('getLatePaymentRate')->willReturn(0.0);
        $this->repository->method('getAverageDaysToPay')->willReturn(0.0);
        $this->repository->method('getInvoiceCount30d')->willReturn(0);
        $this->repository->method('getInvoiceCount90d')->willReturn(0);
        $this->repository->method('getInvoiceCount365d')->willReturn(0);
        $this->repository->method('getPaidInvoiceCount90d')->willReturn(0);
        $this->repository->method('getOverdueInvoiceCount')->willReturn(0);
        $this->repository->method('getTotalOutstandingAmount')->willReturn(0.0);
        $this->repository->method('getOverdueAmount')->willReturn(0.0);
        $this->repository->method('getCreditLimit')->willReturn(10000.00);
        $this->repository->method('getCreditUtilizationRatio')->willReturn(0.0);
        $this->repository->method('getCustomerTenureDays')->willReturn(0);
        $this->repository->method('getLifetimeValue')->willReturn(0.0);
        $this->repository->method('getLastPaymentDate')->willReturn(null);
        $this->repository->method('hasDisputedInvoices')->willReturn(false);
        $this->repository->method('getAverageInvoiceAmount')->willReturn(0.0);
        $this->repository->method('getPaymentMethodStability')->willReturn(0.0);

        $context = [
            'customer_id' => 'CUST-NEW',
            'invoice_amount' => 5000.00,
            'due_date' => new DateTimeImmutable('2024-12-22'),
            'invoice_date' => new DateTimeImmutable('2024-11-22'),
        ];

        $features = $this->extractor->extract($context);

        // Should extract features with default/zero values
        $this->assertCount(20, $features);
        $this->assertSame(0.0, $features['avg_payment_delay_days']);
        $this->assertSame(0, $features['invoice_count_90d']);
        $this->assertSame(0.0, $features['lifetime_value']);
        
        // days_since_last_payment should be null or very high for new customers
        $this->assertArrayHasKey('days_since_last_payment', $features);
    }

    #[Test]
    #[DataProvider('creditUtilizationProvider')]
    public function it_calculates_credit_risk_score_correctly(
        float $utilizationRatio,
        float $overdueAmount,
        int $overdueCount,
        string $expectedRiskLevel
    ): void {
        $this->repository->method('getAveragePaymentDelayDays')->willReturn(5.0);
        $this->repository->method('getStdDevPaymentDelayDays')->willReturn(2.0);
        $this->repository->method('getOnTimePaymentRate')->willReturn(0.80);
        $this->repository->method('getLatePaymentRate')->willReturn(0.20);
        $this->repository->method('getAverageDaysToPay')->willReturn(30.0);
        $this->repository->method('getInvoiceCount30d')->willReturn(5);
        $this->repository->method('getInvoiceCount90d')->willReturn(15);
        $this->repository->method('getInvoiceCount365d')->willReturn(60);
        $this->repository->method('getPaidInvoiceCount90d')->willReturn(14);
        $this->repository->method('getOverdueInvoiceCount')->willReturn($overdueCount);
        $this->repository->method('getTotalOutstandingAmount')->willReturn(10000.00);
        $this->repository->method('getOverdueAmount')->willReturn($overdueAmount);
        $this->repository->method('getCreditLimit')->willReturn(50000.00);
        $this->repository->method('getCreditUtilizationRatio')->willReturn($utilizationRatio);
        $this->repository->method('getCustomerTenureDays')->willReturn(365);
        $this->repository->method('getLifetimeValue')->willReturn(100000.00);
        $this->repository->method('getLastPaymentDate')->willReturn(new DateTimeImmutable('2024-11-20'));
        $this->repository->method('hasDisputedInvoices')->willReturn(false);
        $this->repository->method('getAverageInvoiceAmount')->willReturn(5000.00);
        $this->repository->method('getPaymentMethodStability')->willReturn(0.90);

        $context = [
            'customer_id' => 'CUST-001',
            'invoice_amount' => 5000.00,
            'due_date' => new DateTimeImmutable('2024-12-22'),
            'invoice_date' => new DateTimeImmutable('2024-11-22'),
        ];

        $features = $this->extractor->extract($context);

        // Verify credit-related features reflect risk level
        $this->assertSame($utilizationRatio, $features['credit_utilization_ratio']);
        $this->assertSame($overdueAmount, $features['overdue_amount']);
        $this->assertSame($overdueCount, $features['overdue_invoice_count']);
        
        // High utilization + high overdue = higher risk (lower consistency score)
        if ($expectedRiskLevel === 'high') {
            $this->assertLessThan(0.60, $features['payment_consistency_score']);
        } elseif ($expectedRiskLevel === 'low') {
            $this->assertGreaterThan(0.70, $features['payment_consistency_score']);
        }
    }

    public static function creditUtilizationProvider(): array
    {
        return [
            'Low risk (low utilization, no overdue)' => [0.20, 0.0, 0, 'low'],
            'Medium risk (moderate utilization, small overdue)' => [0.50, 2000.0, 1, 'medium'],
            'High risk (high utilization, significant overdue)' => [0.85, 8000.0, 3, 'high'],
            'Very high risk (maxed out, large overdue)' => [0.95, 15000.0, 5, 'high'],
        ];
    }

    #[Test]
    public function it_predicts_days_to_pay_based_on_historical_patterns(): void
    {
        $avgDaysToPay = 35.0;
        $avgDelayDays = 5.0;
        
        $this->repository->method('getAveragePaymentDelayDays')->willReturn($avgDelayDays);
        $this->repository->method('getStdDevPaymentDelayDays')->willReturn(2.5);
        $this->repository->method('getOnTimePaymentRate')->willReturn(0.75);
        $this->repository->method('getLatePaymentRate')->willReturn(0.25);
        $this->repository->method('getAverageDaysToPay')->willReturn($avgDaysToPay);
        $this->repository->method('getInvoiceCount30d')->willReturn(5);
        $this->repository->method('getInvoiceCount90d')->willReturn(15);
        $this->repository->method('getInvoiceCount365d')->willReturn(60);
        $this->repository->method('getPaidInvoiceCount90d')->willReturn(13);
        $this->repository->method('getOverdueInvoiceCount')->willReturn(2);
        $this->repository->method('getTotalOutstandingAmount')->willReturn(12000.00);
        $this->repository->method('getOverdueAmount')->willReturn(4000.00);
        $this->repository->method('getCreditLimit')->willReturn(50000.00);
        $this->repository->method('getCreditUtilizationRatio')->willReturn(0.24);
        $this->repository->method('getCustomerTenureDays')->willReturn(730);
        $this->repository->method('getLifetimeValue')->willReturn(200000.00);
        $this->repository->method('getLastPaymentDate')->willReturn(new DateTimeImmutable('2024-11-18'));
        $this->repository->method('hasDisputedInvoices')->willReturn(false);
        $this->repository->method('getAverageInvoiceAmount')->willReturn(5500.00);
        $this->repository->method('getPaymentMethodStability')->willReturn(0.88);

        $context = [
            'customer_id' => 'CUST-001',
            'invoice_amount' => 6000.00,
            'due_date' => new DateTimeImmutable('2024-12-22'),
            'invoice_date' => new DateTimeImmutable('2024-11-22'),
        ];

        $features = $this->extractor->extract($context);

        // predicted_days_to_pay should be close to avg_days_to_pay
        $this->assertArrayHasKey('predicted_days_to_pay', $features);
        $this->assertEqualsWithDelta($avgDaysToPay, $features['predicted_days_to_pay'], 10.0);
    }
}

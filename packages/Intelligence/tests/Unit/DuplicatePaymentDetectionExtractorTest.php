<?php

declare(strict_types=1);

namespace Nexus\Intelligence\Tests\Unit;

use DateTimeImmutable;
use Nexus\Intelligence\Extractors\DuplicatePaymentDetectionExtractor;
use Nexus\Intelligence\Contracts\VendorPaymentAnalyticsRepositoryInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('intelligence')]
#[Group('extractors')]
#[Group('payable')]
final class DuplicatePaymentDetectionExtractorTest extends TestCase
{
    private VendorPaymentAnalyticsRepositoryInterface $repository;
    private DuplicatePaymentDetectionExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(VendorPaymentAnalyticsRepositoryInterface::class);
        $this->extractor = new DuplicatePaymentDetectionExtractor($this->repository);
    }

    #[Test]
    public function it_extracts_all_22_features_successfully(): void
    {
        // Arrange: Mock repository responses
        $this->repository->method('getAveragePaymentAmount')->willReturn(5000.00);
        $this->repository->method('getStdDevPaymentAmount')->willReturn(1200.00);
        $this->repository->method('getPaymentCount30d')->willReturn(8);
        $this->repository->method('getPaymentCount90d')->willReturn(24);
        $this->repository->method('getDuplicatePaymentCount90d')->willReturn(2);
        $this->repository->method('getHighRiskPaymentCount90d')->willReturn(1);
        $this->repository->method('getAverageDaysBetweenPayments')->willReturn(10.5);
        $this->repository->method('hasPaymentOnWeekend')->willReturn(false);
        $this->repository->method('hasPaymentAfterHours')->willReturn(true);
        $this->repository->method('getTotalPaymentAmount90d')->willReturn(120000.00);
        $this->repository->method('getUniquePaymentDays90d')->willReturn(22);

        $this->repository->method('findSimilarRecentPayments')
            ->willReturn([
                [
                    'payment_id' => 'PAY-001',
                    'amount' => 10000.00,
                    'payment_date' => '2024-11-20',
                    'similarity_score' => 0.95,
                ],
            ]);

        $context = [
            'vendor_id' => 'VEN-001',
            'amount' => 10000.00,
            'payment_date' => new DateTimeImmutable('2024-11-22'),
            'description' => 'Monthly service fee',
            'payment_method' => 'bank_transfer',
        ];

        // Act
        $features = $this->extractor->extract($context);

        // Assert: Verify all 22 features are present
        $this->assertCount(22, $features);
        
        // Verify statistical features
        $this->assertArrayHasKey('avg_payment_amount', $features);
        $this->assertSame(5000.00, $features['avg_payment_amount']);
        
        $this->assertArrayHasKey('std_dev_payment_amount', $features);
        $this->assertSame(1200.00, $features['std_dev_payment_amount']);
        
        // Verify frequency features
        $this->assertArrayHasKey('payment_count_30d', $features);
        $this->assertSame(8, $features['payment_count_30d']);
        
        $this->assertArrayHasKey('payment_count_90d', $features);
        $this->assertSame(24, $features['payment_count_90d']);
        
        // Verify duplicate detection features
        $this->assertArrayHasKey('duplicate_count_90d', $features);
        $this->assertSame(2, $features['duplicate_count_90d']);
        
        $this->assertArrayHasKey('high_risk_payment_count_90d', $features);
        $this->assertSame(1, $features['high_risk_payment_count_90d']);
        
        // Verify temporal features
        $this->assertArrayHasKey('avg_days_between_payments', $features);
        $this->assertSame(10.5, $features['avg_days_between_payments']);
        
        $this->assertArrayHasKey('is_weekend_payment', $features);
        $this->assertFalse($features['is_weekend_payment']);
        
        $this->assertArrayHasKey('is_after_hours_payment', $features);
        $this->assertTrue($features['is_after_hours_payment']);
        
        // Verify derived features
        $this->assertArrayHasKey('amount_to_avg_ratio', $features);
        $this->assertEqualsWithDelta(2.0, $features['amount_to_avg_ratio'], 0.01);
        
        $this->assertArrayHasKey('z_score_amount', $features);
        $this->assertGreaterThan(0, $features['z_score_amount']);
        
        // Verify similarity features
        $this->assertArrayHasKey('similar_payment_count_7d', $features);
        $this->assertSame(1, $features['similar_payment_count_7d']);
        
        $this->assertArrayHasKey('max_similarity_score', $features);
        $this->assertSame(0.95, $features['max_similarity_score']);
        
        // Verify string analysis features
        $this->assertArrayHasKey('description_length', $features);
        $this->assertSame(19, $features['description_length']);
        
        $this->assertArrayHasKey('description_word_count', $features);
        $this->assertSame(3, $features['description_word_count']);
    }

    #[Test]
    public function it_handles_missing_vendor_id_gracefully(): void
    {
        $context = [
            'amount' => 5000.00,
            'payment_date' => new DateTimeImmutable('2024-11-22'),
        ];

        $this->repository->expects($this->never())->method('getAveragePaymentAmount');

        $features = $this->extractor->extract($context);

        // Should return empty features array when vendor_id is missing
        $this->assertIsArray($features);
        $this->assertEmpty($features);
    }

    #[Test]
    #[DataProvider('weekendDatesProvider')]
    public function it_correctly_identifies_weekend_payments(string $date, bool $expectedIsWeekend): void
    {
        $this->repository->method('getAveragePaymentAmount')->willReturn(5000.00);
        $this->repository->method('getStdDevPaymentAmount')->willReturn(1200.00);
        $this->repository->method('getPaymentCount30d')->willReturn(8);
        $this->repository->method('getPaymentCount90d')->willReturn(24);
        $this->repository->method('getDuplicatePaymentCount90d')->willReturn(0);
        $this->repository->method('getHighRiskPaymentCount90d')->willReturn(0);
        $this->repository->method('getAverageDaysBetweenPayments')->willReturn(10.5);
        $this->repository->method('hasPaymentOnWeekend')->willReturn(false);
        $this->repository->method('hasPaymentAfterHours')->willReturn(false);
        $this->repository->method('getTotalPaymentAmount90d')->willReturn(120000.00);
        $this->repository->method('getUniquePaymentDays90d')->willReturn(22);
        $this->repository->method('findSimilarRecentPayments')->willReturn([]);

        $context = [
            'vendor_id' => 'VEN-001',
            'amount' => 5000.00,
            'payment_date' => new DateTimeImmutable($date),
            'description' => 'Test payment',
        ];

        $features = $this->extractor->extract($context);

        $this->assertSame($expectedIsWeekend, $features['is_weekend_payment']);
    }

    public static function weekendDatesProvider(): array
    {
        return [
            'Saturday' => ['2024-11-23', true],  // Saturday
            'Sunday' => ['2024-11-24', true],    // Sunday
            'Monday' => ['2024-11-25', false],   // Monday
            'Friday' => ['2024-11-22', false],   // Friday
        ];
    }

    #[Test]
    #[DataProvider('zScoreProvider')]
    public function it_calculates_z_score_correctly(float $amount, float $avg, float $stdDev, float $expectedZScore): void
    {
        $this->repository->method('getAveragePaymentAmount')->willReturn($avg);
        $this->repository->method('getStdDevPaymentAmount')->willReturn($stdDev);
        $this->repository->method('getPaymentCount30d')->willReturn(8);
        $this->repository->method('getPaymentCount90d')->willReturn(24);
        $this->repository->method('getDuplicatePaymentCount90d')->willReturn(0);
        $this->repository->method('getHighRiskPaymentCount90d')->willReturn(0);
        $this->repository->method('getAverageDaysBetweenPayments')->willReturn(10.5);
        $this->repository->method('hasPaymentOnWeekend')->willReturn(false);
        $this->repository->method('hasPaymentAfterHours')->willReturn(false);
        $this->repository->method('getTotalPaymentAmount90d')->willReturn(120000.00);
        $this->repository->method('getUniquePaymentDays90d')->willReturn(22);
        $this->repository->method('findSimilarRecentPayments')->willReturn([]);

        $context = [
            'vendor_id' => 'VEN-001',
            'amount' => $amount,
            'payment_date' => new DateTimeImmutable('2024-11-22'),
            'description' => 'Test',
        ];

        $features = $this->extractor->extract($context);

        $this->assertEqualsWithDelta($expectedZScore, $features['z_score_amount'], 0.01);
    }

    public static function zScoreProvider(): array
    {
        return [
            'One std dev above mean' => [6200.00, 5000.00, 1200.00, 1.0],
            'Two std dev above mean' => [7400.00, 5000.00, 1200.00, 2.0],
            'At mean' => [5000.00, 5000.00, 1200.00, 0.0],
            'One std dev below mean' => [3800.00, 5000.00, 1200.00, -1.0],
            'Zero std dev (edge case)' => [5000.00, 5000.00, 0.0, 0.0],
        ];
    }

    #[Test]
    public function it_handles_null_repository_values_gracefully(): void
    {
        $this->repository->method('getAveragePaymentAmount')->willReturn(0.0);
        $this->repository->method('getStdDevPaymentAmount')->willReturn(0.0);
        $this->repository->method('getPaymentCount30d')->willReturn(0);
        $this->repository->method('getPaymentCount90d')->willReturn(0);
        $this->repository->method('getDuplicatePaymentCount90d')->willReturn(0);
        $this->repository->method('getHighRiskPaymentCount90d')->willReturn(0);
        $this->repository->method('getAverageDaysBetweenPayments')->willReturn(0.0);
        $this->repository->method('hasPaymentOnWeekend')->willReturn(false);
        $this->repository->method('hasPaymentAfterHours')->willReturn(false);
        $this->repository->method('getTotalPaymentAmount90d')->willReturn(0.0);
        $this->repository->method('getUniquePaymentDays90d')->willReturn(0);
        $this->repository->method('findSimilarRecentPayments')->willReturn([]);

        $context = [
            'vendor_id' => 'VEN-NEW',
            'amount' => 5000.00,
            'payment_date' => new DateTimeImmutable('2024-11-22'),
            'description' => 'First payment',
        ];

        $features = $this->extractor->extract($context);

        // Should handle division by zero gracefully
        $this->assertArrayHasKey('amount_to_avg_ratio', $features);
        $this->assertArrayHasKey('z_score_amount', $features);
        $this->assertArrayHasKey('payment_frequency_score', $features);
    }

    #[Test]
    public function it_calculates_levenshtein_similarity_for_similar_payments(): void
    {
        $this->repository->method('getAveragePaymentAmount')->willReturn(5000.00);
        $this->repository->method('getStdDevPaymentAmount')->willReturn(1200.00);
        $this->repository->method('getPaymentCount30d')->willReturn(8);
        $this->repository->method('getPaymentCount90d')->willReturn(24);
        $this->repository->method('getDuplicatePaymentCount90d')->willReturn(0);
        $this->repository->method('getHighRiskPaymentCount90d')->willReturn(0);
        $this->repository->method('getAverageDaysBetweenPayments')->willReturn(10.5);
        $this->repository->method('hasPaymentOnWeekend')->willReturn(false);
        $this->repository->method('hasPaymentAfterHours')->willReturn(false);
        $this->repository->method('getTotalPaymentAmount90d')->willReturn(120000.00);
        $this->repository->method('getUniquePaymentDays90d')->willReturn(22);

        $this->repository->method('findSimilarRecentPayments')
            ->willReturn([
                [
                    'payment_id' => 'PAY-001',
                    'amount' => 10000.00,
                    'payment_date' => '2024-11-20',
                    'similarity_score' => 0.85,
                ],
                [
                    'payment_id' => 'PAY-002',
                    'amount' => 10000.50,
                    'payment_date' => '2024-11-21',
                    'similarity_score' => 0.92,
                ],
            ]);

        $context = [
            'vendor_id' => 'VEN-001',
            'amount' => 10000.00,
            'payment_date' => new DateTimeImmutable('2024-11-22'),
            'description' => 'Monthly service invoice',
        ];

        $features = $this->extractor->extract($context);

        $this->assertSame(2, $features['similar_payment_count_7d']);
        $this->assertSame(0.92, $features['max_similarity_score']);
        $this->assertEqualsWithDelta(0.885, $features['avg_similarity_score'], 0.01);
    }
}

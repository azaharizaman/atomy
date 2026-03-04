<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Nexus\QuotationIntelligence\Services\RuleBasedRiskAssessmentService;
use Nexus\QuotationIntelligence\DTOs\NormalizedQuoteLine;
use Psr\Log\LoggerInterface;

final class RuleBasedRiskAssessmentServiceTest extends TestCase
{
    private $logger;
    private $service;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new RuleBasedRiskAssessmentService($this->logger);
    }

    public function test_flags_low_ai_confidence_risk(): void
    {
        // 1. Arrange
        $line = new NormalizedQuoteLine(
            'L1', 'Laptop', '43211503', 1.0, 'UNIT', 1.0, 1000.0, 1000.0, 0.5 // 0.5 < 0.7 threshold
        );

        // 2. Act
        $risks = $this->service->assess('T1', 'R1', [$line]);

        // 3. Assert
        $this->assertCount(1, $risks);
        $this->assertSame('medium', $risks[0]['level']);
        $this->assertStringContainsString('Low AI extraction confidence', $risks[0]['message']);
    }

    public function test_flags_terms_deviation_risk(): void
    {
        // 1. Arrange
        $line = new NormalizedQuoteLine(
            'L1',
            'Laptop',
            '43211503',
            1.0,
            'UNIT',
            1.0,
            1000.0,
            1000.0,
            0.95,
            [],
            [
                'commercial_terms' => [
                    'incoterm' => 'EXW',
                    'payment_days' => 30,
                    'lead_time_days' => 10,
                    'warranty_months' => 24,
                ],
            ]
        );

        // 2. Act
        $risks = $this->service->assess('T1', 'R1', [$line]);

        // 3. Assert
        $this->assertCount(1, $risks);
        $this->assertSame('high', $risks[0]['level']);
        $this->assertStringContainsString('Detected EXW term', $risks[0]['message']);
    }

    public function test_flags_long_payment_term_risk(): void
    {
        // 1. Arrange
        $line = new NormalizedQuoteLine(
            'L1',
            'Laptop',
            '43211503',
            1.0,
            'UNIT',
            1.0,
            1000.0,
            1000.0,
            0.95,
            [],
            [
                'commercial_terms' => [
                    'incoterm' => 'DDP',
                    'payment_days' => 60,
                    'lead_time_days' => 10,
                    'warranty_months' => 24,
                ],
            ]
        );

        // 2. Act
        $risks = $this->service->assess('T1', 'R1', [$line]);

        // 3. Assert
        $this->assertCount(1, $risks);
        $this->assertSame('medium', $risks[0]['level']);
        $this->assertStringContainsString('Long payment term detected', $risks[0]['message']);
    }

    public function test_detects_pricing_anomaly_against_peers(): void
    {
        // 1. Arrange
        $line = new NormalizedQuoteLine('L1', 'P1', 'C1', 1, 'U', 1, 10.0, 10.0, 1.0); // outlier
        $peers = [
            new NormalizedQuoteLine('L1', 'P1', 'C1', 1, 'U', 1, 100.0, 100.0, 1.0),
            new NormalizedQuoteLine('L1', 'P1', 'C1', 1, 'U', 1, 105.0, 105.0, 1.0),
            new NormalizedQuoteLine('L1', 'P1', 'C1', 1, 'U', 1, 95.0, 95.0, 1.0),
        ];

        // 2. Act
        $isAnomaly = $this->service->isPricingAnomaly($line, $peers);

        // 3. Assert
        $this->assertTrue($isAnomaly);
    }

    public function test_detects_no_anomaly_when_peer_lines_empty(): void
    {
        $line = new NormalizedQuoteLine('L1', 'P1', 'C1', 1, 'U', 1, 10.0, 10.0, 1.0);
        $this->assertFalse($this->service->isPricingAnomaly($line, []));
    }

    public function test_detects_no_anomaly_when_peer_average_is_zero(): void
    {
        $line = new NormalizedQuoteLine('L1', 'P1', 'C1', 1, 'U', 1, 10.0, 10.0, 1.0);
        $peers = [
            new NormalizedQuoteLine('L1', 'P1', 'C1', 1, 'U', 1, 0.0, 0.0, 1.0),
            new NormalizedQuoteLine('L1', 'P1', 'C1', 1, 'U', 1, 0.0, 0.0, 1.0),
        ];

        $this->assertFalse($this->service->isPricingAnomaly($line, $peers));
    }

    public function test_flags_long_lead_time_and_short_warranty_risks(): void
    {
        $line = new NormalizedQuoteLine(
            'L1',
            'Laptop',
            '43211503',
            1.0,
            'UNIT',
            1.0,
            1000.0,
            1000.0,
            0.95,
            [],
            [
                'commercial_terms' => [
                    'incoterm' => 'DDP',
                    'payment_days' => 30,
                    'lead_time_days' => 45,
                    'warranty_months' => 6,
                ],
            ]
        );

        $risks = $this->service->assess('T1', 'R1', [$line]);

        $this->assertCount(2, $risks);
        $this->assertStringContainsString('Long lead time detected', $risks[0]['message'] . $risks[1]['message']);
        $this->assertStringContainsString('Short warranty detected', $risks[0]['message'] . $risks[1]['message']);
    }

    public function test_handles_non_array_commercial_terms_without_risk(): void
    {
        $line = new NormalizedQuoteLine(
            'L1',
            'Laptop',
            '43211503',
            1.0,
            'UNIT',
            1.0,
            1000.0,
            1000.0,
            0.95,
            [],
            [
                'commercial_terms' => 'invalid',
            ]
        );

        $risks = $this->service->assess('T1', 'R1', [$line]);
        $this->assertCount(0, $risks);
    }
}

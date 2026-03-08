<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Tests\Unit\Coordinators;

use Nexus\QuotationIntelligence\Coordinators\BatchQuoteComparisonCoordinator;
use Nexus\QuotationIntelligence\Contracts\ComparisonReadinessValidatorInterface;
use Nexus\QuotationIntelligence\Contracts\QuotationIntelligenceCoordinatorInterface;
use Nexus\QuotationIntelligence\Contracts\QuoteComparisonMatrixServiceInterface;
use Nexus\QuotationIntelligence\Contracts\RiskAssessmentServiceInterface;
use Nexus\QuotationIntelligence\Contracts\VendorScoringServiceInterface;
use Nexus\QuotationIntelligence\Contracts\ApprovalGateServiceInterface;
use Nexus\QuotationIntelligence\Contracts\DecisionTrailWriterInterface;
use Nexus\QuotationIntelligence\Exceptions\MissingVendorContextException;
use Nexus\QuotationIntelligence\ValueObjects\ComparisonReadinessResult;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class BatchQuoteComparisonCoordinatorTest extends TestCase
{
    private function createPassingReadinessValidator(): ComparisonReadinessValidatorInterface
    {
        $validator = $this->createMock(ComparisonReadinessValidatorInterface::class);
        $validator->method('validate')->willReturn(ComparisonReadinessResult::pass());
        return $validator;
    }

    public function test_compare_quotes_adds_peer_anomaly_risk(): void
    {
        $quoteCoordinator = $this->createMock(QuotationIntelligenceCoordinatorInterface::class);
        $matrixService = $this->createMock(QuoteComparisonMatrixServiceInterface::class);
        $riskService = $this->createMock(RiskAssessmentServiceInterface::class);
        $scoringService = $this->createMock(VendorScoringServiceInterface::class);
        $approvalGateService = $this->createMock(ApprovalGateServiceInterface::class);
        $decisionTrailWriter = $this->createMock(DecisionTrailWriterInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $quoteCoordinator->method('processQuote')
            ->willReturnMap([
                ['tenant-1', 'doc-a', [
                    'lines' => [[
                        'rfq_line_id' => 'rfq-line-1',
                        'vendor_description' => 'Laptop Pro',
                        'taxonomy_code' => '43211503',
                        'quoted_quantity' => 1.0,
                        'quoted_unit' => 'EA',
                        'normalized_quantity' => 1.0,
                        'quoted_unit_price' => 1200.0,
                        'normalized_unit_price' => 1200.0,
                        'ai_confidence' => 0.95,
                        'metadata' => ['vendor_id' => 'vendor-a'],
                    ]],
                    'risks' => [],
                ]],
                ['tenant-1', 'doc-b', [
                    'lines' => [[
                        'rfq_line_id' => 'rfq-line-1',
                        'vendor_description' => 'Notebook',
                        'taxonomy_code' => '43211503',
                        'quoted_quantity' => 1.0,
                        'quoted_unit' => 'EA',
                        'normalized_quantity' => 1.0,
                        'quoted_unit_price' => 1000.0,
                        'normalized_unit_price' => 1000.0,
                        'ai_confidence' => 0.95,
                        'metadata' => ['vendor_id' => 'vendor-b'],
                    ]],
                    'risks' => [],
                ]],
            ]);

        $matrixService->expects($this->once())
            ->method('buildMatrix')
            ->with(
                'tenant-1',
                'rfq-1',
                $this->callback(static fn(array $sets): bool => count($sets) === 2)
            )
            ->willReturn([
                'tenant_id' => 'tenant-1',
                'rfq_id' => 'rfq-1',
                'clusters' => [[
                    'cluster_key' => 'rfq:rfq-line-1',
                    'basis' => 'rfq_line_id',
                    'offers' => [
                        ['vendor_id' => 'vendor-a', 'rfq_line_id' => 'rfq-line-1', 'taxonomy_code' => '43211503'],
                        ['vendor_id' => 'vendor-b', 'rfq_line_id' => 'rfq-line-1', 'taxonomy_code' => '43211503'],
                    ],
                    'statistics' => [],
                    'recommendation' => [],
                ]],
            ]);

        $riskService->method('assess')->willReturn([]);
        $riskService->expects($this->exactly(2))
            ->method('isPricingAnomaly')
            ->willReturnOnConsecutiveCalls(true, false);
        $scoringService->expects($this->once())
            ->method('score')
            ->willReturn([
                'weights' => ['price' => 0.5, 'risk' => 0.2, 'delivery' => 0.15, 'sustainability' => 0.15],
                'ranking' => [],
            ]);
        $approvalGateService->expects($this->once())
            ->method('evaluate')
            ->willReturn([
                'required' => true,
                'status' => 'pending_approval',
                'reasons' => ['High risk detected'],
            ]);
        $decisionTrailWriter->expects($this->once())
            ->method('write')
            ->willReturn([[
                'sequence' => 1,
                'event_type' => 'matrix_built',
                'payload_hash' => 'a',
                'previous_hash' => str_repeat('0', 64),
                'entry_hash' => 'b',
                'occurred_at' => '2026-03-01T00:00:00+00:00',
            ]]);

        $coordinator = new BatchQuoteComparisonCoordinator(
            $quoteCoordinator,
            $matrixService,
            $riskService,
            $scoringService,
            $approvalGateService,
            $decisionTrailWriter,
            $this->createPassingReadinessValidator(),
            $logger
        );

        $result = $coordinator->compareQuotes('tenant-1', 'rfq-1', ['doc-a', 'doc-b']);

        $this->assertSame(2, $result['documents_processed']);
        $this->assertCount(2, $result['vendors']);
        $this->assertCount(1, $result['vendors'][0]['risks']);
        $this->assertSame('high', $result['vendors'][0]['risks'][0]['level']);
        $this->assertCount(0, $result['vendors'][1]['risks']);
        $this->assertArrayHasKey('scoring', $result);
        $this->assertArrayHasKey('approval', $result);
        $this->assertArrayHasKey('decision_trail', $result);
    }

    public function test_compare_quotes_throws_when_vendor_context_missing(): void
    {
        $quoteCoordinator = $this->createMock(QuotationIntelligenceCoordinatorInterface::class);
        $matrixService = $this->createMock(QuoteComparisonMatrixServiceInterface::class);
        $riskService = $this->createMock(RiskAssessmentServiceInterface::class);
        $scoringService = $this->createMock(VendorScoringServiceInterface::class);
        $approvalGateService = $this->createMock(ApprovalGateServiceInterface::class);
        $decisionTrailWriter = $this->createMock(DecisionTrailWriterInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $quoteCoordinator->method('processQuote')->willReturn([
            'lines' => [[
                'rfq_line_id' => 'rfq-line-1',
                'vendor_description' => 'Laptop Pro',
                'taxonomy_code' => '43211503',
                'quoted_quantity' => 1.0,
                'quoted_unit' => 'EA',
                'normalized_quantity' => 1.0,
                'quoted_unit_price' => 1200.0,
                'normalized_unit_price' => 1200.0,
                'ai_confidence' => 0.95,
                'metadata' => [],
            ]],
            'risks' => [],
        ]);

        $coordinator = new BatchQuoteComparisonCoordinator(
            $quoteCoordinator,
            $matrixService,
            $riskService,
            $scoringService,
            $approvalGateService,
            $decisionTrailWriter,
            $this->createPassingReadinessValidator(),
            $logger
        );

        $this->expectException(MissingVendorContextException::class);
        $coordinator->compareQuotes('tenant-1', 'rfq-1', ['doc-a']);
    }

    public function test_compare_quotes_adds_peer_term_deviation_risk(): void
    {
        $quoteCoordinator = $this->createMock(QuotationIntelligenceCoordinatorInterface::class);
        $matrixService = $this->createMock(QuoteComparisonMatrixServiceInterface::class);
        $riskService = $this->createMock(RiskAssessmentServiceInterface::class);
        $scoringService = $this->createMock(VendorScoringServiceInterface::class);
        $approvalGateService = $this->createMock(ApprovalGateServiceInterface::class);
        $decisionTrailWriter = $this->createMock(DecisionTrailWriterInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $quoteCoordinator->method('processQuote')
            ->willReturnMap([
                ['tenant-1', 'doc-a', [
                    'lines' => [[
                        'rfq_line_id' => 'rfq-line-1',
                        'vendor_description' => 'Laptop Pro',
                        'taxonomy_code' => '43211503',
                        'quoted_quantity' => 1.0,
                        'quoted_unit' => 'EA',
                        'normalized_quantity' => 1.0,
                        'quoted_unit_price' => 1000.0,
                        'normalized_unit_price' => 1000.0,
                        'ai_confidence' => 0.95,
                        'metadata' => [
                            'vendor_id' => 'vendor-a',
                            'commercial_terms' => ['incoterm' => 'EXW', 'payment_days' => 30],
                        ],
                    ]],
                    'risks' => [],
                ]],
                ['tenant-1', 'doc-b', [
                    'lines' => [[
                        'rfq_line_id' => 'rfq-line-1',
                        'vendor_description' => 'Notebook',
                        'taxonomy_code' => '43211503',
                        'quoted_quantity' => 1.0,
                        'quoted_unit' => 'EA',
                        'normalized_quantity' => 1.0,
                        'quoted_unit_price' => 990.0,
                        'normalized_unit_price' => 990.0,
                        'ai_confidence' => 0.95,
                        'metadata' => [
                            'vendor_id' => 'vendor-b',
                            'commercial_terms' => ['incoterm' => 'DDP', 'payment_days' => 30],
                        ],
                    ]],
                    'risks' => [],
                ]],
            ]);

        $matrixService->method('buildMatrix')->willReturn([
            'tenant_id' => 'tenant-1',
            'rfq_id' => 'rfq-1',
            'clusters' => [[
                'cluster_key' => 'rfq:rfq-line-1',
                'basis' => 'rfq_line_id',
                'offers' => [
                    ['vendor_id' => 'vendor-a', 'rfq_line_id' => 'rfq-line-1', 'taxonomy_code' => '43211503'],
                    ['vendor_id' => 'vendor-b', 'rfq_line_id' => 'rfq-line-1', 'taxonomy_code' => '43211503'],
                ],
                'statistics' => [],
                'recommendation' => [],
            ]],
        ]);

        $riskService->method('assess')->willReturn([]);
        $riskService->method('isPricingAnomaly')->willReturn(false);
        $scoringService->expects($this->once())
            ->method('score')
            ->willReturn([
                'weights' => ['price' => 0.5, 'risk' => 0.2, 'delivery' => 0.15, 'sustainability' => 0.15],
                'ranking' => [],
            ]);
        $approvalGateService->expects($this->once())
            ->method('evaluate')
            ->willReturn([
                'required' => false,
                'status' => 'auto_approved',
                'reasons' => [],
            ]);
        $decisionTrailWriter->expects($this->once())
            ->method('write')
            ->willReturn([]);

        $coordinator = new BatchQuoteComparisonCoordinator(
            $quoteCoordinator,
            $matrixService,
            $riskService,
            $scoringService,
            $approvalGateService,
            $decisionTrailWriter,
            $this->createPassingReadinessValidator(),
            $logger
        );

        $result = $coordinator->compareQuotes('tenant-1', 'rfq-1', ['doc-a', 'doc-b']);

        $this->assertCount(2, $result['vendors']);
        $this->assertCount(1, $result['vendors'][0]['risks']);
        $this->assertStringContainsString('Commercial term deviation detected', $result['vendors'][0]['risks'][0]['message']);
    }

    public function test_compare_quotes_adds_payment_days_term_deviation_risk(): void
    {
        $quoteCoordinator = $this->createMock(QuotationIntelligenceCoordinatorInterface::class);
        $matrixService = $this->createMock(QuoteComparisonMatrixServiceInterface::class);
        $riskService = $this->createMock(RiskAssessmentServiceInterface::class);
        $scoringService = $this->createMock(VendorScoringServiceInterface::class);
        $approvalGateService = $this->createMock(ApprovalGateServiceInterface::class);
        $decisionTrailWriter = $this->createMock(DecisionTrailWriterInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $quoteCoordinator->method('processQuote')
            ->willReturnMap([
                ['tenant-1', 'doc-a', [
                    'lines' => [[
                        'rfq_line_id' => 'rfq-line-1',
                        'vendor_description' => 'Laptop Pro',
                        'taxonomy_code' => '43211503',
                        'quoted_quantity' => 1.0,
                        'quoted_unit' => 'EA',
                        'normalized_quantity' => 1.0,
                        'quoted_unit_price' => 1000.0,
                        'normalized_unit_price' => 1000.0,
                        'ai_confidence' => 0.95,
                        'metadata' => [
                            'vendor_id' => 'vendor-a',
                            'commercial_terms' => ['incoterm' => 'DDP', 'payment_days' => 60],
                        ],
                    ]],
                    'risks' => [],
                ]],
                ['tenant-1', 'doc-b', [
                    'lines' => [[
                        'rfq_line_id' => 'rfq-line-1',
                        'vendor_description' => 'Notebook',
                        'taxonomy_code' => '43211503',
                        'quoted_quantity' => 1.0,
                        'quoted_unit' => 'EA',
                        'normalized_quantity' => 1.0,
                        'quoted_unit_price' => 990.0,
                        'normalized_unit_price' => 990.0,
                        'ai_confidence' => 0.95,
                        'metadata' => [
                            'vendor_id' => 'vendor-b',
                            'commercial_terms' => ['incoterm' => 'DDP', 'payment_days' => 30],
                        ],
                    ]],
                    'risks' => [],
                ]],
            ]);

        $matrixService->method('buildMatrix')->willReturn([
            'tenant_id' => 'tenant-1',
            'rfq_id' => 'rfq-1',
            'clusters' => [[
                'cluster_key' => 'rfq:rfq-line-1',
                'basis' => 'rfq_line_id',
                'offers' => [
                    ['vendor_id' => 'vendor-a', 'rfq_line_id' => 'rfq-line-1', 'taxonomy_code' => '43211503'],
                    ['vendor_id' => 'vendor-b', 'rfq_line_id' => 'rfq-line-1', 'taxonomy_code' => '43211503'],
                ],
                'statistics' => [],
                'recommendation' => [],
            ]],
        ]);

        $riskService->method('assess')->willReturn([]);
        $riskService->method('isPricingAnomaly')->willReturn(false);
        $scoringService->method('score')->willReturn(['weights' => [], 'ranking' => []]);
        $approvalGateService->method('evaluate')->willReturn(['required' => false, 'status' => 'auto_approved', 'reasons' => []]);
        $decisionTrailWriter->method('write')->willReturn([]);

        $coordinator = new BatchQuoteComparisonCoordinator(
            $quoteCoordinator,
            $matrixService,
            $riskService,
            $scoringService,
            $approvalGateService,
            $decisionTrailWriter,
            $this->createPassingReadinessValidator(),
            $logger
        );

        $result = $coordinator->compareQuotes('tenant-1', 'rfq-1', ['doc-a', 'doc-b']);

        $this->assertCount(2, $result['vendors']);
        $this->assertCount(1, $result['vendors'][0]['risks']);
        $this->assertStringContainsString('payment days', $result['vendors'][0]['risks'][0]['message']);
    }
}

<?php

declare(strict_types=1);

namespace Nexus\QuoteIngestion\Tests\Unit;

use Nexus\QuotationIntelligence\Contracts\DecisionTrailWriterInterface;
use Nexus\QuotationIntelligence\Contracts\QuotationIntelligenceCoordinatorInterface;
use Nexus\QuoteIngestion\Contracts\NormalizationSourceLinePersistInterface;
use Nexus\QuoteIngestion\Contracts\NormalizationSourceLineQueryInterface;
use Nexus\QuoteIngestion\Contracts\QuoteSubmissionInterface;
use Nexus\QuoteIngestion\Contracts\QuoteSubmissionPersistInterface;
use Nexus\QuoteIngestion\Contracts\QuoteSubmissionQueryInterface;
use Nexus\QuoteIngestion\QuoteIngestionOrchestrator;
use Nexus\Tenant\Contracts\TenantContextInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class QuoteIngestionOrchestratorTest extends TestCase
{
    public function testProcessLogsAndReturnsWhenSubmissionIsMissing(): void
    {
        $tenantId = 'tenant-1';
        $submissionId = 'submission-1';

        $coordinator = $this->createMock(QuotationIntelligenceCoordinatorInterface::class);
        $decisionTrailWriter = $this->createMock(DecisionTrailWriterInterface::class);
        $tenantContext = $this->createMock(TenantContextInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $submissionQuery = $this->createMock(QuoteSubmissionQueryInterface::class);
        $submissionPersist = $this->createMock(QuoteSubmissionPersistInterface::class);
        $sourceLineQuery = $this->createMock(NormalizationSourceLineQueryInterface::class);
        $sourceLinePersist = $this->createMock(NormalizationSourceLinePersistInterface::class);

        $submissionQuery->expects(self::once())
            ->method('find')
            ->with($tenantId, $submissionId)
            ->willReturn(null);

        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Quote submission not found',
                self::callback(static fn (array $context): bool => $context['tenant_id'] === $tenantId
                    && $context['quote_submission_id'] === $submissionId)
            );

        $tenantContext->expects(self::never())->method('setTenant');
        $tenantContext->expects(self::never())->method('clearTenant');
        $submissionPersist->expects(self::never())->method('markExtracting');
        $coordinator->expects(self::never())->method('processQuote');

        $orchestrator = new QuoteIngestionOrchestrator(
            $coordinator,
            $decisionTrailWriter,
            $tenantContext,
            $logger,
            $submissionQuery,
            $submissionPersist,
            $sourceLineQuery,
            $sourceLinePersist,
        );

        $orchestrator->process($submissionId, $tenantId);
    }

    public function testProcessSkipsMalformedLinesAndCompletesWithPersistedCount(): void
    {
        $tenantId = 'tenant-1';
        $submissionId = 'submission-1';
        $submission = $this->createSubmission($submissionId, $tenantId, 'rfq-123', 'Vendor A');

        $coordinator = $this->createMock(QuotationIntelligenceCoordinatorInterface::class);
        $decisionTrailWriter = $this->createMock(DecisionTrailWriterInterface::class);
        $tenantContext = $this->createMock(TenantContextInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $submissionQuery = $this->createMock(QuoteSubmissionQueryInterface::class);
        $submissionPersist = $this->createMock(QuoteSubmissionPersistInterface::class);
        $sourceLineQuery = $this->createMock(NormalizationSourceLineQueryInterface::class);
        $sourceLinePersist = $this->createMock(NormalizationSourceLinePersistInterface::class);

        $submissionQuery->method('find')->willReturn($submission);

        $lines = [
            'invalid-line-entry',
            [
                'rfq_line_id' => '',
                'vendor_description' => 'Missing key line',
                'ai_confidence' => 94,
            ],
            [
                'rfq_line_id' => 'rfq-line-1',
                'vendor_description' => 'Widget A',
                'quoted_quantity' => '2',
                'quoted_unit' => 'EA',
                'quoted_unit_price' => '10.5',
                'ai_confidence' => 90,
                'taxonomy_code' => 'TAX-100',
                'metadata' => ['mapping_version' => 'v1'],
            ],
        ];

        $coordinator->expects(self::once())
            ->method('processQuote')
            ->with($tenantId, $submissionId)
            ->willReturn(['lines' => $lines, 'risks' => []]);

        $tenantContext->expects(self::once())->method('setTenant')->with($tenantId);
        $tenantContext->expects(self::once())->method('clearTenant');

        $submissionPersist->expects(self::once())->method('markExtracting')->with($submission);
        $submissionPersist->expects(self::once())->method('markNormalizing')->with($submission);
        $submissionPersist->expects(self::once())
            ->method('markCompleted')
            ->with($submission, 'ready', 90.0, 1);
        $submissionPersist->expects(self::never())->method('markFailed');

        $sourceLineQuery->expects(self::once())
            ->method('findExisting')
            ->with($tenantId, $submissionId, 'rfq-line-1')
            ->willReturn(null);

        $sourceLinePersist->expects(self::once())
            ->method('upsert')
            ->with(
                $tenantId,
                $submissionId,
                'rfq-line-1',
                self::callback(static fn (array $payload): bool => $payload['source_description'] === 'Widget A'
                    && $payload['source_quantity'] === 2.0
                    && $payload['source_uom'] === 'EA'
                    && $payload['source_unit_price'] === 10.5
                    && $payload['ai_confidence'] === 90.0
                    && $payload['taxonomy_code'] === 'TAX-100'
                    && $payload['mapping_version'] === 'v1'
                    && $payload['sort_order'] === 0)
            );

        $decisionTrailWriter->expects(self::once())
            ->method('write')
            ->with(
                $tenantId,
                'rfq-123',
                self::callback(static fn (array $entries): bool => count($entries) === 1
                    && $entries[0]['event_type'] === 'auto_map'
                    && $entries[0]['payload']['rfq_line_item_id'] === 'rfq-line-1')
            )
            ->willReturn([]);

        $logger->expects(self::exactly(2))->method('warning');

        $orchestrator = new QuoteIngestionOrchestrator(
            $coordinator,
            $decisionTrailWriter,
            $tenantContext,
            $logger,
            $submissionQuery,
            $submissionPersist,
            $sourceLineQuery,
            $sourceLinePersist,
        );

        $orchestrator->process($submissionId, $tenantId);
    }

    public function testProcessMarksFailedWhenCoordinatorThrows(): void
    {
        $tenantId = 'tenant-1';
        $submissionId = 'submission-1';
        $submission = $this->createSubmission($submissionId, $tenantId, 'rfq-123', 'Vendor A');

        $coordinator = $this->createMock(QuotationIntelligenceCoordinatorInterface::class);
        $decisionTrailWriter = $this->createMock(DecisionTrailWriterInterface::class);
        $tenantContext = $this->createMock(TenantContextInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $submissionQuery = $this->createMock(QuoteSubmissionQueryInterface::class);
        $submissionPersist = $this->createMock(QuoteSubmissionPersistInterface::class);
        $sourceLineQuery = $this->createMock(NormalizationSourceLineQueryInterface::class);
        $sourceLinePersist = $this->createMock(NormalizationSourceLinePersistInterface::class);

        $submissionQuery->method('find')->willReturn($submission);
        $coordinator->expects(self::once())
            ->method('processQuote')
            ->willThrowException(new \RuntimeException('upstream failed'));

        $tenantContext->expects(self::once())->method('setTenant')->with($tenantId);
        $tenantContext->expects(self::once())->method('clearTenant');

        $submissionPersist->expects(self::once())->method('markExtracting')->with($submission);
        $submissionPersist->expects(self::never())->method('markNormalizing');
        $submissionPersist->expects(self::never())->method('markCompleted');
        $submissionPersist->expects(self::once())
            ->method('markFailed')
            ->with($submission, 'INTELLIGENCE_FAILED', 'upstream failed');

        $orchestrator = new QuoteIngestionOrchestrator(
            $coordinator,
            $decisionTrailWriter,
            $tenantContext,
            $logger,
            $submissionQuery,
            $submissionPersist,
            $sourceLineQuery,
            $sourceLinePersist,
        );

        $orchestrator->process($submissionId, $tenantId);
    }

    public function testProcessUsesNeedsReviewWhenNoFiniteConfidenceValuesExist(): void
    {
        $tenantId = 'tenant-1';
        $submissionId = 'submission-1';
        $submission = $this->createSubmission($submissionId, $tenantId, 'rfq-123', 'Vendor A');

        $coordinator = $this->createMock(QuotationIntelligenceCoordinatorInterface::class);
        $decisionTrailWriter = $this->createMock(DecisionTrailWriterInterface::class);
        $tenantContext = $this->createMock(TenantContextInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $submissionQuery = $this->createMock(QuoteSubmissionQueryInterface::class);
        $submissionPersist = $this->createMock(QuoteSubmissionPersistInterface::class);
        $sourceLineQuery = $this->createMock(NormalizationSourceLineQueryInterface::class);
        $sourceLinePersist = $this->createMock(NormalizationSourceLinePersistInterface::class);

        $submissionQuery->method('find')->willReturn($submission);

        $lines = [
            [
                'rfq_line_id' => 'rfq-line-1',
                'vendor_description' => 'Widget A',
                'quoted_quantity' => 1,
                'quoted_unit' => 'EA',
                'quoted_unit_price' => 5,
                'ai_confidence' => 'unknown',
            ],
            [
                'rfq_line_id' => 'rfq-line-2',
                'vendor_description' => 'Widget B',
                'quoted_quantity' => 1,
                'quoted_unit' => 'EA',
                'quoted_unit_price' => 7,
                'ai_confidence' => NAN,
            ],
        ];

        $coordinator->expects(self::once())
            ->method('processQuote')
            ->with($tenantId, $submissionId)
            ->willReturn(['lines' => $lines, 'risks' => []]);

        $sourceLineQuery->method('findExisting')->willReturn(null);
        $sourceLinePersist->expects(self::exactly(2))->method('upsert');
        $decisionTrailWriter->expects(self::never())->method('write');

        $submissionPersist->expects(self::once())->method('markExtracting')->with($submission);
        $submissionPersist->expects(self::once())->method('markNormalizing')->with($submission);
        $submissionPersist->expects(self::once())
            ->method('markCompleted')
            ->with($submission, 'needs_review', 0.0, 2);

        $tenantContext->expects(self::once())->method('setTenant')->with($tenantId);
        $tenantContext->expects(self::once())->method('clearTenant');

        $orchestrator = new QuoteIngestionOrchestrator(
            $coordinator,
            $decisionTrailWriter,
            $tenantContext,
            $logger,
            $submissionQuery,
            $submissionPersist,
            $sourceLineQuery,
            $sourceLinePersist,
        );

        $orchestrator->process($submissionId, $tenantId);
    }

    public function testProcessHandlesMissingMetadataWithoutFailing(): void
    {
        $tenantId = 'tenant-1';
        $submissionId = 'submission-1';
        $submission = $this->createSubmission($submissionId, $tenantId, 'rfq-123', 'Vendor A');

        $coordinator = $this->createMock(QuotationIntelligenceCoordinatorInterface::class);
        $decisionTrailWriter = $this->createMock(DecisionTrailWriterInterface::class);
        $tenantContext = $this->createMock(TenantContextInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $submissionQuery = $this->createMock(QuoteSubmissionQueryInterface::class);
        $submissionPersist = $this->createMock(QuoteSubmissionPersistInterface::class);
        $sourceLineQuery = $this->createMock(NormalizationSourceLineQueryInterface::class);
        $sourceLinePersist = $this->createMock(NormalizationSourceLinePersistInterface::class);

        $submissionQuery->method('find')->willReturn($submission);
        $sourceLineQuery->method('findExisting')->willReturn(null);

        $coordinator->expects(self::once())
            ->method('processQuote')
            ->with($tenantId, $submissionId)
            ->willReturn([
                'lines' => [
                    [
                        'rfq_line_id' => 'rfq-line-1',
                        'vendor_description' => 'Widget A',
                        'quoted_quantity' => 1,
                        'quoted_unit' => 'EA',
                        'quoted_unit_price' => 12,
                        'ai_confidence' => 85,
                    ],
                ],
                'risks' => [],
            ]);

        $sourceLinePersist->expects(self::once())
            ->method('upsert')
            ->with(
                $tenantId,
                $submissionId,
                'rfq-line-1',
                self::callback(static fn (array $payload): bool => $payload['mapping_version'] === '')
            );

        $decisionTrailWriter->expects(self::once())
            ->method('write')
            ->with(
                $tenantId,
                'rfq-123',
                self::callback(static fn (array $entries): bool => $entries[0]['payload']['mapping_version'] === '')
            )
            ->willReturn([]);

        $submissionPersist->expects(self::once())->method('markExtracting')->with($submission);
        $submissionPersist->expects(self::once())->method('markNormalizing')->with($submission);
        $submissionPersist->expects(self::once())
            ->method('markCompleted')
            ->with($submission, 'ready', 85.0, 1);
        $submissionPersist->expects(self::never())->method('markFailed');

        $tenantContext->expects(self::once())->method('setTenant')->with($tenantId);
        $tenantContext->expects(self::once())->method('clearTenant');

        $orchestrator = new QuoteIngestionOrchestrator(
            $coordinator,
            $decisionTrailWriter,
            $tenantContext,
            $logger,
            $submissionQuery,
            $submissionPersist,
            $sourceLineQuery,
            $sourceLinePersist,
        );

        $orchestrator->process($submissionId, $tenantId);
    }

    private function createSubmission(
        string $id,
        string $tenantId,
        string $rfqId,
        string $vendorName
    ): QuoteSubmissionInterface {
        return new readonly class ($id, $tenantId, $rfqId, $vendorName) implements QuoteSubmissionInterface {
            public function __construct(
                private string $id,
                private string $tenantId,
                private string $rfqId,
                private string $vendorName
            ) {}

            public function getId(): string
            {
                return $this->id;
            }

            public function getTenantId(): string
            {
                return $this->tenantId;
            }

            public function getRfqId(): string
            {
                return $this->rfqId;
            }

            public function getVendorName(): string
            {
                return $this->vendorName;
            }
        };
    }
}

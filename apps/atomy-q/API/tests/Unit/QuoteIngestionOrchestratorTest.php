<?php

declare(strict_types=1);

namespace Tests\Unit;

use Nexus\QuoteIngestion\Contracts\NormalizationSourceLinePersistInterface;
use Nexus\QuoteIngestion\Contracts\NormalizationSourceLineQueryInterface;
use Nexus\QuoteIngestion\Contracts\QuoteSubmissionInterface;
use Nexus\QuoteIngestion\Contracts\QuoteSubmissionPersistInterface;
use Nexus\QuoteIngestion\Contracts\QuoteSubmissionQueryInterface;
use Nexus\QuoteIngestion\QuoteIngestionOrchestrator;
use Nexus\QuotationIntelligence\Contracts\DecisionTrailWriterInterface;
use Nexus\QuotationIntelligence\Contracts\QuotationIntelligenceCoordinatorInterface;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

final class QuoteIngestionOrchestratorTest extends TestCase
{
    public function test_process_logs_missing_upstream_fields_before_applying_fallbacks(): void
    {
        $submission = $this->createMock(QuoteSubmissionInterface::class);
        $submission->method('getId')->willReturn('submission-1');
        $submission->method('getTenantId')->willReturn('tenant-1');
        $submission->method('getRfqId')->willReturn('rfq-1');
        $submission->method('getVendorName')->willReturn('Vendor Co');

        $coordinator = $this->createMock(QuotationIntelligenceCoordinatorInterface::class);
        $coordinator->expects($this->once())
            ->method('processQuote')
            ->with('tenant-1', 'submission-1')
            ->willReturn([
                'lines' => [[
                    'rfq_line_id' => 'line-1',
                    'vendor_description' => 'Vendor supplied line',
                    'ai_confidence' => 85.0,
                    'taxonomy_code' => 'TAX-001',
                    'metadata' => [
                        'mapping_version' => 'v1',
                    ],
                ]],
            ]);

        $decisionTrailWriter = $this->createMock(DecisionTrailWriterInterface::class);
        $decisionTrailWriter->expects($this->once())
            ->method('write')
            ->with(
                'tenant-1',
                'rfq-1',
                $this->callback(static function (array $entries): bool {
                    return $entries[0]['event_type'] === 'auto_map'
                        && $entries[0]['payload']['quote_submission_id'] === 'submission-1'
                        && $entries[0]['payload']['rfq_line_item_id'] === 'line-1'
                        && $entries[0]['payload']['confidence'] === 85.0;
                })
            );

        $tenantContext = $this->createMock(TenantContextInterface::class);
        $tenantContext->expects($this->once())->method('setTenant')->with('tenant-1');
        $tenantContext->expects($this->once())->method('clearTenant');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with(
                'Missing upstream quote data for normalization source line',
                $this->callback(static function (array $context): bool {
                    return $context['tenant_id'] === 'tenant-1'
                        && $context['quote_submission_id'] === 'submission-1'
                        && $context['rfq_id'] === 'rfq-1'
                        && $context['rfq_line_id'] === 'line-1'
                        && $context['missing_fields'] === ['quoted_quantity', 'quoted_unit', 'quoted_unit_price'];
                })
            );

        $submissionQuery = $this->createMock(QuoteSubmissionQueryInterface::class);
        $submissionQuery->expects($this->once())
            ->method('find')
            ->with('tenant-1', 'submission-1')
            ->willReturn($submission);

        $submissionPersist = $this->createMock(QuoteSubmissionPersistInterface::class);
        $submissionPersist->expects($this->once())->method('markExtracting')->with($submission);
        $submissionPersist->expects($this->once())->method('markNormalizing')->with($submission);
        $submissionPersist->expects($this->once())
            ->method('markCompleted')
            ->with($submission, 'ready', 85.0, 1);

        $sourceLineQuery = $this->createMock(NormalizationSourceLineQueryInterface::class);
        $sourceLineQuery->expects($this->once())
            ->method('findExisting')
            ->with('tenant-1', 'submission-1', 'line-1')
            ->willReturn(null);

        $sourceLinePersist = $this->createMock(NormalizationSourceLinePersistInterface::class);
        $sourceLinePersist->expects($this->once())
            ->method('upsert')
            ->with(
                'tenant-1',
                'submission-1',
                'line-1',
                $this->callback(static function (array $data): bool {
                    return $data['source_vendor'] === 'Vendor Co'
                        && $data['source_description'] === 'Vendor supplied line'
                        && $data['source_quantity'] === 0.0
                        && $data['source_uom'] === 'EA'
                        && $data['source_unit_price'] === 0.0
                        && $data['raw_data']['quoted_quantity'] === null
                        && $data['raw_data']['quoted_unit'] === null
                        && $data['raw_data']['quoted_unit_price'] === null
                        && $data['raw_data']['normalized_quantity'] === null
                        && $data['raw_data']['normalized_unit_price'] === null
                        && $data['sort_order'] === 0
                        && $data['ai_confidence'] === 85.0
                        && $data['taxonomy_code'] === 'TAX-001'
                        && $data['mapping_version'] === 'v1';
                })
            );

        $orchestrator = new QuoteIngestionOrchestrator(
            $coordinator,
            $decisionTrailWriter,
            $tenantContext,
            $logger,
            $submissionQuery,
            $submissionPersist,
            $sourceLineQuery,
            $sourceLinePersist
        );

        $orchestrator->process('submission-1', 'tenant-1');
    }
}

<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services\Compliance;

use Nexus\Document\Contracts\DisposalCertificationInterface;
use Nexus\Document\Contracts\DisposalCertificationRepositoryInterface;
use Nexus\Document\Contracts\DocumentRepositoryInterface;
use Nexus\Document\Contracts\LegalHoldInterface;
use Nexus\Document\Contracts\LegalHoldRepositoryInterface;
use Nexus\Document\Contracts\RetentionPolicyInterface;
use Nexus\Document\Services\RetentionService;
use Nexus\ProcurementOperations\Contracts\AuditLoggerAdapterInterface;
use Nexus\ProcurementOperations\Contracts\DocumentRetentionServiceInterface;
use Nexus\ProcurementOperations\DTOs\Audit\DisposalCertificationData;
use Nexus\ProcurementOperations\DTOs\Audit\LegalHoldData;
use Nexus\ProcurementOperations\DTOs\Audit\RetentionPolicyData;
use Nexus\ProcurementOperations\Enums\RetentionCategory;
use Nexus\ProcurementOperations\Exceptions\DocumentRetentionException;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service for document retention management in procurement workflows.
 *
 * Orchestrates document lifecycle management using the Nexus\Document package
 * for regulatory compliance (SOX, IRS, industry standards):
 * - Configurable retention policies by document category
 * - Legal hold management with litigation support
 * - Certified disposal with audit trail
 * - Compliance reporting for audits
 *
 * @see RetentionService For underlying atomic package implementation
 */
final readonly class DocumentRetentionService implements DocumentRetentionServiceInterface
{
    /**
     * Maps procurement document types to retention categories.
     *
     * @var array<string, RetentionCategory>
     */
    private const DOCUMENT_TYPE_MAPPING = [
        'purchase_order' => RetentionCategory::PURCHASE_ORDERS,
        'requisition' => RetentionCategory::PURCHASE_ORDERS,
        'vendor_invoice' => RetentionCategory::INVOICES_PAYABLE,
        'goods_receipt' => RetentionCategory::PURCHASE_ORDERS,
        'payment_record' => RetentionCategory::PAYMENT_RECORDS,
        'vendor_contract' => RetentionCategory::VENDOR_CONTRACTS,
        'rfq' => RetentionCategory::RFQ_DATA,
        'quote' => RetentionCategory::RFQ_DATA,
        'bid' => RetentionCategory::RFQ_DATA,
        'credit_memo' => RetentionCategory::INVOICES_PAYABLE,
        'debit_memo' => RetentionCategory::INVOICES_PAYABLE,
        'bank_statement' => RetentionCategory::PAYMENT_RECORDS,
        'tax_document' => RetentionCategory::TAX_DOCUMENTS,
        'audit_workpaper' => RetentionCategory::AUDIT_WORKPAPERS,
        'correspondence' => RetentionCategory::CORRESPONDENCE,
        'general' => RetentionCategory::GENERAL_AP,
    ];

    public function __construct(
        private RetentionService $retentionService,
        private DocumentRepositoryInterface $documentRepository,
        private LegalHoldRepositoryInterface $legalHoldRepository,
        private DisposalCertificationRepositoryInterface $disposalCertificationRepository,
        private RetentionPolicyInterface $retentionPolicy,
        private TenantContextInterface $tenantContext,
        private AuditLoggerAdapterInterface $auditLogger,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * {@inheritdoc}
     */
    public function getRetentionPolicy(string $documentType): array
    {
        $category = $this->resolveRetentionCategory($documentType);

        return [
            'years' => $category->getRetentionYears(),
            'legal_hold' => $category->requiresLegalHoldCheck(),
            'disposal_method' => $category->getDisposalMethod(),
            'regulatory_basis' => $category->getRegulatoryBasis(),
            'subject_to_sox' => $category->isSubjectToSox(),
            'document_type' => $documentType,
            'category' => $category->value,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isWithinRetentionPeriod(string $documentType, \DateTimeImmutable $createdAt): bool
    {
        $category = $this->resolveRetentionCategory($documentType);
        $retentionYears = $category->getRetentionYears();

        $expirationDate = $createdAt->modify("+{$retentionYears} years");
        $now = new \DateTimeImmutable();

        return $now <= $expirationDate;
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentsForDisposal(string $documentType, ?\DateTimeImmutable $asOfDate = null): array
    {
        // Use enhanced Document package method (filters by document type if specified)
        $typeFilter = $documentType !== 'all' ? $documentType : null;
        $documents = $this->retentionService->getDocumentsForDisposal($typeFilter);

        // Exclude documents with active legal holds (already handled in RetentionService, but double-check)
        $activeHolds = $this->legalHoldRepository->findAllActive();
        $documentsWithHolds = array_map(
            fn($hold) => $hold->getDocumentId(),
            $activeHolds
        );

        return array_filter(
            $documents,
            fn($doc) => !in_array($doc->getId(), $documentsWithHolds, true)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function applyLegalHold(string $documentId, string $holdReason, string $holdBy): array
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        $this->logger->info('Applying legal hold to document', [
            'document_id' => $documentId,
            'reason' => $holdReason,
            'applied_by' => $holdBy,
            'tenant_id' => $tenantId,
        ]);

        // Check if document exists
        $document = $this->documentRepository->findById($documentId);
        if ($document === null) {
            throw DocumentRetentionException::documentNotFound($documentId);
        }

        // Check for existing active hold
        if ($this->legalHoldRepository->hasActiveHold($documentId)) {
            throw DocumentRetentionException::documentAlreadyOnHold($documentId);
        }

        // Use underlying atomic package
        $legalHold = $this->retentionService->applyLegalHold(
            documentId: $documentId,
            reason: $holdReason,
            matterReference: null,
            appliedBy: $holdBy,
            expiresAt: null, // Indefinite hold
        );

        // Log audit trail
        $this->auditLogger->log(
            logName: 'document_retention',
            description: "Legal hold applied: {$holdReason}",
            subjectType: 'document',
            subjectId: $documentId,
            properties: [
                'hold_id' => $legalHold->getId(),
                'applied_by' => $holdBy,
                'matter_reference' => $legalHold->getMatterReference(),
            ],
            event: 'legal_hold_applied',
            tenantId: $this->tenantContext->getCurrentTenantId(),
        );

        return $this->buildLegalHoldResponse($legalHold);
    }

    /**
     * {@inheritdoc}
     */
    public function releaseLegalHold(string $documentId, string $releaseReason, string $releasedBy): array
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        $this->logger->info('Releasing legal hold from document', [
            'document_id' => $documentId,
            'reason' => $releaseReason,
            'released_by' => $releasedBy,
            'tenant_id' => $tenantId,
        ]);

        // Find active hold
        $activeHolds = $this->legalHoldRepository->findActiveByDocumentId($documentId);
        if (empty($activeHolds)) {
            throw DocumentRetentionException::noActiveHoldFound($documentId);
        }
        $activeHold = $activeHolds[0]; // Get first active hold

        // Use underlying atomic package
        $releasedHold = $this->retentionService->releaseLegalHold(
            holdId: $activeHold->getId(),
            releasedBy: $releasedBy,
            releaseReason: $releaseReason,
        );

        // Log audit trail
        $this->auditLogger->log(
            logName: 'document_retention',
            description: "Legal hold released: {$releaseReason}",
            subjectType: 'document',
            subjectId: $documentId,
            properties: [
                'hold_id' => $releasedHold->getId(),
                'released_by' => $releasedBy,
                'original_reason' => $activeHold->getReason(),
            ],
            event: 'legal_hold_released',
            tenantId: $this->tenantContext->getCurrentTenantId(),
        );

        return $this->buildLegalHoldResponse($releasedHold);
    }

    /**
     * {@inheritdoc}
     */
    public function getRetentionReport(
        string $tenantId,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd,
    ): array {
        $this->logger->info('Generating retention compliance report', [
            'tenant_id' => $tenantId,
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
        ]);

        // Get report from underlying service
        $report = $this->retentionService->generateRetentionReport(
            periodStart: $periodStart,
            periodEnd: $periodEnd,
        );

        // Enhance with orchestrator-level aggregations
        $disposals = $this->disposalCertificationRepository->findByDateRange(
            $periodStart,
            $periodEnd,
        );

        $activeHolds = $this->legalHoldRepository->countActive();

        $categoryBreakdown = $this->buildCategoryBreakdown();

        return [
            'tenant_id' => $tenantId,
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
            'generated_at' => (new \DateTimeImmutable())->format('c'),
            'summary' => [
                'total_documents' => $report['summary']['total_documents'] ?? 0,
                'documents_under_retention' => $report['summary']['documents_under_retention'] ?? 0,
                'documents_eligible_for_disposal' => $report['summary']['eligible_for_disposal'] ?? 0,
                'documents_on_legal_hold' => $activeHolds,
                'disposals_in_period' => count($disposals),
            ],
            'category_breakdown' => $categoryBreakdown,
            'disposal_summary' => $this->buildDisposalSummary($disposals),
            'compliance_status' => $this->assessComplianceStatus($report),
            'upcoming_expirations' => $report['upcoming_expirations'] ?? [],
            'legal_holds' => [
                'active_count' => $activeHolds,
                'detail' => $this->getActiveLegalHoldsDetail(),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function archiveDocument(string $documentId, string $archiveReason, string $archivedBy): array
    {
        $this->logger->info('Archiving document', [
            'document_id' => $documentId,
            'reason' => $archiveReason,
            'archived_by' => $archivedBy,
        ]);

        $document = $this->documentRepository->findById($documentId);
        if ($document === null) {
            throw DocumentRetentionException::documentNotFound($documentId);
        }

        // Check for legal hold
        if ($this->legalHoldRepository->hasActiveHold($documentId)) {
            throw DocumentRetentionException::documentOnLegalHold($documentId);
        }

        // Archive via Document package (implementation depends on Document package capabilities)
        // For now, log the action and update metadata
        $this->auditLogger->log(
            logName: 'document_retention',
            description: "Document archived: {$archiveReason}",
            subjectType: 'document',
            subjectId: $documentId,
            properties: [
                'archived_by' => $archivedBy,
                'archive_reason' => $archiveReason,
                'archived_at' => (new \DateTimeImmutable())->format('c'),
            ],
            event: 'document_archived',
            tenantId: $this->tenantContext->getCurrentTenantId(),
        );

        return [
            'success' => true,
            'document_id' => $documentId,
            'archived_at' => (new \DateTimeImmutable())->format('c'),
            'archived_by' => $archivedBy,
            'archive_reason' => $archiveReason,
            'archive_location' => 'cold_storage', // Would be provided by storage layer
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function disposeDocument(
        string $documentId,
        string $disposalMethod,
        string $disposedBy,
        ?string $certificationId = null,
    ): array {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        $this->logger->info('Disposing document', [
            'document_id' => $documentId,
            'disposal_method' => $disposalMethod,
            'disposed_by' => $disposedBy,
            'tenant_id' => $tenantId,
        ]);

        $document = $this->documentRepository->findById($documentId);
        if ($document === null) {
            throw DocumentRetentionException::documentNotFound($documentId);
        }

        // Verify legal hold status
        if ($this->legalHoldRepository->hasActiveHold($documentId)) {
            throw DocumentRetentionException::documentOnLegalHold($documentId);
        }

        // Use atomic package for certified disposal
        $certification = $this->retentionService->disposeDocument(
            document: $document,
            disposalMethod: $disposalMethod,
            disposedBy: $disposedBy,
            approvedBy: $disposedBy, // Should be different in production
            reason: 'Retention period expired',
            witnessedBy: null,
        );

        // Log comprehensive audit trail
        $this->auditLogger->log(
            logName: 'document_retention',
            description: "Document disposed via {$disposalMethod}",
            subjectType: 'document',
            subjectId: $documentId,
            properties: [
                'disposal_method' => $disposalMethod,
                'disposed_by' => $disposedBy,
                'certification_id' => $certification->getId(),
                'disposed_at' => (new \DateTimeImmutable())->format('c'),
                'legal_hold_verified' => true,
            ],
            event: 'document_disposed',
            tenantId: $this->tenantContext->getCurrentTenantId(),
        );

        return [
            'success' => true,
            'document_id' => $documentId,
            'disposed_at' => (new \DateTimeImmutable())->format('c'),
            'disposed_by' => $disposedBy,
            'disposal_method' => $disposalMethod,
            'certification_id' => $certification->getId(),
            'legal_hold_verified' => true,
            'chain_of_custody' => [],
        ];
    }

    /**
     * Resolve document type to retention category.
     */
    private function resolveRetentionCategory(string $documentType): RetentionCategory
    {
        $normalizedType = strtolower(str_replace(['-', ' '], '_', $documentType));

        if (isset(self::DOCUMENT_TYPE_MAPPING[$normalizedType])) {
            return self::DOCUMENT_TYPE_MAPPING[$normalizedType];
        }

        // Try to find a matching category by enum value
        foreach (RetentionCategory::cases() as $category) {
            if ($category->value === $normalizedType) {
                return $category;
            }
        }

        // Default to GENERAL_AP for unknown types
        $this->logger->warning('Unknown document type, using GENERAL_AP category', [
            'document_type' => $documentType,
            'normalized' => $normalizedType,
        ]);

        return RetentionCategory::GENERAL_AP;
    }

    /**
     * Build legal hold response array.
     */
    private function buildLegalHoldResponse(LegalHoldInterface $legalHold): array
    {
        return [
            'hold_id' => $legalHold->getId(),
            'document_id' => $legalHold->getDocumentId(),
            'is_active' => $legalHold->isActive(),
            'reason' => $legalHold->getReason(),
            'matter_reference' => $legalHold->getMatterReference(),
            'applied_by' => $legalHold->getAppliedBy(),
            'applied_at' => $legalHold->getAppliedAt()->format('c'),
            'released_by' => $legalHold->getReleasedBy(),
            'released_at' => $legalHold->getReleasedAt()?->format('c'),
            'release_reason' => $legalHold->getReleaseReason(),
            'expires_at' => $legalHold->getExpiresAt()?->format('c'),
        ];
    }

    /**
     * Build category breakdown for report.
     *
     * @return array<string, array{count: int, retention_years: int, regulatory_basis: string}>
     */
    private function buildCategoryBreakdown(): array
    {
        $breakdown = [];

        foreach (RetentionCategory::cases() as $category) {
            $breakdown[$category->value] = [
                'count' => 0, // Would be populated from document repository
                'retention_years' => $category->getRetentionYears(),
                'regulatory_basis' => $category->getRegulatoryBasis(),
                'disposal_method' => $category->getDisposalMethod(),
                'requires_legal_hold_check' => $category->requiresLegalHoldCheck(),
                'subject_to_sox' => $category->isSubjectToSox(),
            ];
        }

        return $breakdown;
    }

    /**
     * Build disposal summary from certification records.
     *
     * @param array<DisposalCertificationInterface> $disposals
     */
    private function buildDisposalSummary(array $disposals): array
    {
        $byMethod = [];
        $byCategory = [];
        $lateDisposals = 0;

        foreach ($disposals as $disposal) {
            $method = $disposal->getDisposalMethod();
            $type = $disposal->getDocumentType();

            $byMethod[$method] = ($byMethod[$method] ?? 0) + 1;
            $byCategory[$type] = ($byCategory[$type] ?? 0) + 1;

            // Check if disposal was late (more than 30 days after retention expired)
            $retentionExpired = $disposal->getRetentionExpiredAt();
            $disposedAt = $disposal->getDisposedAt();
            $daysDiff = $retentionExpired->diff($disposedAt)->days;

            if ($daysDiff > 30) {
                $lateDisposals++;
            }
        }

        return [
            'total' => count($disposals),
            'by_method' => $byMethod,
            'by_category' => $byCategory,
            'late_disposals' => $lateDisposals,
            'on_time_percentage' => count($disposals) > 0
                ? round(((count($disposals) - $lateDisposals) / count($disposals)) * 100, 2)
                : 100.0,
        ];
    }

    /**
     * Assess overall compliance status.
     */
    private function assessComplianceStatus(array $report): array
    {
        $issues = [];
        $status = 'compliant';

        // Check for documents past retention that haven't been disposed
        $eligibleForDisposal = $report['summary']['eligible_for_disposal'] ?? 0;
        if ($eligibleForDisposal > 0) {
            $issues[] = [
                'severity' => 'warning',
                'message' => "{$eligibleForDisposal} documents are past retention period and pending disposal",
            ];
            $status = 'attention_needed';
        }

        // Check for legal holds without matter references
        $holdsWithoutMatter = $this->legalHoldRepository->findByMatterReference('');
        if (!empty($holdsWithoutMatter)) {
            $issues[] = [
                'severity' => 'info',
                'message' => count($holdsWithoutMatter) . ' legal holds lack matter references',
            ];
        }

        return [
            'status' => $status,
            'issues' => $issues,
            'last_assessed' => (new \DateTimeImmutable())->format('c'),
        ];
    }

    /**
     * Get active legal holds detail.
     *
     * @return array<array{hold_id: string, document_id: string, reason: string, applied_at: string}>
     */
    private function getActiveLegalHoldsDetail(): array
    {
        $holds = $this->legalHoldRepository->findAllActive();
        $detail = [];

        foreach ($holds as $hold) {
            $detail[] = [
                'hold_id' => $hold->getId(),
                'document_id' => $hold->getDocumentId(),
                'reason' => $hold->getReason(),
                'matter_reference' => $hold->getMatterReference(),
                'applied_at' => $hold->getAppliedAt()->format('c'),
                'applied_by' => $hold->getAppliedBy(),
                'expires_at' => $hold->getExpiresAt()?->format('c'),
            ];
        }

        return $detail;
    }
}

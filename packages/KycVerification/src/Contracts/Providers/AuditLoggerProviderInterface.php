<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Contracts\Providers;

/**
 * Interface for audit logging.
 * 
 * This interface abstracts audit logging operations.
 * The orchestrator layer must implement this using Nexus\AuditLogger.
 */
interface AuditLoggerProviderInterface
{
    /**
     * Log a KYC verification event
     * 
     * @param array<string, mixed> $metadata
     */
    public function logVerificationEvent(
        string $partyId,
        string $action,
        string $description,
        array $metadata = [],
        ?string $performedBy = null
    ): void;

    /**
     * Log a risk assessment event
     * 
     * @param array<string, mixed> $metadata
     */
    public function logRiskAssessment(
        string $partyId,
        string $riskLevel,
        int $riskScore,
        array $metadata = [],
        ?string $assessedBy = null
    ): void;

    /**
     * Log a document verification event
     * 
     * @param array<string, mixed> $metadata
     */
    public function logDocumentVerification(
        string $partyId,
        string $documentId,
        string $documentType,
        string $status,
        array $metadata = [],
        ?string $verifiedBy = null
    ): void;

    /**
     * Log a beneficial ownership event
     * 
     * @param array<string, mixed> $metadata
     */
    public function logBeneficialOwnershipChange(
        string $partyId,
        string $action,
        string $description,
        array $metadata = [],
        ?string $performedBy = null
    ): void;

    /**
     * Log a review event
     * 
     * @param array<string, mixed> $metadata
     */
    public function logReview(
        string $partyId,
        string $reviewType,
        string $outcome,
        array $metadata = [],
        ?string $reviewedBy = null
    ): void;

    /**
     * Log an alert or exception
     * 
     * @param array<string, mixed> $metadata
     */
    public function logAlert(
        string $partyId,
        string $alertType,
        string $severity,
        string $description,
        array $metadata = []
    ): void;
}

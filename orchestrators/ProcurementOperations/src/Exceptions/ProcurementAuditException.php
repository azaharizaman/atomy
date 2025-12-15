<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Exceptions;

/**
 * Exception for procurement audit operations.
 *
 * Handles errors in SOX 404 compliance, control testing,
 * segregation of duties, and audit finding management.
 */
final class ProcurementAuditException extends ProcurementOperationsException
{
    /**
     * Audit finding not found.
     */
    public static function findingNotFound(string $findingId): self
    {
        return new self(
            message: "Audit finding '{$findingId}' not found.",
            code: 7001,
        );
    }

    /**
     * Finding already resolved.
     */
    public static function findingAlreadyResolved(string $findingId): self
    {
        return new self(
            message: "Audit finding '{$findingId}' has already been resolved.",
            code: 7002,
        );
    }

    /**
     * Invalid audit period format.
     */
    public static function invalidAuditPeriod(string $period): self
    {
        return new self(
            message: "Invalid audit period format: '{$period}'. Expected format: 'YYYY-QN' (e.g., '2024-Q4').",
            code: 7003,
        );
    }

    /**
     * Invalid control area.
     */
    public static function invalidControlArea(string $controlArea): self
    {
        return new self(
            message: "Invalid control area: '{$controlArea}'.",
            code: 7004,
        );
    }

    /**
     * Control test failed with material weakness.
     */
    public static function materialWeaknessDetected(string $controlArea): self
    {
        return new self(
            message: "Material weakness detected in control area: '{$controlArea}'. Board notification required.",
            code: 7005,
        );
    }

    /**
     * SOD violation detected.
     */
    public static function segregationOfDutiesViolation(
        string $userId,
        string $duty1,
        string $duty2,
    ): self {
        return new self(
            message: "Segregation of duties violation detected for user '{$userId}': incompatible duties '{$duty1}' and '{$duty2}'.",
            code: 7006,
        );
    }

    /**
     * Approval authority exceeded.
     */
    public static function approvalAuthorityExceeded(
        string $approverId,
        float $amount,
        float $limit,
    ): self {
        $amountFormatted = number_format($amount, 2);
        $limitFormatted = number_format($limit, 2);

        return new self(
            message: "Approver '{$approverId}' exceeded authority limit. Amount: {$amountFormatted}, Limit: {$limitFormatted}.",
            code: 7007,
        );
    }

    /**
     * Evidence generation failed.
     */
    public static function evidenceGenerationFailed(string $reason): self
    {
        return new self(
            message: "Failed to generate SOX 404 evidence package: {$reason}.",
            code: 7008,
        );
    }

    /**
     * Three-way match audit trail not available.
     */
    public static function threeWayMatchTrailUnavailable(string $documentId): self
    {
        return new self(
            message: "Three-way match audit trail not available for document: '{$documentId}'.",
            code: 7009,
        );
    }

    /**
     * Remediation deadline exceeded.
     */
    public static function remediationDeadlineExceeded(
        string $findingId,
        \DateTimeImmutable $deadline,
    ): self {
        return new self(
            message: "Remediation deadline exceeded for finding '{$findingId}'. Deadline was: {$deadline->format('Y-m-d')}.",
            code: 7010,
        );
    }
}

<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Exceptions;

use Nexus\AmlCompliance\Enums\SarStatus;

/**
 * Exception thrown when SAR generation or workflow operations fail.
 *
 * Provides factory methods for common SAR-related failure scenarios.
 */
final class SarGenerationFailedException extends AmlException
{
    /**
     * Create exception for missing required data.
     *
     * @param string $sarId SAR identifier
     * @param array<string> $missingFields List of missing required fields
     */
    public static function missingRequiredData(string $sarId, array $missingFields): self
    {
        return new self(
            message: sprintf(
                'SAR %s is missing required data: %s',
                $sarId,
                implode(', ', $missingFields)
            ),
            code: 4001,
            context: [
                'sar_id' => $sarId,
                'missing_fields' => $missingFields,
            ]
        );
    }

    /**
     * Create exception for invalid status transition.
     *
     * @param string $sarId SAR identifier
     * @param string $fromStatus Current status
     * @param string $toStatus Attempted target status
     */
    public static function invalidTransition(string $sarId, string $fromStatus, string $toStatus): self
    {
        return new self(
            message: sprintf(
                'Cannot transition SAR %s from %s to %s',
                $sarId,
                $fromStatus,
                $toStatus
            ),
            code: 4002,
            context: [
                'sar_id' => $sarId,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
            ]
        );
    }

    /**
     * Create exception when filing deadline has been exceeded.
     *
     * @param string $sarId SAR identifier
     * @param \DateTimeImmutable $deadline Original deadline
     * @param \DateTimeImmutable $now Current time
     */
    public static function filingDeadlineExceeded(
        string $sarId,
        \DateTimeImmutable $deadline,
        \DateTimeImmutable $now
    ): self {
        $daysLate = $deadline->diff($now)->days;

        return new self(
            message: sprintf(
                'Filing deadline for SAR %s exceeded by %d days (deadline: %s)',
                $sarId,
                $daysLate,
                $deadline->format('Y-m-d')
            ),
            code: 4003,
            context: [
                'sar_id' => $sarId,
                'deadline' => $deadline->format('Y-m-d H:i:s'),
                'current_time' => $now->format('Y-m-d H:i:s'),
                'days_late' => $daysLate,
            ]
        );
    }

    /**
     * Create exception when SAR is not found.
     *
     * @param string $sarId SAR identifier
     */
    public static function notFound(string $sarId): self
    {
        return new self(
            message: sprintf('SAR %s not found', $sarId),
            code: 4004,
            context: ['sar_id' => $sarId]
        );
    }

    /**
     * Create exception when SAR has already been submitted.
     *
     * @param string $sarId SAR identifier
     * @param \DateTimeImmutable $submittedAt Submission timestamp
     */
    public static function alreadySubmitted(string $sarId, \DateTimeImmutable $submittedAt): self
    {
        return new self(
            message: sprintf(
                'SAR %s was already submitted on %s',
                $sarId,
                $submittedAt->format('Y-m-d H:i:s')
            ),
            code: 4005,
            context: [
                'sar_id' => $sarId,
                'submitted_at' => $submittedAt->format('Y-m-d H:i:s'),
            ]
        );
    }

    /**
     * Create exception for invalid narrative content.
     *
     * @param string $sarId SAR identifier
     * @param string $reason Reason for invalidity
     */
    public static function invalidNarrative(string $sarId, string $reason): self
    {
        return new self(
            message: sprintf('SAR %s has invalid narrative: %s', $sarId, $reason),
            code: 4006,
            context: [
                'sar_id' => $sarId,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Create exception for insufficient evidence.
     *
     * @param array<string, mixed> $context Additional context about the insufficiency
     */
    public static function insufficientEvidence(array $context = []): self
    {
        return new self(
            message: 'Insufficient evidence to generate SAR',
            code: 4007,
            context: array_merge(['reason' => 'insufficient_evidence'], $context)
        );
    }

    /**
     * Create exception when approval is required but not obtained.
     *
     * @param string $sarId SAR identifier
     * @param string $requiredApprover Required approver role or ID
     */
    public static function approvalRequired(string $sarId, string $requiredApprover): self
    {
        return new self(
            message: sprintf(
                'SAR %s requires approval from %s before proceeding',
                $sarId,
                $requiredApprover
            ),
            code: 4008,
            context: [
                'sar_id' => $sarId,
                'required_approver' => $requiredApprover,
            ]
        );
    }

    /**
     * Create exception for filing service errors.
     *
     * @param string $sarId SAR identifier
     * @param string $serviceError Error from filing service
     */
    public static function filingServiceError(string $sarId, string $serviceError): self
    {
        return new self(
            message: sprintf(
                'Filing service error for SAR %s: %s',
                $sarId,
                $serviceError
            ),
            code: 4009,
            context: [
                'sar_id' => $sarId,
                'service_error' => $serviceError,
            ]
        );
    }

    /**
     * Create exception for duplicate SAR attempts.
     *
     * @param string $partyId Party identifier
     * @param string $existingSarId Existing SAR identifier
     */
    public static function duplicateSar(string $partyId, string $existingSarId): self
    {
        return new self(
            message: sprintf(
                'Duplicate SAR attempt for party %s - existing SAR: %s',
                $partyId,
                $existingSarId
            ),
            code: 4010,
            context: [
                'party_id' => $partyId,
                'existing_sar_id' => $existingSarId,
            ]
        );
    }
}

<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\SOX\SOXControlValidationRequest;
use Nexus\ProcurementOperations\DTOs\SOX\SOXControlValidationResponse;
use Nexus\ProcurementOperations\DTOs\SOX\SOXControlValidationResult;
use Nexus\ProcurementOperations\DTOs\SOX\SOXOverrideRequest;
use Nexus\ProcurementOperations\DTOs\SOX\SOXOverrideResult;
use Nexus\ProcurementOperations\Enums\SOXControlPoint;

/**
 * Contract for SOX control validation service.
 *
 * Validates SOX compliance controls at each step of the P2P process.
 * This service is the central point for all SOX 404 compliance checks.
 */
interface SOXControlServiceInterface
{
    /**
     * Validate all applicable SOX controls for a transaction.
     *
     * @throws \Nexus\ProcurementOperations\Exceptions\SOXValidationException
     */
    public function validate(SOXControlValidationRequest $request): SOXControlValidationResponse;

    /**
     * Validate a single SOX control point.
     *
     * @param array<string, mixed> $context Transaction context
     */
    public function validateControl(
        SOXControlPoint $controlPoint,
        string $tenantId,
        string $transactionId,
        string $userId,
        array $context = [],
    ): SOXControlValidationResult;

    /**
     * Request an override for a failed control.
     *
     * Override requests require approval from a different user (SOD).
     */
    public function requestOverride(SOXOverrideRequest $request): string;

    /**
     * Approve an override request.
     */
    public function approveOverride(
        string $overrideId,
        string $approverId,
        ?string $comment = null,
    ): SOXOverrideResult;

    /**
     * Reject an override request.
     */
    public function rejectOverride(
        string $overrideId,
        string $approverId,
        string $reason,
    ): SOXOverrideResult;

    /**
     * Check if SOX compliance is enabled for a tenant.
     */
    public function isSOXComplianceEnabled(string $tenantId): bool;

    /**
     * Get pending override requests for a tenant.
     *
     * @return array<SOXOverrideRequest>
     */
    public function getPendingOverrides(string $tenantId): array;

    /**
     * Get override history for a transaction.
     *
     * @return array<SOXOverrideResult>
     */
    public function getOverrideHistory(string $transactionId): array;
}

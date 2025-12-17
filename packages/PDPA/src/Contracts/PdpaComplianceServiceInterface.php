<?php

declare(strict_types=1);

namespace Nexus\PDPA\Contracts;

use DateTimeImmutable;
use Nexus\DataPrivacy\ValueObjects\DataSubjectRequest;
use Nexus\PDPA\ValueObjects\PdpaDeadline;

/**
 * PDPA compliance service interface.
 *
 * Provides Malaysia PDPA-specific compliance monitoring and validation.
 */
interface PdpaComplianceServiceInterface
{
    /**
     * Calculate deadline for a data subject request.
     *
     * PDPA Section 30: 21 days to respond.
     */
    public function calculateDeadline(DataSubjectRequest $request): PdpaDeadline;

    /**
     * Check if extension is possible for a request.
     *
     * Note: PDPA extensions require Commissioner approval.
     */
    public function canExtendDeadline(DataSubjectRequest $request): bool;

    /**
     * Request deadline extension.
     *
     * @throws \Nexus\PDPA\Exceptions\PdpaException If extension not allowed
     */
    public function extendDeadline(DataSubjectRequest $request, string $reason): PdpaDeadline;

    /**
     * Check if a request is overdue.
     */
    public function isOverdue(DataSubjectRequest $request, ?DateTimeImmutable $asOf = null): bool;

    /**
     * Get all overdue requests.
     *
     * @return array<DataSubjectRequest>
     */
    public function getOverdueRequests(): array;

    /**
     * Validate PDPA compliance for a specific request.
     *
     * @return array<string> Array of compliance issues
     */
    public function validatePdpaCompliance(DataSubjectRequest $request): array;

    /**
     * Get requests approaching deadline.
     *
     * @return array<DataSubjectRequest>
     */
    public function getRequestsApproachingDeadline(int $withinDays = 5): array;

    /**
     * Get compliance summary.
     *
     * @return array<string, mixed>
     */
    public function getComplianceSummary(): array;

    /**
     * Validate data processing against PDPA principles.
     *
     * @param array<string, mixed> $processingDetails
     * @return array<string> Array of violations
     */
    public function validateProcessingPrinciples(array $processingDetails): array;
}

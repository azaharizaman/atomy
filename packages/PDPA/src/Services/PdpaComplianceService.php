<?php

declare(strict_types=1);

namespace Nexus\PDPA\Services;

use DateTimeImmutable;
use Nexus\DataPrivacy\Contracts\DataSubjectRequestManagerInterface;
use Nexus\DataPrivacy\Enums\RequestStatus;
use Nexus\DataPrivacy\ValueObjects\DataSubjectRequest;
use Nexus\PDPA\Contracts\PdpaComplianceServiceInterface;
use Nexus\PDPA\Enums\PdpaDataPrinciple;
use Nexus\PDPA\Exceptions\PdpaException;
use Nexus\PDPA\ValueObjects\PdpaDeadline;

/**
 * PDPA compliance service implementation.
 *
 * Provides Malaysia PDPA-specific compliance monitoring and validation.
 * Key difference from GDPR: 21-day response deadline (vs 30 days).
 */
final readonly class PdpaComplianceService implements PdpaComplianceServiceInterface
{
    public function __construct(
        private DataSubjectRequestManagerInterface $requestManager,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function calculateDeadline(DataSubjectRequest $request): PdpaDeadline
    {
        $deadline = PdpaDeadline::forDataSubjectRequest($request);

        // Check if extension was granted
        if (isset($request->metadata['deadline_extended']) && $request->metadata['deadline_extended'] === true) {
            $extensionReason = $request->metadata['extension_reason'] ?? 'Commissioner approved extension';
            $extensionDays = $request->metadata['extension_days'] ?? PdpaDeadline::MAX_EXTENSION_DAYS;
            $deadline = $deadline->extend($extensionReason, (int) $extensionDays);
        }

        return $deadline;
    }

    /**
     * {@inheritDoc}
     */
    public function canExtendDeadline(DataSubjectRequest $request): bool
    {
        return !isset($request->metadata['deadline_extended'])
            || $request->metadata['deadline_extended'] !== true;
    }

    /**
     * {@inheritDoc}
     */
    public function extendDeadline(DataSubjectRequest $request, string $reason): PdpaDeadline
    {
        if (!$this->canExtendDeadline($request)) {
            throw PdpaException::extensionLimitExceeded();
        }

        $currentDeadline = $this->calculateDeadline($request);

        return $currentDeadline->extend($reason);
    }

    /**
     * {@inheritDoc}
     */
    public function isOverdue(DataSubjectRequest $request, ?DateTimeImmutable $asOf = null): bool
    {
        $deadline = $this->calculateDeadline($request);
        $checkDate = $asOf ?? new DateTimeImmutable();

        // Only check overdue for pending/in-progress requests
        if ($request->status === RequestStatus::COMPLETED || $request->status === RequestStatus::REJECTED) {
            return false;
        }

        return $deadline->isOverdue($checkDate);
    }

    /**
     * {@inheritDoc}
     */
    public function getOverdueRequests(): array
    {
        $activeRequests = $this->requestManager->getActiveRequests();
        $overdue = [];

        foreach ($activeRequests as $request) {
            if ($this->isOverdue($request)) {
                $overdue[] = $request;
            }
        }

        return $overdue;
    }

    /**
     * {@inheritDoc}
     */
    public function validatePdpaCompliance(DataSubjectRequest $request): array
    {
        $errors = [];

        // Check if deadline is exceeded
        if ($this->isOverdue($request)) {
            $deadline = $this->calculateDeadline($request);
            $daysOverdue = $deadline->getDaysOverdue(new DateTimeImmutable());
            $errors[] = "Request is {$daysOverdue} days overdue (PDPA Section 30 violation)";
        }

        // Check if request has been acknowledged within 5 days (best practice for PDPA)
        if ($request->status === RequestStatus::PENDING) {
            $daysSinceSubmission = $request->submittedAt->diff(new DateTimeImmutable())->days;
            if ($daysSinceSubmission > 5) {
                $errors[] = 'Request should be acknowledged within 5 days (PDPA best practice)';
            }
        }

        // Check if identity has been verified for access requests
        if ($request->type->value === 'access') {
            $identityVerified = $request->metadata['identity_verified'] ?? false;
            if (!$identityVerified) {
                $errors[] = 'Identity must be verified before processing access request (PDPA Section 12)';
            }
        }

        // Check for fee collection (PDPA allows fees for access requests)
        if ($request->type->value === 'access') {
            $feeCollected = $request->metadata['fee_collected'] ?? null;
            $feeWaived = $request->metadata['fee_waived'] ?? false;

            if ($feeCollected === null && !$feeWaived) {
                // This is informational, not a compliance error
                // PDPA allows reasonable fees for access requests
            }
        }

        return $errors;
    }

    /**
     * Get requests due within specified days.
     *
     * @return array<DataSubjectRequest>
     */
    public function getRequestsDueWithinDays(int $days): array
    {
        $activeRequests = $this->requestManager->getActiveRequests();
        $dueSoon = [];
        $now = new DateTimeImmutable();

        foreach ($activeRequests as $request) {
            $deadline = $this->calculateDeadline($request);
            $daysRemaining = $deadline->getDaysRemaining($now);

            // Include if due within specified days but not overdue
            if ($daysRemaining >= 0 && $daysRemaining <= $days) {
                $dueSoon[] = $request;
            }
        }

        return $dueSoon;
    }

    /**
     * {@inheritDoc}
     */
    public function getRequestsApproachingDeadline(int $withinDays = 5): array
    {
        return $this->getRequestsDueWithinDays($withinDays);
    }

    /**
     * {@inheritDoc}
     */
    public function getComplianceSummary(): array
    {
        $activeRequests = $this->requestManager->getActiveRequests();
        $now = new DateTimeImmutable();

        $overdue = 0;
        $approaching = 0;
        $onTrack = 0;
        $totalDaysRemaining = 0;

        foreach ($activeRequests as $request) {
            $deadline = $this->calculateDeadline($request);
            $daysRemaining = $deadline->getDaysRemaining($now);

            if ($daysRemaining < 0) {
                $overdue++;
            } elseif ($daysRemaining <= 5) {
                // PDPA: 5 days approaching deadline (vs 7 for GDPR, proportional to 21 vs 30)
                $approaching++;
            } else {
                $onTrack++;
            }

            $totalDaysRemaining += max(0, $daysRemaining);
        }

        $totalPending = count($activeRequests);

        return [
            'total_pending' => $totalPending,
            'overdue' => $overdue,
            'approaching_deadline' => $approaching,
            'on_track' => $onTrack,
            'average_days_remaining' => $totalPending > 0 ? round($totalDaysRemaining / $totalPending, 1) : 0,
            'compliance_rate' => $totalPending > 0 ? round((($totalPending - $overdue) / $totalPending) * 100, 1) : 100.0,
            'regulation' => 'PDPA 2010',
            'deadline_days' => PdpaDeadline::STANDARD_DEADLINE_DAYS,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function validateProcessingPrinciples(array $processingDetails): array
    {
        $violations = [];

        // General Principle (Section 6) - Consent
        if (!isset($processingDetails['consent_obtained']) || !$processingDetails['consent_obtained']) {
            $hasLegalBasis = isset($processingDetails['legal_basis'])
                && in_array($processingDetails['legal_basis'], ['contract', 'legal_obligation', 'vital_interests'], true);

            if (!$hasLegalBasis) {
                $violations[] = sprintf(
                    '%s violation: %s',
                    PdpaDataPrinciple::GENERAL->getLabel(),
                    'Personal data processed without consent or valid legal basis'
                );
            }
        }

        // Notice and Choice Principle (Section 7)
        if (!isset($processingDetails['privacy_notice_provided']) || !$processingDetails['privacy_notice_provided']) {
            $violations[] = sprintf(
                '%s violation: %s',
                PdpaDataPrinciple::NOTICE_AND_CHOICE->getLabel(),
                'Data subject not informed of data collection purpose and rights'
            );
        }

        // Security Principle (Section 9)
        if (isset($processingDetails['security_measures'])) {
            $measures = $processingDetails['security_measures'];
            if (empty($measures) || (is_array($measures) && count($measures) < 2)) {
                $violations[] = sprintf(
                    '%s violation: %s',
                    PdpaDataPrinciple::SECURITY->getLabel(),
                    'Insufficient security measures documented'
                );
            }
        }

        // Retention Principle (Section 10)
        if (isset($processingDetails['retention_period'])) {
            $retention = $processingDetails['retention_period'];
            $purpose = $processingDetails['purpose'] ?? 'general';

            // Check if retention exceeds recommended periods
            $maxRecommendedDays = $this->getRecommendedRetention($purpose);
            if ($retention > $maxRecommendedDays) {
                $violations[] = sprintf(
                    '%s warning: %s',
                    PdpaDataPrinciple::RETENTION->getLabel(),
                    "Retention period of {$retention} days may exceed necessary period for purpose '{$purpose}'"
                );
            }
        }

        // Data Integrity Principle (Section 11)
        if (isset($processingDetails['data_accuracy_verified']) && !$processingDetails['data_accuracy_verified']) {
            $violations[] = sprintf(
                '%s violation: %s',
                PdpaDataPrinciple::DATA_INTEGRITY->getLabel(),
                'Data accuracy not verified'
            );
        }

        return $violations;
    }

    /**
     * Get recommended retention period for a purpose.
     */
    private function getRecommendedRetention(string $purpose): int
    {
        return match ($purpose) {
            'marketing' => 365,              // 1 year
            'contract' => 2555,              // 7 years
            'employment' => 2555,            // 7 years
            'tax' => 2555,                   // 7 years
            'legal_proceedings' => 3650,     // 10 years
            'medical' => 5475,               // 15 years
            default => 1095,                 // 3 years default
        };
    }
}

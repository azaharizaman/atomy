<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Services;

use Nexus\AmlCompliance\Contracts\PartyInterface;
use Nexus\AmlCompliance\Contracts\SarManagerInterface;
use Nexus\AmlCompliance\Enums\SarStatus;
use Nexus\AmlCompliance\Exceptions\SarGenerationFailedException;
use Nexus\AmlCompliance\ValueObjects\AmlRiskScore;
use Nexus\AmlCompliance\ValueObjects\SuspiciousActivityReport;
use Nexus\AmlCompliance\ValueObjects\TransactionMonitoringResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * SAR Manager Service
 * 
 * Production-ready implementation of Suspicious Activity Report management.
 * Handles the complete SAR lifecycle from creation through filing.
 * 
 * Compliant with FinCEN SAR requirements:
 * - 30-day filing deadline from discovery
 * - Narrative requirements (5 "W"s)
 * - Document retention
 * - Filing reference tracking
 */
final class SarManager implements SarManagerInterface
{
    /**
     * SAR storage (in production would be a repository interface)
     * @var array<string, SuspiciousActivityReport>
     */
    private array $sars = [];

    /**
     * Minimum narrative length for filing
     */
    private const MIN_NARRATIVE_LENGTH = 100;

    /**
     * Maximum SAR filing deadline in days (FinCEN requirement)
     */
    private const FILING_DEADLINE_DAYS = 30;

    public function __construct(
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * {@inheritdoc}
     */
    public function createFromMonitoring(
        TransactionMonitoringResult $result,
        PartyInterface $party,
        string $createdBy
    ): SuspiciousActivityReport {
        $this->logger->info('Creating SAR from monitoring result', [
            'party_id' => $result->partyId,
            'is_suspicious' => $result->isSuspicious,
            'alert_count' => count($result->alerts),
        ]);

        if (!$result->isSuspicious) {
            throw SarGenerationFailedException::insufficientEvidence([
                'is_suspicious' => false,
                'risk_score' => $result->riskScore,
            ]);
        }

        // Determine SAR type based on patterns detected
        $sarType = $this->determineSarType($result->patterns);

        // Generate initial narrative from monitoring result
        $narrative = $this->generateNarrativeFromMonitoring($result, $party);

        // Extract transaction IDs
        $transactionIds = [];
        foreach ($result->alerts as $alert) {
            if (isset($alert->evidence['transaction_ids'])) {
                $transactionIds = array_merge($transactionIds, $alert->evidence['transaction_ids']);
            }
            if (isset($alert->evidence['transaction_id'])) {
                $transactionIds[] = $alert->evidence['transaction_id'];
            }
        }

        // Extract suspicious activity indicators from alerts
        $suspiciousActivities = [];
        foreach ($result->alerts as $alert) {
            $suspiciousActivities[] = $alert->type;
        }
        $suspiciousActivities = array_unique($suspiciousActivities);

        $sar = new SuspiciousActivityReport(
            sarId: $this->generateSarId(),
            partyId: $result->partyId,
            status: SarStatus::DRAFT,
            type: $sarType,
            narrative: $narrative,
            totalAmount: $result->totalVolume,
            currency: 'USD', // Default currency
            activityStartDate: $result->periodStart,
            activityEndDate: $result->periodEnd,
            transactionIds: array_unique($transactionIds),
            suspiciousActivities: $suspiciousActivities,
            alerts: $result->alerts,
            createdBy: $createdBy,
            createdAt: new \DateTimeImmutable(),
        );

        $this->sars[$sar->sarId] = $sar;

        $this->logger->info('SAR created from monitoring', [
            'sar_id' => $sar->sarId,
            'party_id' => $sar->partyId,
            'type' => $sarType,
            'alert_count' => count($sar->alerts),
        ]);

        return $sar;
    }

    /**
     * {@inheritdoc}
     */
    public function createFromRiskAssessment(
        AmlRiskScore $riskScore,
        PartyInterface $party,
        string $reason,
        string $createdBy
    ): SuspiciousActivityReport {
        $this->logger->info('Creating SAR from risk assessment', [
            'party_id' => $riskScore->partyId,
            'risk_level' => $riskScore->riskLevel->value,
            'overall_score' => $riskScore->overallScore,
        ]);

        // Generate narrative from risk assessment
        $narrative = $this->generateNarrativeFromRiskAssessment($riskScore, $party, $reason);

        // Generate suspicious activities list from risk factors
        $suspiciousActivities = [];
        if ($riskScore->factors !== null) {
            $metadata = $riskScore->factors->metadata;
            if (($metadata['pep_level'] ?? null) !== null) {
                $suspiciousActivities[] = 'pep_involvement';
            }
            if ($metadata['has_adverse_media'] ?? false) {
                $suspiciousActivities[] = 'adverse_media';
            }
            if ($riskScore->factors->sanctionsScore > 0) {
                $suspiciousActivities[] = 'sanctions_exposure';
            }
            if ($riskScore->factors->jurisdictionScore >= 50) {
                $suspiciousActivities[] = 'high_risk_jurisdiction';
            }
        }
        if (empty($suspiciousActivities)) {
            $suspiciousActivities[] = 'elevated_risk_score';
        }

        $sar = new SuspiciousActivityReport(
            sarId: $this->generateSarId(),
            partyId: $riskScore->partyId,
            status: SarStatus::DRAFT,
            type: SuspiciousActivityReport::TYPE_SUSPICIOUS_PARTY,
            narrative: $narrative,
            totalAmount: null,
            currency: null,
            activityStartDate: null,
            activityEndDate: null,
            transactionIds: [],
            suspiciousActivities: $suspiciousActivities,
            alerts: [],
            createdBy: $createdBy,
            createdAt: new \DateTimeImmutable(),
        );

        $this->sars[$sar->sarId] = $sar;

        $this->logger->info('SAR created from risk assessment', [
            'sar_id' => $sar->sarId,
            'party_id' => $sar->partyId,
            'risk_level' => $riskScore->riskLevel->value,
        ]);

        return $sar;
    }

    /**
     * {@inheritdoc}
     */
    public function createManual(
        string $partyId,
        string $type,
        string $narrative,
        ?float $amount,
        ?string $currency,
        ?\DateTimeImmutable $activityStartDate,
        ?\DateTimeImmutable $activityEndDate,
        string $createdBy
    ): SuspiciousActivityReport {
        $this->logger->info('Creating manual SAR', [
            'party_id' => $partyId,
            'type' => $type,
        ]);

        // Validate SAR type
        $validTypes = SuspiciousActivityReport::getAllTypes();
        if (!in_array($type, $validTypes, true)) {
            throw new \InvalidArgumentException(
                "Invalid SAR type: {$type}. Valid types are: " . implode(', ', $validTypes)
            );
        }

        // Validate activity dates
        if ($activityStartDate !== null && $activityEndDate !== null) {
            if ($activityEndDate < $activityStartDate) {
                throw new \InvalidArgumentException(
                    'Activity end date must be after start date'
                );
            }
        }

        $sar = new SuspiciousActivityReport(
            sarId: $this->generateSarId(),
            partyId: $partyId,
            status: SarStatus::DRAFT,
            type: $type,
            narrative: $narrative,
            totalAmount: $amount,
            currency: $currency,
            activityStartDate: $activityStartDate,
            activityEndDate: $activityEndDate,
            transactionIds: [],
            suspiciousActivities: [$type],
            alerts: [],
            createdBy: $createdBy,
            createdAt: new \DateTimeImmutable(),
        );

        $this->sars[$sar->sarId] = $sar;

        $this->logger->info('Manual SAR created', [
            'sar_id' => $sar->sarId,
            'party_id' => $partyId,
            'type' => $type,
        ]);

        return $sar;
    }

    /**
     * {@inheritdoc}
     */
    public function updateNarrative(
        string $sarId,
        string $narrative,
        string $updatedBy
    ): SuspiciousActivityReport {
        $sar = $this->findById($sarId);

        if (!$sar->status->isEditable()) {
            throw new \InvalidArgumentException(
                sprintf('Cannot update narrative: SAR %s is in %s status', $sarId, $sar->status->value)
            );
        }

        $updatedSar = $sar->withNarrative($narrative)
            ->withUpdatedBy($updatedBy);

        $this->sars[$sarId] = $updatedSar;

        $this->logger->debug('SAR narrative updated', [
            'sar_id' => $sarId,
            'narrative_length' => strlen($narrative),
        ]);

        return $updatedSar;
    }

    /**
     * {@inheritdoc}
     */
    public function addTransactions(
        string $sarId,
        array $transactionIds,
        string $updatedBy
    ): SuspiciousActivityReport {
        $sar = $this->findById($sarId);

        if (!$sar->status->isEditable()) {
            throw new \InvalidArgumentException(
                sprintf('Cannot add transactions: SAR %s is in %s status', $sarId, $sar->status->value)
            );
        }

        $allTransactionIds = array_unique(array_merge($sar->transactionIds, $transactionIds));
        $updatedSar = $sar->withTransactionIds($allTransactionIds)
            ->withUpdatedBy($updatedBy);

        $this->sars[$sarId] = $updatedSar;

        $this->logger->debug('Transactions added to SAR', [
            'sar_id' => $sarId,
            'new_transactions' => count($transactionIds),
            'total_transactions' => count($allTransactionIds),
        ]);

        return $updatedSar;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForReview(
        string $sarId,
        string $submittedBy
    ): SuspiciousActivityReport {
        $sar = $this->findById($sarId);

        // Validate before submission
        $validationErrors = $this->validate($sar);
        if (!empty($validationErrors)) {
            throw SarGenerationFailedException::invalidNarrative(
                $sarId,
                implode('; ', $validationErrors)
            );
        }

        try {
            $updatedSar = $sar->submitForReview()
                ->withUpdatedBy($submittedBy);

            $this->sars[$sarId] = $updatedSar;

            $this->logger->info('SAR submitted for review', [
                'sar_id' => $sarId,
                'submitted_by' => $submittedBy,
            ]);

            return $updatedSar;
        } catch (\InvalidArgumentException $e) {
            throw SarGenerationFailedException::invalidTransition(
                $sarId,
                $sar->status->value,
                SarStatus::PENDING_REVIEW->value
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function approve(
        string $sarId,
        string $approvedBy,
        ?string $comments = null
    ): SuspiciousActivityReport {
        $sar = $this->findById($sarId);

        // Only reviewers can approve (not the creator)
        if ($sar->createdBy === $approvedBy) {
            throw SarGenerationFailedException::approvalRequired(
                $sarId,
                'different_officer'
            );
        }

        try {
            $updatedSar = $sar->approve($approvedBy, $comments);
            $this->sars[$sarId] = $updatedSar;

            $this->logger->info('SAR approved', [
                'sar_id' => $sarId,
                'approved_by' => $approvedBy,
            ]);

            return $updatedSar;
        } catch (\InvalidArgumentException $e) {
            throw SarGenerationFailedException::invalidTransition(
                $sarId,
                $sar->status->value,
                SarStatus::APPROVED->value
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function reject(
        string $sarId,
        string $rejectedBy,
        string $reason
    ): SuspiciousActivityReport {
        $sar = $this->findById($sarId);

        if ($sar->status !== SarStatus::PENDING_REVIEW) {
            throw SarGenerationFailedException::invalidTransition(
                $sarId,
                $sar->status->value,
                SarStatus::REJECTED->value
            );
        }

        $updatedSar = $sar->transitionTo(SarStatus::REJECTED)
            ->withUpdatedBy($rejectedBy)
            ->withRejectionReason($reason);

        $this->sars[$sarId] = $updatedSar;

        $this->logger->info('SAR rejected', [
            'sar_id' => $sarId,
            'rejected_by' => $rejectedBy,
            'reason' => $reason,
        ]);

        return $updatedSar;
    }

    /**
     * {@inheritdoc}
     */
    public function submitToAuthority(
        string $sarId,
        string $filingReference,
        string $submittedBy
    ): SuspiciousActivityReport {
        $sar = $this->findById($sarId);

        if ($sar->status !== SarStatus::APPROVED) {
            throw SarGenerationFailedException::approvalRequired(
                $sarId,
                'compliance_officer'
            );
        }

        try {
            $updatedSar = $sar->submitToAuthority($filingReference, $submittedBy);
            $this->sars[$sarId] = $updatedSar;

            $this->logger->info('SAR submitted to authority', [
                'sar_id' => $sarId,
                'filing_reference' => $filingReference,
                'submitted_by' => $submittedBy,
            ]);

            return $updatedSar;
        } catch (\InvalidArgumentException $e) {
            throw SarGenerationFailedException::filingServiceError(
                $sarId,
                $e->getMessage()
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close(
        string $sarId,
        string $resolution,
        string $closedBy
    ): SuspiciousActivityReport {
        $sar = $this->findById($sarId);

        try {
            $updatedSar = $sar->close($resolution, $closedBy);
            $this->sars[$sarId] = $updatedSar;

            $this->logger->info('SAR closed', [
                'sar_id' => $sarId,
                'resolution' => $resolution,
                'closed_by' => $closedBy,
            ]);

            return $updatedSar;
        } catch (\InvalidArgumentException $e) {
            throw SarGenerationFailedException::invalidTransition(
                $sarId,
                $sar->status->value,
                SarStatus::CLOSED->value
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function cancel(
        string $sarId,
        string $reason,
        string $cancelledBy
    ): SuspiciousActivityReport {
        $sar = $this->findById($sarId);

        if ($sar->status->isFinal()) {
            throw SarGenerationFailedException::invalidTransition(
                $sarId,
                $sar->status->value,
                SarStatus::CANCELLED->value
            );
        }

        // Cannot cancel if already submitted to authority
        if ($sar->status === SarStatus::SUBMITTED) {
            throw SarGenerationFailedException::alreadySubmitted(
                $sarId,
                new \DateTimeImmutable()
            );
        }

        $updatedSar = $sar->transitionTo(SarStatus::CANCELLED)
            ->withUpdatedBy($cancelledBy)
            ->withCancellationReason($reason);

        $this->sars[$sarId] = $updatedSar;

        $this->logger->info('SAR cancelled', [
            'sar_id' => $sarId,
            'reason' => $reason,
            'cancelled_by' => $cancelledBy,
        ]);

        return $updatedSar;
    }

    /**
     * {@inheritdoc}
     */
    public function assignOfficer(
        string $sarId,
        string $officerId,
        string $assignedBy
    ): SuspiciousActivityReport {
        $sar = $this->findById($sarId);

        if ($sar->status->isFinal()) {
            throw new \InvalidArgumentException(
                sprintf('Cannot assign officer: SAR %s is in %s status', $sarId, $sar->status->value)
            );
        }

        $updatedSar = $sar->withAssignedOfficer($officerId)
            ->withUpdatedBy($assignedBy);

        $this->sars[$sarId] = $updatedSar;

        $this->logger->debug('SAR officer assigned', [
            'sar_id' => $sarId,
            'officer_id' => $officerId,
        ]);

        return $updatedSar;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(SuspiciousActivityReport $sar): array
    {
        $errors = [];

        // Required fields
        if (empty($sar->partyId)) {
            $errors[] = 'Party ID is required';
        }

        if (empty($sar->type)) {
            $errors[] = 'SAR type is required';
        }

        // Narrative validation
        if (empty($sar->narrative)) {
            $errors[] = 'Narrative is required';
        } elseif (strlen($sar->narrative) < self::MIN_NARRATIVE_LENGTH) {
            $errors[] = sprintf(
                'Narrative must be at least %d characters (currently %d)',
                self::MIN_NARRATIVE_LENGTH,
                strlen($sar->narrative)
            );
        }

        // Activity dates for transaction-related SARs
        if (!empty($sar->transactionIds)) {
            if ($sar->activityStartDate === null) {
                $errors[] = 'Activity start date is required for transaction-related SARs';
            }
            if ($sar->activityEndDate === null) {
                $errors[] = 'Activity end date is required for transaction-related SARs';
            }
        }

        // Amount validation
        if ($sar->totalAmount !== null && $sar->totalAmount < 0) {
            $errors[] = 'Total amount cannot be negative';
        }

        // Currency validation if amount is present
        if ($sar->totalAmount !== null && empty($sar->currency)) {
            $errors[] = 'Currency is required when amount is specified';
        }

        return $errors;
    }

    /**
     * {@inheritdoc}
     */
    public function isOverdue(SuspiciousActivityReport $sar): bool
    {
        return $sar->isOverdue();
    }

    /**
     * {@inheritdoc}
     */
    public function getDaysUntilDeadline(SuspiciousActivityReport $sar): int
    {
        return $sar->getDaysUntilDeadline();
    }

    /**
     * {@inheritdoc}
     */
    public function generateSummary(SuspiciousActivityReport $sar): array
    {
        $now = new \DateTimeImmutable();

        return [
            'sar_id' => $sar->sarId,
            'party_id' => $sar->partyId,
            'status' => $sar->status->value,
            'status_phase' => $sar->status->getPhase(),
            'type' => $sar->type,
            'total_amount' => $sar->totalAmount,
            'currency' => $sar->currency,
            'transaction_count' => count($sar->transactionIds),
            'alert_count' => count($sar->alerts),
            'activity_period' => [
                'start' => $sar->activityStartDate?->format('Y-m-d'),
                'end' => $sar->activityEndDate?->format('Y-m-d'),
            ],
            'created' => [
                'at' => $sar->createdAt->format('Y-m-d H:i:s'),
                'by' => $sar->createdBy,
            ],
            'updated' => [
                'at' => $sar->metadata['updated_at'] ?? null,
                'by' => $sar->metadata['updated_by'] ?? null,
            ],
            'assigned_officer' => $sar->assignedOfficer,
            'filing_reference' => $sar->filingReference,
            'is_overdue' => $sar->isOverdue(),
            'days_until_deadline' => $sar->getDaysUntilDeadline(),
            'is_editable' => $sar->status->isEditable(),
            'is_final' => $sar->status->isFinal(),
            'narrative_length' => strlen($sar->narrative),
            'narrative_preview' => substr($sar->narrative, 0, 200) . (strlen($sar->narrative) > 200 ? '...' : ''),
            'validation_errors' => $this->validate($sar),
        ];
    }

    /**
     * Find SAR by ID
     */
    public function findById(string $sarId): SuspiciousActivityReport
    {
        if (!isset($this->sars[$sarId])) {
            throw SarGenerationFailedException::notFound($sarId);
        }

        return $this->sars[$sarId];
    }

    /**
     * Check if SAR exists
     */
    public function exists(string $sarId): bool
    {
        return isset($this->sars[$sarId]);
    }

    /**
     * Generate unique SAR ID
     */
    private function generateSarId(): string
    {
        return sprintf(
            'SAR-%s-%s',
            date('Ymd'),
            strtoupper(substr(bin2hex(random_bytes(6)), 0, 8))
        );
    }

    /**
     * Determine SAR type from detected patterns
     */
    private function determineSarType(array $patterns): string
    {
        // Priority-based type determination
        if (in_array('structuring', $patterns, true)) {
            return SuspiciousActivityReport::TYPE_STRUCTURING;
        }
        if (in_array('layering', $patterns, true)) {
            return SuspiciousActivityReport::TYPE_MONEY_LAUNDERING;
        }
        if (in_array('geographic', $patterns, true)) {
            return SuspiciousActivityReport::TYPE_SANCTIONS_EVASION;
        }
        if (in_array('velocity', $patterns, true)) {
            return SuspiciousActivityReport::TYPE_UNUSUAL_ACTIVITY;
        }
        if (in_array('round_amounts', $patterns, true)) {
            return SuspiciousActivityReport::TYPE_STRUCTURING;
        }

        return SuspiciousActivityReport::TYPE_OTHER;
    }

    /**
     * Generate narrative from monitoring result
     */
    private function generateNarrativeFromMonitoring(
        TransactionMonitoringResult $result,
        PartyInterface $party
    ): string {
        $parts = [];

        // WHO
        $parts[] = sprintf(
            'Subject: %s (ID: %s), a %s operating in %s.',
            $party->getName(),
            $party->getId(),
            $party->getType(),
            $party->getCountryCode()
        );

        // WHAT
        $parts[] = sprintf(
            'During the monitoring period, the following suspicious patterns were detected: %s.',
            implode(', ', $result->patterns)
        );

        // WHEN
        $parts[] = sprintf(
            'Activity period: %s to %s.',
            $result->periodStart->format('Y-m-d'),
            $result->periodEnd->format('Y-m-d')
        );

        // Amount if applicable
        if ($result->totalVolume > 0) {
            $parts[] = sprintf(
                'Total transaction volume: %.2f USD across %d transactions.',
                $result->totalVolume,
                $result->transactionCount
            );
        }

        // WHY
        if (!empty($result->reasons)) {
            $parts[] = 'Reasons for suspicion: ' . implode(' ', $result->reasons);
        }

        // Alerts summary
        if (!empty($result->alerts)) {
            $parts[] = sprintf(
                'Total alerts generated: %d. Highest severity: %s.',
                count($result->alerts),
                $result->getHighestSeverity() ?? 'unknown'
            );
        }

        return implode("\n\n", $parts);
    }

    /**
     * Generate narrative from risk assessment
     */
    private function generateNarrativeFromRiskAssessment(
        AmlRiskScore $riskScore,
        PartyInterface $party,
        string $reason
    ): string {
        $parts = [];

        // WHO
        $parts[] = sprintf(
            'Subject: %s (ID: %s), a %s operating in %s.',
            $party->getName(),
            $party->getId(),
            $party->getType(),
            $party->getCountryCode()
        );

        // Risk level
        $parts[] = sprintf(
            'Risk Assessment: Overall score of %d (Level: %s). Assessment performed on %s by %s.',
            $riskScore->overallScore,
            $riskScore->riskLevel->value,
            $riskScore->assessedAt->format('Y-m-d H:i:s'),
            $riskScore->assessedBy ?? 'system'
        );

        // Risk factors breakdown
        $parts[] = sprintf(
            'Risk Factor Breakdown: Jurisdiction Risk: %d, Business Type Risk: %d, Sanctions Risk: %d, Transaction Risk: %d.',
            $riskScore->factors->jurisdictionScore,
            $riskScore->factors->businessTypeScore,
            $riskScore->factors->sanctionsScore,
            $riskScore->factors->transactionScore
        );

        // PEP status
        if ($party->isPep()) {
            $parts[] = sprintf(
                'Note: Subject is a Politically Exposed Person (Level %d).',
                $party->getPepLevel() ?? 0
            );
        }

        // Reason
        $parts[] = 'Reason for filing: ' . $reason;

        // Recommendations if any
        if (!empty($riskScore->recommendations)) {
            $parts[] = 'Recommendations: ' . implode('; ', $riskScore->recommendations);
        }

        return implode("\n\n", $parts);
    }
}

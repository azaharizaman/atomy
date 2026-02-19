<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Contracts;

use Nexus\AmlCompliance\Enums\SarStatus;
use Nexus\AmlCompliance\ValueObjects\AmlRiskScore;
use Nexus\AmlCompliance\ValueObjects\SuspiciousActivityReport;
use Nexus\AmlCompliance\ValueObjects\TransactionMonitoringResult;

/**
 * SAR Manager interface
 * 
 * Defines contract for Suspicious Activity Report lifecycle management.
 * Based on FinCEN SAR requirements and FATF guidelines.
 * 
 * Key compliance features:
 * - 30-day filing deadline from discovery
 * - Minimum narrative requirements
 * - Status workflow enforcement
 * - Segregation of duties (creator cannot approve)
 */
interface SarManagerInterface
{
    /**
     * Create a new SAR from monitoring results
     * 
     * SAR type and narrative are auto-generated from the monitoring result patterns.
     * 
     * @param TransactionMonitoringResult $result Monitoring findings
     * @param PartyInterface $party Subject party
     * @param string $createdBy User creating the SAR
     * @return SuspiciousActivityReport Created SAR in DRAFT status
     * 
     * @throws \Nexus\AmlCompliance\Exceptions\SarGenerationFailedException
     */
    public function createFromMonitoring(
        TransactionMonitoringResult $result,
        PartyInterface $party,
        string $createdBy
    ): SuspiciousActivityReport;

    /**
     * Create a new SAR from risk assessment
     * 
     * @param AmlRiskScore $riskScore Risk assessment findings
     * @param PartyInterface $party Subject party
     * @param string $reason Reason for creating SAR
     * @param string $createdBy User creating the SAR
     * @return SuspiciousActivityReport Created SAR in DRAFT status
     * 
     * @throws \Nexus\AmlCompliance\Exceptions\SarGenerationFailedException
     */
    public function createFromRiskAssessment(
        AmlRiskScore $riskScore,
        PartyInterface $party,
        string $reason,
        string $createdBy
    ): SuspiciousActivityReport;

    /**
     * Create a manual SAR
     * 
     * @param string $partyId Subject party ID
     * @param string $type SAR type (see SuspiciousActivityReport::TYPE_* constants)
     * @param string $narrative Detailed description
     * @param float|null $amount Total amount involved
     * @param string|null $currency Currency code
     * @param \DateTimeImmutable|null $activityStartDate Start of suspicious activity
     * @param \DateTimeImmutable|null $activityEndDate End of suspicious activity
     * @param string $createdBy User creating the SAR
     * @return SuspiciousActivityReport Created SAR
     * 
     * @throws \Nexus\AmlCompliance\Exceptions\SarGenerationFailedException
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
    ): SuspiciousActivityReport;

    /**
     * Update SAR narrative
     * 
     * @param string $sarId SAR ID
     * @param string $narrative Updated narrative
     * @param string $updatedBy User updating
     * @return SuspiciousActivityReport Updated SAR
     * 
     * @throws \Nexus\AmlCompliance\Exceptions\SarGenerationFailedException If SAR is not editable
     */
    public function updateNarrative(
        string $sarId,
        string $narrative,
        string $updatedBy
    ): SuspiciousActivityReport;

    /**
     * Add transactions to SAR
     * 
     * @param string $sarId SAR ID
     * @param array<string> $transactionIds Transaction IDs to add
     * @param string $updatedBy User updating
     * @return SuspiciousActivityReport Updated SAR
     * 
     * @throws \Nexus\AmlCompliance\Exceptions\SarGenerationFailedException
     */
    public function addTransactions(
        string $sarId,
        array $transactionIds,
        string $updatedBy
    ): SuspiciousActivityReport;

    /**
     * Submit SAR for review
     * 
     * @param string $sarId SAR ID
     * @param string $submittedBy User submitting
     * @return SuspiciousActivityReport SAR in PENDING_REVIEW status
     * 
     * @throws \Nexus\AmlCompliance\Exceptions\SarGenerationFailedException
     */
    public function submitForReview(
        string $sarId,
        string $submittedBy
    ): SuspiciousActivityReport;

    /**
     * Approve SAR
     * 
     * Note: Approver cannot be the same as creator (segregation of duties)
     * 
     * @param string $sarId SAR ID
     * @param string $approvedBy User approving
     * @param string|null $comments Optional approval comments
     * @return SuspiciousActivityReport SAR in APPROVED status
     * 
     * @throws \Nexus\AmlCompliance\Exceptions\SarGenerationFailedException
     */
    public function approve(
        string $sarId,
        string $approvedBy,
        ?string $comments = null
    ): SuspiciousActivityReport;

    /**
     * Reject SAR
     * 
     * @param string $sarId SAR ID
     * @param string $rejectedBy User rejecting
     * @param string $reason Rejection reason
     * @return SuspiciousActivityReport SAR in REJECTED status
     * 
     * @throws \Nexus\AmlCompliance\Exceptions\SarGenerationFailedException
     */
    public function reject(
        string $sarId,
        string $rejectedBy,
        string $reason
    ): SuspiciousActivityReport;

    /**
     * Submit SAR to regulatory authority
     * 
     * @param string $sarId SAR ID
     * @param string $filingReference Reference from authority
     * @param string $submittedBy User submitting
     * @return SuspiciousActivityReport SAR in SUBMITTED status
     * 
     * @throws \Nexus\AmlCompliance\Exceptions\SarGenerationFailedException
     */
    public function submitToAuthority(
        string $sarId,
        string $filingReference,
        string $submittedBy
    ): SuspiciousActivityReport;

    /**
     * Close SAR
     * 
     * @param string $sarId SAR ID
     * @param string $resolution Resolution notes
     * @param string $closedBy User closing
     * @return SuspiciousActivityReport SAR in CLOSED status
     * 
     * @throws \Nexus\AmlCompliance\Exceptions\SarGenerationFailedException
     */
    public function close(
        string $sarId,
        string $resolution,
        string $closedBy
    ): SuspiciousActivityReport;

    /**
     * Cancel SAR
     * 
     * @param string $sarId SAR ID
     * @param string $reason Cancellation reason
     * @param string $cancelledBy User cancelling
     * @return SuspiciousActivityReport SAR in CANCELLED status
     * 
     * @throws \Nexus\AmlCompliance\Exceptions\SarGenerationFailedException
     */
    public function cancel(
        string $sarId,
        string $reason,
        string $cancelledBy
    ): SuspiciousActivityReport;

    /**
     * Assign compliance officer to SAR
     * 
     * @param string $sarId SAR ID
     * @param string $officerId Officer user ID
     * @param string $assignedBy User assigning
     * @return SuspiciousActivityReport Updated SAR
     * 
     * @throws \Nexus\AmlCompliance\Exceptions\SarGenerationFailedException
     */
    public function assignOfficer(
        string $sarId,
        string $officerId,
        string $assignedBy
    ): SuspiciousActivityReport;

    /**
     * Validate SAR for submission
     * 
     * @param SuspiciousActivityReport $sar SAR to validate
     * @return array<string> List of validation errors (empty if valid)
     */
    public function validate(SuspiciousActivityReport $sar): array;

    /**
     * Check if SAR is overdue for filing
     * 
     * @param SuspiciousActivityReport $sar SAR to check
     * @return bool True if past filing deadline
     */
    public function isOverdue(SuspiciousActivityReport $sar): bool;

    /**
     * Get days until filing deadline
     * 
     * @param SuspiciousActivityReport $sar SAR to check
     * @return int Days remaining (negative if overdue)
     */
    public function getDaysUntilDeadline(SuspiciousActivityReport $sar): int;

    /**
     * Generate SAR summary for reporting
     * 
     * @param SuspiciousActivityReport $sar SAR to summarize
     * @return array<string, mixed> Summary data
     */
    public function generateSummary(SuspiciousActivityReport $sar): array;

    /**
     * Find SAR by ID
     * 
     * @param string $sarId SAR ID
     * @return SuspiciousActivityReport Found SAR
     * 
     * @throws \Nexus\AmlCompliance\Exceptions\SarGenerationFailedException If not found
     */
    public function findById(string $sarId): SuspiciousActivityReport;

    /**
     * Check if SAR exists
     * 
     * @param string $sarId SAR ID
     * @return bool True if exists
     */
    public function exists(string $sarId): bool;

    /**
     * Get SAR metrics
     * 
     * Returns metrics about SAR filings including counts by status,
     * overdue SARs, and filing statistics.
     *
     * @param \DateTimeImmutable|null $fromDate Start date for metrics (default: 30 days ago)
     * @param \DateTimeImmutable|null $toDate End date for metrics (default: now)
     * @return array<string, mixed> Metrics data including:
     *         - total_count: int
     *         - by_status: array<string, int>
     *         - overdue_count: int
     *         - filed_count: int
     *         - average_resolution_days: float
     */
    public function getSarMetrics(
        ?\DateTimeImmutable $fromDate = null,
        ?\DateTimeImmutable $toDate = null
    ): array;

    /**
     * Get SAR by party ID
     * 
     * Retrieves the most recent SAR associated with a party.
     *
     * @param string $partyId Party ID to search for
     * @return SuspiciousActivityReport|null The SAR if found, null otherwise
     */
    public function getSarByPartyId(string $partyId): ?SuspiciousActivityReport;
}

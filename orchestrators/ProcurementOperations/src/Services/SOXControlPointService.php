<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

use Nexus\ProcurementOperations\Contracts\SOXControlServiceInterface;
use Nexus\ProcurementOperations\Contracts\SOXPerformanceMonitorInterface;
use Nexus\ProcurementOperations\DTOs\SOX\SOXControlValidationRequest;
use Nexus\ProcurementOperations\DTOs\SOX\SOXControlValidationResponse;
use Nexus\ProcurementOperations\DTOs\SOX\SOXControlValidationResult;
use Nexus\ProcurementOperations\DTOs\SOX\SOXOverrideRequest;
use Nexus\ProcurementOperations\DTOs\SOX\SOXOverrideResult;
use Nexus\ProcurementOperations\Enums\P2PStep;
use Nexus\ProcurementOperations\Enums\SOXControlPoint;
use Nexus\ProcurementOperations\Enums\SOXControlResult;
use Nexus\ProcurementOperations\Events\SOX\SOXControlFailedEvent;
use Nexus\ProcurementOperations\Events\SOX\SOXControlPassedEvent;
use Nexus\ProcurementOperations\Events\SOX\SOXControlTimeoutEvent;
use Nexus\ProcurementOperations\Events\SOX\SOXExemptionApprovedEvent;
use Nexus\ProcurementOperations\Events\SOX\SOXExemptionRejectedEvent;
use Nexus\ProcurementOperations\Events\SOX\SOXExemptionRequestedEvent;
use Nexus\Setting\Contracts\SettingsManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * SOX Control Point Service.
 *
 * Validates procurement transactions against SOX control points
 * with support for performance monitoring, risk-based opt-out,
 * and manager-approved exemptions.
 *
 * @see GAP_ANALYSIS: "STEP 1: SOX Compliance Foundation"
 */
final readonly class SOXControlPointService implements SOXControlServiceInterface
{
    private const DEFAULT_TIMEOUT_MS = 200.0;
    private const DEFAULT_SOX_ENABLED = true;

    /**
     * @var array<string, array{expiry: \DateTimeImmutable, approver: string, controlPoints: array<string>}>
     */
    private array $overrideCache;

    public function __construct(
        private SOXPerformanceMonitorInterface $performanceMonitor,
        private SettingsManagerInterface $settings,
        private EventDispatcherInterface $eventDispatcher,
        private SOXOverrideStorageInterface $overrideStorage,
        private SODValidationServiceInterface $sodValidator,
        private LoggerInterface $logger = new NullLogger(),
    ) {
        $this->overrideCache = [];
    }

    /**
     * {@inheritdoc}
     */
    public function validate(SOXControlValidationRequest $request): SOXControlValidationResponse
    {
        $startTime = microtime(true);

        // Check if SOX is enabled for this tenant
        if (!$this->isSOXComplianceEnabled($request->tenantId)) {
            return SOXControlValidationResponse::skipped(
                $request->entityId,
                'SOX compliance disabled for tenant',
            );
        }

        // Get applicable control points for this P2P step
        $controlPoints = $this->getControlPointsForStep($request->step);

        if (empty($controlPoints)) {
            return SOXControlValidationResponse::skipped(
                $request->entityId,
                'No SOX controls applicable for this step',
            );
        }

        $results = [];
        $overallPassed = true;
        $blockedByControls = [];

        foreach ($controlPoints as $controlPoint) {
            $controlResult = $this->validateControl(
                $request->tenantId,
                $controlPoint,
                $request->entityType,
                $request->entityId,
                $request->userId,
                $request->context,
            );

            $results[] = $controlResult;

            if (!$controlResult->result->allowsProceeding()) {
                $overallPassed = false;
                $blockedByControls[] = $controlPoint->value;
            }
        }

        $durationMs = (microtime(true) - $startTime) * 1000;

        // Check for overrides that may allow proceeding
        $activeOverride = $this->getActiveOverride(
            $request->tenantId,
            $request->entityType,
            $request->entityId,
        );

        if (!$overallPassed && $activeOverride !== null) {
            // Override exists - check if it covers the blocked controls
            $overriddenControls = $activeOverride['controlPoints'];
            $uncoveredControls = array_diff($blockedByControls, $overriddenControls);

            if (empty($uncoveredControls)) {
                // All blocked controls are overridden
                return SOXControlValidationResponse::passedWithOverride(
                    entityId: $request->entityId,
                    results: $results,
                    durationMs: $durationMs,
                    overrideApprover: $activeOverride['approver'],
                );
            }
        }

        return $overallPassed
            ? SOXControlValidationResponse::passed(
                entityId: $request->entityId,
                results: $results,
                durationMs: $durationMs,
            )
            : SOXControlValidationResponse::failed(
                entityId: $request->entityId,
                results: $results,
                failedControls: $blockedByControls,
                durationMs: $durationMs,
            );
    }

    /**
     * {@inheritdoc}
     */
    public function validateControl(
        string $tenantId,
        SOXControlPoint $controlPoint,
        string $entityType,
        string $entityId,
        string $userId,
        array $context = [],
    ): SOXControlValidationResult {
        $startTime = microtime(true);
        $timeoutMs = $this->getTimeoutMs($tenantId);

        try {
            // Execute the actual validation logic
            $validationResult = $this->executeControlValidation(
                $tenantId,
                $controlPoint,
                $entityType,
                $entityId,
                $userId,
                $context,
            );

            $durationMs = (microtime(true) - $startTime) * 1000;

            // Check for timeout
            if ($durationMs > $timeoutMs) {
                $this->handleTimeout(
                    $tenantId,
                    $controlPoint,
                    $entityType,
                    $entityId,
                    $durationMs,
                    $timeoutMs,
                    $userId,
                    $context,
                );

                return SOXControlValidationResult::timeout(
                    $controlPoint,
                    $durationMs,
                    "Validation exceeded timeout of {$timeoutMs}ms",
                );
            }

            // Record metrics
            $this->performanceMonitor->recordValidation(
                $tenantId,
                $controlPoint,
                $validationResult->result === SOXControlResult::PASSED,
                $durationMs,
                ['entity_type' => $entityType, 'entity_id' => $entityId],
            );

            // Dispatch events
            $this->dispatchValidationEvent(
                $validationResult,
                $tenantId,
                $controlPoint,
                $entityType,
                $entityId,
                $userId,
                $durationMs,
                $context,
            );

            return $validationResult;

        } catch (\Throwable $e) {
            $durationMs = (microtime(true) - $startTime) * 1000;

            $this->performanceMonitor->recordError(
                $tenantId,
                $controlPoint,
                $e->getMessage(),
            );

            $this->logger->error('SOX control validation error', [
                'tenant_id' => $tenantId,
                'control_point' => $controlPoint->value,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'error' => $e->getMessage(),
            ]);

            return SOXControlValidationResult::error(
                $controlPoint,
                $e->getMessage(),
                $durationMs,
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function requestOverride(SOXOverrideRequest $request): SOXOverrideResult
    {
        // Validate SOD - requester cannot approve their own override
        if ($request->requestedByUserId === $request->managerUserId) {
            return SOXOverrideResult::denied(
                $request->exemptionId,
                'Segregation of duties violation: cannot self-approve override',
            );
        }

        // Check if override is allowed for this control point
        $controlPoint = SOXControlPoint::from($request->affectedControlPoints[0] ?? '');
        if ($controlPoint->getRiskLevel() >= 5) {
            return SOXOverrideResult::denied(
                $request->exemptionId,
                'Override not permitted for critical (risk level 5) controls',
            );
        }

        // Store pending override request
        $this->overrideStorage->storePending($request);

        // Dispatch event
        $event = SOXExemptionRequestedEvent::forMultipleControlPoints(
            tenantId: $request->tenantId,
            exemptionId: $request->exemptionId,
            controlPoints: array_map(
                fn(string $cp) => SOXControlPoint::from($cp),
                $request->affectedControlPoints,
            ),
            requestedByUserId: $request->requestedByUserId,
            entityType: $request->entityType,
            entityId: $request->entityId,
            justification: $request->justification,
            managerUserId: $request->managerUserId,
            hoursToExpire: $request->hoursUntilExpiry,
            context: $request->context,
        );

        $this->eventDispatcher->dispatch($event);

        $this->logger->info('SOX override requested', [
            'exemption_id' => $request->exemptionId,
            'tenant_id' => $request->tenantId,
            'control_points' => $request->affectedControlPoints,
            'requested_by' => $request->requestedByUserId,
        ]);

        return SOXOverrideResult::pending(
            $request->exemptionId,
            $request->managerUserId ?? 'No manager assigned',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function approveOverride(
        string $tenantId,
        string $exemptionId,
        string $approverUserId,
        ?string $comments = null,
    ): SOXOverrideResult {
        $pendingRequest = $this->overrideStorage->findPending($exemptionId);

        if ($pendingRequest === null) {
            return SOXOverrideResult::denied(
                $exemptionId,
                'Override request not found or already processed',
            );
        }

        // Validate SOD - approver must be different from requester
        if ($pendingRequest->requestedByUserId === $approverUserId) {
            return SOXOverrideResult::denied(
                $exemptionId,
                'Segregation of duties violation: requester cannot approve own request',
            );
        }

        // Check approver authority
        if (!$this->hasApprovalAuthority($tenantId, $approverUserId, $pendingRequest)) {
            return SOXOverrideResult::denied(
                $exemptionId,
                'Approver does not have authority for this override level',
            );
        }

        // Store approved override
        $expiresAt = $pendingRequest->hoursUntilExpiry
            ? (new \DateTimeImmutable())->modify("+{$pendingRequest->hoursUntilExpiry} hours")
            : null;

        $this->overrideStorage->storeApproved(
            $exemptionId,
            $tenantId,
            $pendingRequest->entityType,
            $pendingRequest->entityId,
            $pendingRequest->affectedControlPoints,
            $approverUserId,
            $expiresAt,
        );

        // Remove from pending
        $this->overrideStorage->removePending($exemptionId);

        // Dispatch event
        $event = SOXExemptionApprovedEvent::create(
            tenantId: $tenantId,
            exemptionId: $exemptionId,
            approvedByUserId: $approverUserId,
            requestedByUserId: $pendingRequest->requestedByUserId,
            entityType: $pendingRequest->entityType,
            entityId: $pendingRequest->entityId,
            affectedControlPoints: $pendingRequest->affectedControlPoints,
            approverComments: $comments,
            hoursUntilExpiry: $pendingRequest->hoursUntilExpiry,
            context: $pendingRequest->context,
        );

        $this->eventDispatcher->dispatch($event);

        $this->logger->info('SOX override approved', [
            'exemption_id' => $exemptionId,
            'tenant_id' => $tenantId,
            'approved_by' => $approverUserId,
        ]);

        return SOXOverrideResult::approved(
            $exemptionId,
            $approverUserId,
            $expiresAt,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function rejectOverride(
        string $tenantId,
        string $exemptionId,
        string $rejectorUserId,
        string $reason,
    ): SOXOverrideResult {
        $pendingRequest = $this->overrideStorage->findPending($exemptionId);

        if ($pendingRequest === null) {
            return SOXOverrideResult::denied(
                $exemptionId,
                'Override request not found or already processed',
            );
        }

        // Remove from pending
        $this->overrideStorage->removePending($exemptionId);

        // Dispatch event
        $event = SOXExemptionRejectedEvent::create(
            tenantId: $tenantId,
            exemptionId: $exemptionId,
            rejectedByUserId: $rejectorUserId,
            requestedByUserId: $pendingRequest->requestedByUserId,
            entityType: $pendingRequest->entityType,
            entityId: $pendingRequest->entityId,
            affectedControlPoints: $pendingRequest->affectedControlPoints,
            rejectionReason: $reason,
            context: $pendingRequest->context,
        );

        $this->eventDispatcher->dispatch($event);

        $this->logger->info('SOX override rejected', [
            'exemption_id' => $exemptionId,
            'tenant_id' => $tenantId,
            'rejected_by' => $rejectorUserId,
            'reason' => $reason,
        ]);

        return SOXOverrideResult::denied($exemptionId, $reason);
    }

    /**
     * {@inheritdoc}
     */
    public function isSOXComplianceEnabled(string $tenantId): bool
    {
        return (bool) $this->settings->get(
            "tenant.{$tenantId}.sox.compliance_enabled",
            self::DEFAULT_SOX_ENABLED,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPendingOverrides(string $tenantId): array
    {
        return $this->overrideStorage->findPendingByTenant($tenantId);
    }

    /**
     * {@inheritdoc}
     */
    public function getOverrideHistory(
        string $tenantId,
        string $entityType,
        string $entityId,
    ): array {
        return $this->overrideStorage->findHistory($tenantId, $entityType, $entityId);
    }

    /**
     * Get control points applicable for a P2P step.
     *
     * @return array<SOXControlPoint>
     */
    private function getControlPointsForStep(P2PStep $step): array
    {
        return array_filter(
            SOXControlPoint::cases(),
            fn(SOXControlPoint $cp) => $cp->getP2PStep() === $step,
        );
    }

    /**
     * Execute the actual control validation logic.
     *
     * @param array<string, mixed> $context
     */
    private function executeControlValidation(
        string $tenantId,
        SOXControlPoint $controlPoint,
        string $entityType,
        string $entityId,
        string $userId,
        array $context,
    ): SOXControlValidationResult {
        // Dispatch to specific control validators based on control point
        return match ($controlPoint) {
            // Requisition controls
            SOXControlPoint::REQ_BUDGET_CHECK => $this->validateBudgetCheck($tenantId, $entityId, $context),
            SOXControlPoint::REQ_APPROVAL_AUTHORITY => $this->validateApprovalAuthority($tenantId, $entityId, $userId, $context),
            SOXControlPoint::REQ_SOD_CHECK => $this->validateSOD($tenantId, $entityId, $userId, $context),

            // PO controls
            SOXControlPoint::PO_VENDOR_COMPLIANCE => $this->validateVendorCompliance($tenantId, $entityId, $context),
            SOXControlPoint::PO_PRICE_VARIANCE => $this->validatePriceVariance($tenantId, $entityId, $context),
            SOXControlPoint::PO_SPEND_POLICY => $this->validateSpendPolicy($tenantId, $entityId, $context),
            SOXControlPoint::PO_DUPLICATE_CHECK => $this->validateDuplicateCheck($tenantId, $entityId, $context),
            SOXControlPoint::PO_CONTRACT_COMPLIANCE => $this->validateContractCompliance($tenantId, $entityId, $context),

            // GR controls
            SOXControlPoint::GR_QUANTITY_TOLERANCE => $this->validateQuantityTolerance($tenantId, $entityId, $context),
            SOXControlPoint::GR_QUALITY_INSPECTION => $this->validateQualityInspection($tenantId, $entityId, $context),
            SOXControlPoint::GR_RECEIVER_VALIDATION => $this->validateReceiverAuth($tenantId, $entityId, $userId, $context),

            // Invoice controls
            SOXControlPoint::INV_THREE_WAY_MATCH => $this->validateThreeWayMatch($tenantId, $entityId, $context),
            SOXControlPoint::INV_TAX_VALIDATION => $this->validateTaxCompliance($tenantId, $entityId, $context),
            SOXControlPoint::INV_DUPLICATE_DETECTION => $this->validateInvoiceDuplicate($tenantId, $entityId, $context),
            SOXControlPoint::INV_VENDOR_BANK_VERIFY => $this->validateVendorBankDetails($tenantId, $entityId, $context),

            // Payment controls
            SOXControlPoint::PAY_DUAL_APPROVAL => $this->validateDualApproval($tenantId, $entityId, $userId, $context),
            SOXControlPoint::PAY_BANK_RECONCILIATION => $this->validateBankReconciliation($tenantId, $entityId, $context),
            SOXControlPoint::PAY_FRAUD_SCREENING => $this->validateFraudScreening($tenantId, $entityId, $context),
            SOXControlPoint::PAY_WITHHOLDING_TAX => $this->validateWithholdingTax($tenantId, $entityId, $context),

            // Default for any unmapped controls
            default => SOXControlValidationResult::skipped(
                $controlPoint,
                'Control validation not implemented',
            ),
        };
    }

    // Control validation implementations follow...
    // Each returns SOXControlValidationResult

    /**
     * @param array<string, mixed> $context
     */
    private function validateBudgetCheck(string $tenantId, string $entityId, array $context): SOXControlValidationResult
    {
        // TODO: Integrate with Nexus\Budget
        $budgetAvailable = $context['budget_available'] ?? true;

        if (!$budgetAvailable) {
            return SOXControlValidationResult::failed(
                SOXControlPoint::REQ_BUDGET_CHECK,
                ['Insufficient budget for requisition'],
            );
        }

        return SOXControlValidationResult::passed(SOXControlPoint::REQ_BUDGET_CHECK);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function validateApprovalAuthority(string $tenantId, string $entityId, string $userId, array $context): SOXControlValidationResult
    {
        $requiredLevel = $context['required_approval_level'] ?? 1;
        $userLevel = $context['user_approval_level'] ?? 0;

        if ($userLevel < $requiredLevel) {
            return SOXControlValidationResult::failed(
                SOXControlPoint::REQ_APPROVAL_AUTHORITY,
                ["User approval level {$userLevel} insufficient (requires {$requiredLevel})"],
            );
        }

        return SOXControlValidationResult::passed(SOXControlPoint::REQ_APPROVAL_AUTHORITY);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function validateSOD(string $tenantId, string $entityId, string $userId, array $context): SOXControlValidationResult
    {
        $sodResult = $this->sodValidator->validate($tenantId, $entityId, $userId, $context);

        if (!$sodResult->isPassed()) {
            return SOXControlValidationResult::failed(
                SOXControlPoint::REQ_SOD_CHECK,
                $sodResult->getViolations(),
            );
        }

        return SOXControlValidationResult::passed(SOXControlPoint::REQ_SOD_CHECK);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function validateVendorCompliance(string $tenantId, string $entityId, array $context): SOXControlValidationResult
    {
        $vendorStatus = $context['vendor_status'] ?? 'active';
        $vendorOnHold = $context['vendor_on_hold'] ?? false;

        if ($vendorOnHold || $vendorStatus !== 'active') {
            return SOXControlValidationResult::failed(
                SOXControlPoint::PO_VENDOR_COMPLIANCE,
                ['Vendor is on hold or inactive'],
            );
        }

        return SOXControlValidationResult::passed(SOXControlPoint::PO_VENDOR_COMPLIANCE);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function validatePriceVariance(string $tenantId, string $entityId, array $context): SOXControlValidationResult
    {
        $variance = $context['price_variance_percent'] ?? 0.0;
        $threshold = $context['variance_threshold'] ?? 10.0;

        if (abs($variance) > $threshold) {
            return SOXControlValidationResult::failed(
                SOXControlPoint::PO_PRICE_VARIANCE,
                [sprintf('Price variance %.2f%% exceeds threshold %.2f%%', $variance, $threshold)],
            );
        }

        return SOXControlValidationResult::passed(SOXControlPoint::PO_PRICE_VARIANCE);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function validateSpendPolicy(string $tenantId, string $entityId, array $context): SOXControlValidationResult
    {
        // TODO: Integrate with SpendPolicyCoordinator
        $policyCompliant = $context['spend_policy_compliant'] ?? true;

        if (!$policyCompliant) {
            return SOXControlValidationResult::failed(
                SOXControlPoint::PO_SPEND_POLICY,
                ['Transaction violates spend policy'],
            );
        }

        return SOXControlValidationResult::passed(SOXControlPoint::PO_SPEND_POLICY);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function validateDuplicateCheck(string $tenantId, string $entityId, array $context): SOXControlValidationResult
    {
        $isDuplicate = $context['is_potential_duplicate'] ?? false;

        if ($isDuplicate) {
            return SOXControlValidationResult::failed(
                SOXControlPoint::PO_DUPLICATE_CHECK,
                ['Potential duplicate PO detected'],
            );
        }

        return SOXControlValidationResult::passed(SOXControlPoint::PO_DUPLICATE_CHECK);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function validateContractCompliance(string $tenantId, string $entityId, array $context): SOXControlValidationResult
    {
        $withinContractTerms = $context['within_contract_terms'] ?? true;

        if (!$withinContractTerms) {
            return SOXControlValidationResult::failed(
                SOXControlPoint::PO_CONTRACT_COMPLIANCE,
                ['PO exceeds contract terms or limits'],
            );
        }

        return SOXControlValidationResult::passed(SOXControlPoint::PO_CONTRACT_COMPLIANCE);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function validateQuantityTolerance(string $tenantId, string $entityId, array $context): SOXControlValidationResult
    {
        $receivedQty = $context['received_quantity'] ?? 0.0;
        $orderedQty = $context['ordered_quantity'] ?? 0.0;
        $tolerance = $context['quantity_tolerance_percent'] ?? 5.0;

        if ($orderedQty > 0) {
            $variance = (($receivedQty - $orderedQty) / $orderedQty) * 100;
            if (abs($variance) > $tolerance) {
                return SOXControlValidationResult::failed(
                    SOXControlPoint::GR_QUANTITY_TOLERANCE,
                    [sprintf('Quantity variance %.2f%% exceeds tolerance %.2f%%', $variance, $tolerance)],
                );
            }
        }

        return SOXControlValidationResult::passed(SOXControlPoint::GR_QUANTITY_TOLERANCE);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function validateQualityInspection(string $tenantId, string $entityId, array $context): SOXControlValidationResult
    {
        $inspectionRequired = $context['quality_inspection_required'] ?? false;
        $inspectionPassed = $context['quality_inspection_passed'] ?? true;

        if ($inspectionRequired && !$inspectionPassed) {
            return SOXControlValidationResult::failed(
                SOXControlPoint::GR_QUALITY_INSPECTION,
                ['Quality inspection failed or not completed'],
            );
        }

        return SOXControlValidationResult::passed(SOXControlPoint::GR_QUALITY_INSPECTION);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function validateReceiverAuth(string $tenantId, string $entityId, string $userId, array $context): SOXControlValidationResult
    {
        $authorizedReceivers = $context['authorized_receivers'] ?? [];

        if (!empty($authorizedReceivers) && !in_array($userId, $authorizedReceivers, true)) {
            return SOXControlValidationResult::failed(
                SOXControlPoint::GR_RECEIVER_VALIDATION,
                ['User not authorized to receive goods'],
            );
        }

        return SOXControlValidationResult::passed(SOXControlPoint::GR_RECEIVER_VALIDATION);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function validateThreeWayMatch(string $tenantId, string $entityId, array $context): SOXControlValidationResult
    {
        $matchStatus = $context['three_way_match_status'] ?? 'pending';

        if ($matchStatus !== 'matched') {
            return SOXControlValidationResult::failed(
                SOXControlPoint::INV_THREE_WAY_MATCH,
                ["Three-way match status: {$matchStatus}"],
            );
        }

        return SOXControlValidationResult::passed(SOXControlPoint::INV_THREE_WAY_MATCH);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function validateTaxCompliance(string $tenantId, string $entityId, array $context): SOXControlValidationResult
    {
        $taxValid = $context['tax_validation_passed'] ?? true;

        if (!$taxValid) {
            return SOXControlValidationResult::failed(
                SOXControlPoint::INV_TAX_VALIDATION,
                ['Invoice tax validation failed'],
            );
        }

        return SOXControlValidationResult::passed(SOXControlPoint::INV_TAX_VALIDATION);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function validateInvoiceDuplicate(string $tenantId, string $entityId, array $context): SOXControlValidationResult
    {
        $isDuplicate = $context['is_duplicate_invoice'] ?? false;

        if ($isDuplicate) {
            return SOXControlValidationResult::failed(
                SOXControlPoint::INV_DUPLICATE_DETECTION,
                ['Duplicate invoice detected'],
            );
        }

        return SOXControlValidationResult::passed(SOXControlPoint::INV_DUPLICATE_DETECTION);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function validateVendorBankDetails(string $tenantId, string $entityId, array $context): SOXControlValidationResult
    {
        $bankVerified = $context['vendor_bank_verified'] ?? true;

        if (!$bankVerified) {
            return SOXControlValidationResult::failed(
                SOXControlPoint::INV_VENDOR_BANK_VERIFY,
                ['Vendor bank details not verified'],
            );
        }

        return SOXControlValidationResult::passed(SOXControlPoint::INV_VENDOR_BANK_VERIFY);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function validateDualApproval(string $tenantId, string $entityId, string $userId, array $context): SOXControlValidationResult
    {
        $approvalCount = $context['approval_count'] ?? 0;
        $requiredApprovals = $context['required_approvals'] ?? 2;

        if ($approvalCount < $requiredApprovals) {
            return SOXControlValidationResult::failed(
                SOXControlPoint::PAY_DUAL_APPROVAL,
                [sprintf('Insufficient approvals: %d of %d required', $approvalCount, $requiredApprovals)],
            );
        }

        return SOXControlValidationResult::passed(SOXControlPoint::PAY_DUAL_APPROVAL);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function validateBankReconciliation(string $tenantId, string $entityId, array $context): SOXControlValidationResult
    {
        // Typically checked post-payment, always passes at payment initiation
        return SOXControlValidationResult::passed(SOXControlPoint::PAY_BANK_RECONCILIATION);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function validateFraudScreening(string $tenantId, string $entityId, array $context): SOXControlValidationResult
    {
        $fraudScore = $context['fraud_risk_score'] ?? 0.0;
        $threshold = $context['fraud_threshold'] ?? 0.7;

        if ($fraudScore > $threshold) {
            return SOXControlValidationResult::failed(
                SOXControlPoint::PAY_FRAUD_SCREENING,
                [sprintf('Fraud risk score %.2f exceeds threshold %.2f', $fraudScore, $threshold)],
            );
        }

        return SOXControlValidationResult::passed(SOXControlPoint::PAY_FRAUD_SCREENING);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function validateWithholdingTax(string $tenantId, string $entityId, array $context): SOXControlValidationResult
    {
        $withholdingApplied = $context['withholding_tax_applied'] ?? true;
        $withholdingRequired = $context['withholding_tax_required'] ?? false;

        if ($withholdingRequired && !$withholdingApplied) {
            return SOXControlValidationResult::failed(
                SOXControlPoint::PAY_WITHHOLDING_TAX,
                ['Required withholding tax not applied'],
            );
        }

        return SOXControlValidationResult::passed(SOXControlPoint::PAY_WITHHOLDING_TAX);
    }

    /**
     * Handle validation timeout.
     *
     * @param array<string, mixed> $context
     */
    private function handleTimeout(
        string $tenantId,
        SOXControlPoint $controlPoint,
        string $entityType,
        string $entityId,
        float $durationMs,
        float $configuredTimeoutMs,
        string $userId,
        array $context,
    ): void {
        $this->performanceMonitor->recordTimeout($tenantId, $controlPoint, $durationMs);

        $event = SOXControlTimeoutEvent::create(
            tenantId: $tenantId,
            controlPoint: $controlPoint->value,
            entityType: $entityType,
            entityId: $entityId,
            timeoutDurationMs: $durationMs,
            configuredTimeoutMs: $configuredTimeoutMs,
            userId: $userId,
            context: $context,
        );

        $this->eventDispatcher->dispatch($event);

        $this->logger->warning('SOX control validation timeout', [
            'tenant_id' => $tenantId,
            'control_point' => $controlPoint->value,
            'duration_ms' => $durationMs,
            'timeout_ms' => $configuredTimeoutMs,
        ]);
    }

    /**
     * Dispatch appropriate event based on validation result.
     *
     * @param array<string, mixed> $context
     */
    private function dispatchValidationEvent(
        SOXControlValidationResult $result,
        string $tenantId,
        SOXControlPoint $controlPoint,
        string $entityType,
        string $entityId,
        string $userId,
        float $durationMs,
        array $context,
    ): void {
        if ($result->result === SOXControlResult::PASSED) {
            $event = SOXControlPassedEvent::create(
                tenantId: $tenantId,
                controlPoint: $controlPoint,
                entityType: $entityType,
                entityId: $entityId,
                userId: $userId,
                validationDurationMs: $durationMs,
                context: $context,
            );
        } else {
            $event = SOXControlFailedEvent::create(
                tenantId: $tenantId,
                controlPoint: $controlPoint,
                entityType: $entityType,
                entityId: $entityId,
                userId: $userId,
                failureReasons: $result->failureReasons,
                validationDurationMs: $durationMs,
                context: $context,
            );
        }

        $this->eventDispatcher->dispatch($event);
    }

    /**
     * Get active override for an entity.
     *
     * @return array{expiry: \DateTimeImmutable, approver: string, controlPoints: array<string>}|null
     */
    private function getActiveOverride(string $tenantId, string $entityType, string $entityId): ?array
    {
        return $this->overrideStorage->findActiveOverride($tenantId, $entityType, $entityId);
    }

    /**
     * Check if user has authority to approve override.
     */
    private function hasApprovalAuthority(string $tenantId, string $userId, SOXOverrideRequest $request): bool
    {
        // Check against configured approval hierarchy
        $requiredLevel = $this->getRequiredApprovalLevel($request);
        $userLevel = $this->getUserApprovalLevel($tenantId, $userId);

        return $userLevel >= $requiredLevel;
    }

    /**
     * Get required approval level based on override risk.
     */
    private function getRequiredApprovalLevel(SOXOverrideRequest $request): int
    {
        $maxRisk = 0;
        foreach ($request->affectedControlPoints as $cp) {
            $controlPoint = SOXControlPoint::from($cp);
            $maxRisk = max($maxRisk, $controlPoint->getRiskLevel());
        }

        // Risk 1-2: Level 1 approver, Risk 3-4: Level 2, Risk 5: Not overridable
        return match (true) {
            $maxRisk >= 5 => 999, // Effectively not approvable
            $maxRisk >= 3 => 2,
            default => 1,
        };
    }

    /**
     * Get user's approval level.
     */
    private function getUserApprovalLevel(string $tenantId, string $userId): int
    {
        return (int) $this->settings->get(
            "tenant.{$tenantId}.user.{$userId}.sox_approval_level",
            0,
        );
    }

    /**
     * Get configured timeout for SOX validations.
     */
    private function getTimeoutMs(string $tenantId): float
    {
        return (float) $this->settings->get(
            "tenant.{$tenantId}.sox.validation_timeout_ms",
            self::DEFAULT_TIMEOUT_MS,
        );
    }
}

/**
 * Interface for SOX override storage (to be implemented by adapter layer).
 */
interface SOXOverrideStorageInterface
{
    public function storePending(SOXOverrideRequest $request): void;

    public function findPending(string $exemptionId): ?SOXOverrideRequest;

    /**
     * @return array<SOXOverrideRequest>
     */
    public function findPendingByTenant(string $tenantId): array;

    public function removePending(string $exemptionId): void;

    /**
     * @param array<string> $controlPoints
     */
    public function storeApproved(
        string $exemptionId,
        string $tenantId,
        string $entityType,
        string $entityId,
        array $controlPoints,
        string $approverUserId,
        ?\DateTimeImmutable $expiresAt,
    ): void;

    /**
     * @return array{expiry: \DateTimeImmutable, approver: string, controlPoints: array<string>}|null
     */
    public function findActiveOverride(string $tenantId, string $entityType, string $entityId): ?array;

    /**
     * @return array<array<string, mixed>>
     */
    public function findHistory(string $tenantId, string $entityType, string $entityId): array;
}

/**
 * Interface for SOD validation (references existing SODValidationService).
 */
interface SODValidationServiceInterface
{
    /**
     * @param array<string, mixed> $context
     * @return object{isPassed(): bool, getViolations(): array<string>}
     */
    public function validate(string $tenantId, string $entityId, string $userId, array $context): object;
}

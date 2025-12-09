<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services\Approval;

use Nexus\ProcurementOperations\Contracts\ApprovalRoutingServiceInterface;
use Nexus\ProcurementOperations\Contracts\DelegationServiceInterface;
use Nexus\ProcurementOperations\DTOs\ApprovalRoutingRequest;
use Nexus\ProcurementOperations\DTOs\ApprovalRoutingResult;
use Nexus\ProcurementOperations\Enums\ApprovalLevel;
use Nexus\Setting\Services\SettingsManager;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Determines the approval routing chain for procurement documents.
 *
 * This service:
 * - Determines required approval level based on amount/category
 * - Resolves approvers from organizational hierarchy
 * - Applies delegation rules for unavailable approvers
 * - Reads thresholds from tenant-configurable settings
 *
 * Following Advanced Orchestrator Pattern v1.1:
 * - Service owns complex cross-boundary logic
 * - Integrates with Setting and Workflow packages
 * - Returns structured result for coordinator use
 */
final readonly class ApprovalRoutingService implements ApprovalRoutingServiceInterface
{
    /**
     * Setting keys for approval thresholds.
     */
    private const SETTING_THRESHOLD_L1 = 'procurement.approval.threshold_level_1_cents';
    private const SETTING_THRESHOLD_L2 = 'procurement.approval.threshold_level_2_cents';
    private const SETTING_THRESHOLD_L3 = 'procurement.approval.threshold_level_3_cents';
    private const SETTING_ESCALATION_HOURS = 'procurement.approval.escalation_timeout_hours';

    /**
     * Default thresholds (in cents).
     */
    private const DEFAULT_THRESHOLD_L1 = 500000;   // $5,000
    private const DEFAULT_THRESHOLD_L2 = 2500000;  // $25,000
    private const DEFAULT_THRESHOLD_L3 = 10000000; // $100,000
    private const DEFAULT_ESCALATION_HOURS = 48;

    public function __construct(
        private SettingsManager $settingsManager,
        private DelegationServiceInterface $delegationService,
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * Get logger instance.
     */
    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    /**
     * Determine the approval routing for a document.
     */
    public function determineRouting(ApprovalRoutingRequest $request): ApprovalRoutingResult
    {
        $this->getLogger()->info('Determining approval routing', [
            'tenant_id' => $request->tenantId,
            'document_id' => $request->documentId,
            'document_type' => $request->documentType,
            'amount_cents' => $request->amountCents,
        ]);

        // Load configured thresholds from settings
        $thresholds = $this->loadThresholds($request->tenantId);

        // Determine required approval level
        $requiredLevel = $this->determineApprovalLevel($request->amountCents, $thresholds);

        $this->getLogger()->debug('Approval level determined', [
            'required_level' => $requiredLevel->value,
            'level_label' => $requiredLevel->label(),
        ]);

        // Build approval chain
        $approvalChain = $this->buildApprovalChain(
            $request->tenantId,
            $request->requesterId,
            $request->departmentId,
            $requiredLevel,
            $request->documentType
        );

        if (empty($approvalChain)) {
            return ApprovalRoutingResult::failure(
                'Unable to determine approval chain: no approvers found for the required level'
            );
        }

        // Get escalation timeout from settings
        $escalationHours = $this->getEscalationTimeout($request->tenantId);

        return ApprovalRoutingResult::success(
            requiredLevel: $requiredLevel,
            approvalChain: $approvalChain,
            escalationTimeoutHours: $escalationHours,
            routingReason: [
                'amount_cents' => $request->amountCents,
                'threshold_applied' => $thresholds[$requiredLevel->value] ?? $requiredLevel->defaultThresholdCents(),
                'department_id' => $request->departmentId,
                'category' => $request->categoryCode,
            ]
        );
    }

    /**
     * Load approval thresholds from settings.
     *
     * @return array<int, int> Level => threshold in cents
     */
    private function loadThresholds(string $tenantId): array
    {
        return [
            ApprovalLevel::LEVEL_1->value => (int) $this->settingsManager->get(
                self::SETTING_THRESHOLD_L1,
                self::DEFAULT_THRESHOLD_L1,
                tenantId: $tenantId
            ),
            ApprovalLevel::LEVEL_2->value => (int) $this->settingsManager->get(
                self::SETTING_THRESHOLD_L2,
                self::DEFAULT_THRESHOLD_L2,
                tenantId: $tenantId
            ),
            ApprovalLevel::LEVEL_3->value => (int) $this->settingsManager->get(
                self::SETTING_THRESHOLD_L3,
                self::DEFAULT_THRESHOLD_L3,
                tenantId: $tenantId
            ),
        ];
    }

    /**
     * Determine the required approval level based on amount.
     */
    private function determineApprovalLevel(int $amountCents, array $thresholds): ApprovalLevel
    {
        // Level 1: Below L1 threshold
        if ($amountCents <= ($thresholds[ApprovalLevel::LEVEL_1->value] ?? self::DEFAULT_THRESHOLD_L1)) {
            return ApprovalLevel::LEVEL_1;
        }

        // Level 2: Below L2 threshold
        if ($amountCents <= ($thresholds[ApprovalLevel::LEVEL_2->value] ?? self::DEFAULT_THRESHOLD_L2)) {
            return ApprovalLevel::LEVEL_2;
        }

        // Level 3: Below L3 threshold or above (L3 is the max for most cases)
        if ($amountCents <= ($thresholds[ApprovalLevel::LEVEL_3->value] ?? self::DEFAULT_THRESHOLD_L3)) {
            return ApprovalLevel::LEVEL_3;
        }

        // Above all thresholds - requires highest level
        return ApprovalLevel::LEVEL_3;
    }

    /**
     * Build the approval chain for the required level.
     *
     * @return array<int, array{
     *     level: int,
     *     approverId: string,
     *     approverName: string,
     *     delegatedFrom: ?string,
     *     delegatedFromName: ?string,
     *     approvalLimit: int,
     *     role: string
     * }>
     */
    private function buildApprovalChain(
        string $tenantId,
        string $requesterId,
        string $departmentId,
        ApprovalLevel $requiredLevel,
        string $documentType
    ): array {
        $approvalChain = [];
        $taskType = $documentType . '_approval';

        // For each level up to required, find approver
        for ($level = 1; $level <= $requiredLevel->value; $level++) {
            $approverLevel = ApprovalLevel::from($level);

            // In a real implementation, this would query the organizational hierarchy
            // For now, we use placeholder logic that would be replaced by actual user lookup
            $baseApprover = $this->findApproverForLevel(
                $tenantId,
                $requesterId,
                $departmentId,
                $approverLevel
            );

            if ($baseApprover === null) {
                $this->getLogger()->warning('No approver found for level', [
                    'level' => $level,
                    'department_id' => $departmentId,
                ]);
                continue;
            }

            // Resolve delegation
            $resolvedApprover = $this->delegationService->resolveApprover(
                $tenantId,
                $baseApprover['approverId'],
                $taskType
            );

            $approvalChain[] = [
                'level' => $level,
                'approverId' => $resolvedApprover['actualApproverId'],
                'approverName' => $resolvedApprover['actualApproverName'] ?? $baseApprover['approverName'],
                'delegatedFrom' => $resolvedApprover['isDelegated'] ? $baseApprover['approverId'] : null,
                'delegatedFromName' => $resolvedApprover['isDelegated'] ? $baseApprover['approverName'] : null,
                'approvalLimit' => $baseApprover['approvalLimit'],
                'role' => $baseApprover['role'],
            ];
        }

        return $approvalChain;
    }

    /**
     * Find the approver for a specific level.
     *
     * This is a placeholder that would integrate with Identity/Party packages
     * to resolve the actual approver from organizational hierarchy.
     *
     * @return array{approverId: string, approverName: string, approvalLimit: int, role: string}|null
     */
    private function findApproverForLevel(
        string $tenantId,
        string $requesterId,
        string $departmentId,
        ApprovalLevel $level
    ): ?array {
        // In a real implementation, this would:
        // 1. Look up the requester's manager for Level 1
        // 2. Look up the department head for Level 2
        // 3. Look up the finance director for Level 3
        //
        // For now, return a placeholder that indicates the role needed.
        // The consuming application would bind this to actual user lookup.

        return match ($level) {
            ApprovalLevel::LEVEL_1 => [
                'approverId' => 'manager-placeholder-' . $requesterId,
                'approverName' => 'Direct Manager',
                'approvalLimit' => self::DEFAULT_THRESHOLD_L1,
                'role' => 'manager',
            ],
            ApprovalLevel::LEVEL_2 => [
                'approverId' => 'dept-head-placeholder-' . $departmentId,
                'approverName' => 'Department Head',
                'approvalLimit' => self::DEFAULT_THRESHOLD_L2,
                'role' => 'department_head',
            ],
            ApprovalLevel::LEVEL_3 => [
                'approverId' => 'finance-director-placeholder-' . $tenantId,
                'approverName' => 'Finance Director',
                'approvalLimit' => self::DEFAULT_THRESHOLD_L3,
                'role' => 'finance_director',
            ],
            default => null,
        };
    }

    /**
     * Get the escalation timeout from settings.
     */
    private function getEscalationTimeout(string $tenantId): int
    {
        return (int) $this->settingsManager->get(
            self::SETTING_ESCALATION_HOURS,
            self::DEFAULT_ESCALATION_HOURS,
            tenantId: $tenantId
        );
    }
}

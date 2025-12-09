<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DataProviders;

use Nexus\Budget\Contracts\BudgetManagerInterface;
use Nexus\ProcurementOperations\Contracts\ApprovalRoutingServiceInterface;
use Nexus\ProcurementOperations\Contracts\DelegationServiceInterface;
use Nexus\ProcurementOperations\DTOs\ApprovalChainContext;
use Nexus\ProcurementOperations\DTOs\ApprovalRoutingRequest;
use Nexus\ProcurementOperations\Enums\ApprovalLevel;
use Nexus\Common\ValueObjects\Money;
use Nexus\Setting\Services\SettingsManager;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Data provider for approval chain context.
 *
 * Aggregates data from multiple packages (Budget, Setting, Workflow)
 * to build the context needed for approval workflow decisions.
 *
 * Following Advanced Orchestrator Pattern v1.1:
 * - Abstracts data fetching from coordinator
 * - Returns typed DTO (ApprovalChainContext)
 * - Never manipulates data directly
 */
final readonly class ApprovalChainDataProvider
{
    /**
     * Setting keys.
     */
    private const SETTING_THRESHOLD_L1 = 'procurement.approval.threshold_level_1_cents';
    private const SETTING_THRESHOLD_L2 = 'procurement.approval.threshold_level_2_cents';
    private const SETTING_THRESHOLD_L3 = 'procurement.approval.threshold_level_3_cents';
    private const SETTING_ESCALATION_HOURS = 'procurement.approval.escalation_timeout_hours';

    public function __construct(
        private BudgetManagerInterface $budgetManager,
        private SettingsManager $settingsManager,
        private ApprovalRoutingServiceInterface $routingService,
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
     * Build approval chain context for a document.
     *
     * @param string $tenantId Tenant context
     * @param string $documentId Document ID
     * @param string $documentType Document type (requisition, purchase_order)
     * @param int $amountCents Amount in cents
     * @param string $currency Currency code
     * @param string $requesterId Requester user ID
     * @param string $departmentId Department ID
     * @param string|null $budgetId Budget ID for availability check
     * @param string|null $categoryCode Purchase category
     * @param array<string, mixed> $metadata Additional context
     */
    public function getContext(
        string $tenantId,
        string $documentId,
        string $documentType,
        int $amountCents,
        string $currency,
        string $requesterId,
        string $departmentId,
        ?string $budgetId = null,
        ?string $categoryCode = null,
        array $metadata = []
    ): ApprovalChainContext {
        $this->getLogger()->debug('Building approval chain context', [
            'tenant_id' => $tenantId,
            'document_id' => $documentId,
            'document_type' => $documentType,
            'amount_cents' => $amountCents,
        ]);

        // Load approval settings
        $approvalSettings = $this->loadApprovalSettings($tenantId, $categoryCode);

        // Determine required approval level
        $requiredLevel = ApprovalLevel::forAmount(
            $amountCents,
            $approvalSettings['configuredThresholds']
        );

        // Get routing result with resolved approvers
        $routingRequest = new ApprovalRoutingRequest(
            tenantId: $tenantId,
            documentId: $documentId,
            documentType: $documentType,
            amountCents: $amountCents,
            currency: $currency,
            requesterId: $requesterId,
            departmentId: $departmentId,
            categoryCode: $categoryCode,
            metadata: $metadata
        );

        $routingResult = $this->routingService->determineRouting($routingRequest);

        // Convert routing result to resolved approvers format
        $resolvedApprovers = [];
        foreach ($routingResult->approvalChain as $approver) {
            $resolvedApprovers[] = [
                'approverId' => $approver['approverId'],
                'approverName' => $approver['approverName'],
                'originalApproverId' => $approver['delegatedFrom'],
                'isDelegated' => $approver['delegatedFrom'] !== null,
                'spendLimitCents' => $approver['approvalLimit'] ?? null,
                'roles' => isset($approver['role']) ? [$approver['role']] : [],
            ];
        }

        // Get budget availability
        $budgetInfo = null;
        if ($budgetId !== null) {
            $budgetInfo = $this->getBudgetInfo($budgetId, $amountCents, $currency);
        }

        // Build context with category in approval settings
        $approvalSettings['category'] = $categoryCode;

        return new ApprovalChainContext(
            tenantId: $tenantId,
            documentId: $documentId,
            documentType: $documentType,
            amountCents: $amountCents,
            currency: $currency,
            requesterId: $requesterId,
            departmentId: $departmentId,
            requiredLevel: $requiredLevel,
            requesterInfo: null, // Would be populated by coordinator from Identity package
            budgetInfo: $budgetInfo,
            approvalSettings: $approvalSettings,
            resolvedApprovers: $resolvedApprovers,
        );
    }

    /**
     * Load approval settings from tenant configuration.
     *
     * @return array{
     *     configuredThresholds: array<int, int>,
     *     escalationHours: int,
     *     category: ?string
     * }
     */
    private function loadApprovalSettings(string $tenantId, ?string $categoryCode): array
    {
        $thresholds = [
            ApprovalLevel::LEVEL_1->value => (int) $this->settingsManager->get(
                self::SETTING_THRESHOLD_L1,
                ApprovalLevel::LEVEL_1->defaultThresholdCents(),
                tenantId: $tenantId
            ),
            ApprovalLevel::LEVEL_2->value => (int) $this->settingsManager->get(
                self::SETTING_THRESHOLD_L2,
                ApprovalLevel::LEVEL_2->defaultThresholdCents(),
                tenantId: $tenantId
            ),
            ApprovalLevel::LEVEL_3->value => (int) $this->settingsManager->get(
                self::SETTING_THRESHOLD_L3,
                ApprovalLevel::LEVEL_3->defaultThresholdCents(),
                tenantId: $tenantId
            ),
        ];

        $escalationHours = (int) $this->settingsManager->get(
            self::SETTING_ESCALATION_HOURS,
            48,
            tenantId: $tenantId
        );

        return [
            'configuredThresholds' => $thresholds,
            'escalationHours' => $escalationHours,
            'category' => $categoryCode,
        ];
    }

    /**
     * Get budget availability information.
     *
     * @return array{budgetId: string, availableCents: int, isAvailable: bool}|null
     */
    private function getBudgetInfo(string $budgetId, int $amountCents, string $currency): ?array
    {
        try {
            $money = new Money($amountCents, $currency);
            $availability = $this->budgetManager->checkAvailability($budgetId, $money);

            return [
                'budgetId' => $budgetId,
                'availableCents' => $availability->getAvailableAmount()->getAmountInCents(),
                'isAvailable' => $availability->isAvailable(),
            ];
        } catch (\Throwable $e) {
            $this->getLogger()->warning('Failed to check budget availability', [
                'budget_id' => $budgetId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}

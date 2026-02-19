<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Coordinators;

use Nexus\ComplianceOperations\Contracts\AmlScreeningAdapterInterface;
use Nexus\ComplianceOperations\Contracts\SanctionsCheckAdapterInterface;
use Nexus\ComplianceOperations\DTOs\SagaContext;
use Nexus\ComplianceOperations\Workflows\Monitoring\TransactionMonitoringWorkflow;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinates transaction monitoring and alert generation.
 *
 * This coordinator manages the real-time transaction monitoring process,
 * including transaction screening, risk threshold evaluation, alert
 * generation, and escalation handling.
 *
 * Following the Advanced Orchestrator Pattern:
 * - Coordinators direct flow, they do not execute business logic
 * - Delegates to workflows for stateful operations
 * - Uses adapters for external service integration
 *
 * @see ARCHITECTURE.md Section 3 for coordinator patterns
 */
final readonly class TransactionMonitoringCoordinator
{
    public function __construct(
        private TransactionMonitoringWorkflow $workflow,
        private AmlScreeningAdapterInterface $amlAdapter,
        private SanctionsCheckAdapterInterface $sanctionsAdapter,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Monitor a transaction for compliance violations.
     *
     * @param string $tenantId Tenant identifier
     * @param string $userId User initiating the monitoring
     * @param string $transactionId Transaction identifier
     * @param int $amountCents Transaction amount in cents
     * @param string $currency Transaction currency (ISO 4217)
     * @param string $partyId Initiating party identifier
     * @param array<string, mixed> $additionalContext Additional context data
     * @return array<string, mixed> Monitoring result
     */
    public function monitorTransaction(
        string $tenantId,
        string $userId,
        string $transactionId,
        int $amountCents,
        string $currency,
        string $partyId,
        array $additionalContext = []
    ): array {
        $this->logger->info('Initiating transaction monitoring', [
            'tenant_id' => $tenantId,
            'transaction_id' => $transactionId,
            'amount_cents' => $amountCents,
            'currency' => $currency,
        ]);

        try {
            // Build workflow context
            $contextData = array_merge([
                'transaction_id' => $transactionId,
                'transaction_amount' => $amountCents,
                'transaction_currency' => $currency,
                'party_id' => $partyId,
                'monitored_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ], $additionalContext);

            $sagaContext = new SagaContext(
                tenantId: $tenantId,
                userId: $userId,
                data: $contextData,
            );

            // Execute the monitoring workflow
            $sagaResult = $this->workflow->execute($sagaContext);

            return [
                'success' => $sagaResult->isSuccessful(),
                'saga_id' => $sagaResult->sagaId,
                'instance_id' => $sagaResult->instanceId,
                'transaction_id' => $transactionId,
                'status' => $sagaResult->status->value,
                'completed_steps' => $sagaResult->completedSteps,
                'failed_step' => $sagaResult->failedStep,
                'error_message' => $sagaResult->errorMessage,
                'message' => $sagaResult->isSuccessful()
                    ? 'Transaction monitoring completed successfully'
                    : 'Transaction monitoring failed',
                'data' => $sagaResult->data,
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to monitor transaction', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'transaction_id' => $transactionId,
                'status' => 'failed',
                'message' => 'Failed to monitor transaction',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get the current status of a monitoring workflow.
     *
     * @param string $instanceId Saga instance identifier
     * @return array<string, mixed> Workflow status
     */
    public function getMonitoringStatus(string $instanceId): array
    {
        $this->logger->info('Getting monitoring status', ['instance_id' => $instanceId]);

        try {
            $state = $this->workflow->getState($instanceId);

            if ($state === null) {
                return [
                    'success' => false,
                    'message' => 'Monitoring workflow not found',
                    'instance_id' => $instanceId,
                ];
            }

            return [
                'success' => true,
                'instance_id' => $instanceId,
                'saga_id' => $state->getSagaId(),
                'tenant_id' => $state->getTenantId(),
                'status' => $state->getStatus()->value,
                'completed_steps' => $state->getCompletedSteps(),
                'compensated_steps' => $state->getCompensatedSteps(),
                'context_data' => $state->getContextData(),
                'step_data' => $state->getStepData(),
                'error_message' => $state->getErrorMessage(),
                'is_terminal' => $state->isTerminal(),
                'created_at' => $state->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'updated_at' => $state->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to get monitoring status', [
                'instance_id' => $instanceId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'instance_id' => $instanceId,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Batch monitor multiple transactions.
     *
     * @param string $tenantId Tenant identifier
     * @param string $userId User initiating the monitoring
     * @param array<int, array<string, mixed>> $transactions Transactions to monitor
     * @return array<string, mixed> Batch monitoring result
     */
    public function batchMonitor(
        string $tenantId,
        string $userId,
        array $transactions
    ): array {
        $this->logger->info('Starting batch transaction monitoring', [
            'tenant_id' => $tenantId,
            'transaction_count' => count($transactions),
        ]);

        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($transactions as $transaction) {
            $transactionId = $transaction['transaction_id'] ?? 'unknown';

            try {
                $result = $this->monitorTransaction(
                    tenantId: $tenantId,
                    userId: $userId,
                    transactionId: $transactionId,
                    amountCents: $transaction['amount_cents'] ?? 0,
                    currency: $transaction['currency'] ?? 'USD',
                    partyId: $transaction['party_id'] ?? '',
                    additionalContext: $transaction['additional_context'] ?? []
                );

                $results[] = $result;

                if ($result['success']) {
                    $successCount++;
                } else {
                    $failureCount++;
                }
            } catch (\Throwable $e) {
                $results[] = [
                    'success' => false,
                    'transaction_id' => $transactionId,
                    'error' => $e->getMessage(),
                ];
                $failureCount++;
            }
        }

        return [
            'success' => $failureCount === 0,
            'tenant_id' => $tenantId,
            'total_transactions' => count($transactions),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results,
            'processed_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Evaluate transaction risk before processing.
     *
     * @param string $partyId Party identifier
     * @param int $amountCents Transaction amount in cents
     * @param string $currency Transaction currency
     * @param array<string, mixed> $partyData Party data for assessment
     * @return array<string, mixed> Risk evaluation result
     */
    public function evaluateTransactionRisk(
        string $partyId,
        int $amountCents,
        string $currency,
        array $partyData = []
    ): array {
        $this->logger->info('Evaluating transaction risk', [
            'party_id' => $partyId,
            'amount_cents' => $amountCents,
            'currency' => $currency,
        ]);

        $riskFactors = [];
        $totalRiskScore = 0;

        // Check AML risk
        $amlRiskLevel = $this->amlAdapter->getRiskLevel($partyId);
        if ($amlRiskLevel === 'high') {
            $riskFactors[] = 'Party has high AML risk level';
            $totalRiskScore += 30;
        } elseif ($amlRiskLevel === 'medium') {
            $riskFactors[] = 'Party has medium AML risk level';
            $totalRiskScore += 15;
        }

        // Check sanctions
        if ($this->sanctionsAdapter->hasMatches($partyId)) {
            $riskFactors[] = 'Party has sanctions matches';
            $totalRiskScore += 50;
        }

        // Check PEP status
        if ($this->sanctionsAdapter->isPep($partyId)) {
            $riskFactors[] = 'Party is a PEP';
            $totalRiskScore += 20;
        }

        // Check if EDD required
        if ($this->amlAdapter->requiresEdd($partyId)) {
            $riskFactors[] = 'Party requires enhanced due diligence';
            $totalRiskScore += 10;
        }

        // Determine risk level
        $riskLevel = 'low';
        if ($totalRiskScore >= 50) {
            $riskLevel = 'high';
        } elseif ($totalRiskScore >= 25) {
            $riskLevel = 'medium';
        }

        return [
            'party_id' => $partyId,
            'amount_cents' => $amountCents,
            'currency' => $currency,
            'risk_score' => $totalRiskScore,
            'risk_level' => $riskLevel,
            'risk_factors' => $riskFactors,
            'requires_review' => $totalRiskScore >= 25,
            'requires_manual_approval' => $totalRiskScore >= 50,
            'recommendations' => $this->amlAdapter->getRecommendations($partyId),
            'evaluated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Get monitoring statistics for a tenant.
     *
     * @param string $tenantId Tenant identifier
     * @param \DateTimeImmutable|null $fromDate Start date
     * @param \DateTimeImmutable|null $toDate End date
     * @return array<string, mixed> Monitoring statistics
     */
    public function getMonitoringStatistics(
        string $tenantId,
        ?\DateTimeImmutable $fromDate = null,
        ?\DateTimeImmutable $toDate = null
    ): array {
        $this->logger->info('Getting monitoring statistics', ['tenant_id' => $tenantId]);

        $fromDate ??= new \DateTimeImmutable('30 days ago');
        $toDate ??= new \DateTimeImmutable();

        // This would typically query a data provider for actual statistics
        return [
            'tenant_id' => $tenantId,
            'period' => [
                'from' => $fromDate->format(\DateTimeInterface::ATOM),
                'to' => $toDate->format(\DateTimeInterface::ATOM),
            ],
            'statistics' => [
                'total_transactions_monitored' => 0,
                'alerts_generated' => 0,
                'alerts_escalated' => 0,
                'false_positives' => 0,
                'pending_review' => 0,
            ],
            'generated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }
}

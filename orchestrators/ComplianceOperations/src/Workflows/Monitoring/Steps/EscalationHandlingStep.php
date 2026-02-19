<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Workflows\Monitoring\Steps;

use Nexus\ComplianceOperations\Contracts\SagaStepInterface;
use Nexus\ComplianceOperations\DTOs\SagaStepContext;
use Nexus\ComplianceOperations\DTOs\SagaStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Saga step: Escalation Handling.
 *
 * Forward action: Handles escalation of high-risk transactions to appropriate reviewers.
 * Compensation: Cancels escalation and notifies relevant parties.
 */
final readonly class EscalationHandlingStep implements SagaStepInterface
{
    public function __construct(
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * Get the logger instance, or a NullLogger if none was injected.
     */
    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    public function getId(): string
    {
        return 'escalation_handling';
    }

    public function getName(): string
    {
        return 'Escalation Handling';
    }

    public function getDescription(): string
    {
        return 'Handles escalation of high-risk transactions to appropriate reviewers';
    }

    public function execute(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Starting escalation handling', [
            'saga_instance_id' => $context->sagaInstanceId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            $transactionId = $context->get('transaction_id');

            if ($transactionId === null) {
                return SagaStepResult::failure(
                    errorMessage: 'Transaction ID is required for escalation handling',
                    canRetry: false,
                );
            }

            // Get alert generation results
            $alertResult = $context->getStepOutput('alert_generation');
            $thresholdResult = $context->getStepOutput('risk_threshold_evaluation');

            $alerts = $alertResult['alerts'] ?? [];
            $riskScore = $thresholdResult['risk_score'] ?? 0;
            $breachSeverity = $thresholdResult['breach_severity'] ?? 'low';

            // Determine escalation requirements
            $escalationRequired = $this->isEscalationRequired($alerts, $riskScore);
            $escalationLevel = $this->determineEscalationLevel($breachSeverity, $riskScore);
            $assignedReviewers = [];

            if ($escalationRequired) {
                $assignedReviewers = $this->assignReviewers($escalationLevel, $alerts);
            }

            $escalationResult = [
                'transaction_id' => $transactionId,
                'escalation_id' => sprintf('ESC-%s-%s', $transactionId, bin2hex(random_bytes(8))),
                'escalation_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'escalation_required' => $escalationRequired,
                'escalation_level' => $escalationLevel,
                'assigned_reviewers' => $assignedReviewers,
                'escalation_path' => $this->getEscalationPath($escalationLevel),
                'sla_hours' => $this->getSlaHours($escalationLevel),
                'notifications_sent' => $escalationRequired,
                'workflow_status' => $escalationRequired ? 'pending_review' : 'auto_approved',
                'auto_approval_reason' => !$escalationRequired ? 'No escalation criteria met' : null,
                'review_deadline' => $escalationRequired
                    ? (new \DateTimeImmutable())->modify('+' . $this->getSlaHours($escalationLevel) . ' hours')->format('Y-m-d H:i:s')
                    : null,
            ];

            $this->getLogger()->info('Escalation handling completed', [
                'transaction_id' => $transactionId,
                'escalation_id' => $escalationResult['escalation_id'],
                'escalation_required' => $escalationRequired,
                'escalation_level' => $escalationLevel,
            ]);

            return SagaStepResult::success($escalationResult);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Escalation handling failed', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::failure(
                errorMessage: 'Escalation handling failed: ' . $e->getMessage(),
                canRetry: true,
            );
        }
    }

    public function compensate(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Compensating: Cancelling escalation', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $escalationId = $context->getStepOutput('escalation_handling', 'escalation_id');

            if ($escalationId === null) {
                return SagaStepResult::compensated([
                    'message' => 'No escalation to cancel',
                ]);
            }

            $assignedReviewers = $context->getStepOutput('escalation_handling', 'assigned_reviewers') ?? [];

            // In production, this would cancel the escalation and notify reviewers
            $this->getLogger()->info('Escalation cancelled', [
                'escalation_id' => $escalationId,
                'notified_reviewers' => count($assignedReviewers),
            ]);

            return SagaStepResult::compensated([
                'cancelled_escalation_id' => $escalationId,
                'reason' => 'Transaction monitoring workflow compensation',
                'notified_reviewers' => array_column($assignedReviewers, 'reviewer_id'),
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to cancel escalation during compensation', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::compensationFailed(
                'Failed to cancel escalation: ' . $e->getMessage()
            );
        }
    }

    public function hasCompensation(): bool
    {
        return true;
    }

    public function getOrder(): int
    {
        return 4;
    }

    public function isRequired(): bool
    {
        return true;
    }

    public function getTimeout(): int
    {
        return 60; // 1 minute
    }

    public function getRetryAttempts(): int
    {
        return 3;
    }

    /**
     * Determine if escalation is required.
     *
     * @param array<array<string, mixed>> $alerts Generated alerts
     * @param int $riskScore Risk score
     * @return bool
     */
    private function isEscalationRequired(array $alerts, int $riskScore): bool
    {
        // Escalate if there are critical or high severity alerts
        foreach ($alerts as $alert) {
            if (in_array($alert['severity'] ?? '', ['critical', 'high'], true)) {
                return true;
            }
        }

        // Escalate if risk score is high
        if ($riskScore >= 50) {
            return true;
        }

        return false;
    }

    /**
     * Determine escalation level.
     *
     * @param string $breachSeverity Breach severity
     * @param int $riskScore Risk score
     * @return string
     */
    private function determineEscalationLevel(string $breachSeverity, int $riskScore): string
    {
        if ($breachSeverity === 'critical' || $riskScore >= 80) {
            return 'executive';
        }

        if ($breachSeverity === 'high' || $riskScore >= 60) {
            return 'senior_compliance';
        }

        if ($breachSeverity === 'medium' || $riskScore >= 40) {
            return 'compliance_analyst';
        }

        return 'auto';
    }

    /**
     * Assign reviewers based on escalation level.
     *
     * @param string $escalationLevel Escalation level
     * @param array<array<string, mixed>> $alerts Alerts
     * @return array<array<string, mixed>>
     */
    private function assignReviewers(string $escalationLevel, array $alerts): array
    {
        // In production, this would query actual reviewer assignments
        $reviewers = [];

        switch ($escalationLevel) {
            case 'executive':
                $reviewers[] = [
                    'reviewer_id' => 'chief_compliance_officer',
                    'role' => 'Chief Compliance Officer',
                    'primary' => true,
                ];
                $reviewers[] = [
                    'reviewer_id' => 'mlro',
                    'role' => 'Money Laundering Reporting Officer',
                    'primary' => false,
                ];
                break;

            case 'senior_compliance':
                $reviewers[] = [
                    'reviewer_id' => 'senior_compliance_manager',
                    'role' => 'Senior Compliance Manager',
                    'primary' => true,
                ];
                break;

            case 'compliance_analyst':
                $reviewers[] = [
                    'reviewer_id' => 'compliance_analyst_pool',
                    'role' => 'Compliance Analyst',
                    'primary' => true,
                ];
                break;
        }

        return $reviewers;
    }

    /**
     * Get escalation path for the level.
     *
     * @param string $escalationLevel Escalation level
     * @return array<string>
     */
    private function getEscalationPath(string $escalationLevel): array
    {
        $paths = [
            'executive' => ['compliance_analyst', 'senior_compliance', 'executive'],
            'senior_compliance' => ['compliance_analyst', 'senior_compliance'],
            'compliance_analyst' => ['compliance_analyst'],
            'auto' => [],
        ];

        return $paths[$escalationLevel] ?? [];
    }

    /**
     * Get SLA hours for the escalation level.
     *
     * @param string $escalationLevel Escalation level
     * @return int
     */
    private function getSlaHours(string $escalationLevel): int
    {
        $slas = [
            'executive' => 4,
            'senior_compliance' => 8,
            'compliance_analyst' => 24,
            'auto' => 0,
        ];

        return $slas[$escalationLevel] ?? 24;
    }
}

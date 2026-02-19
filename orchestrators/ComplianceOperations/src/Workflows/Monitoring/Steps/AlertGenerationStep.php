<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Workflows\Monitoring\Steps;

use Nexus\ComplianceOperations\Contracts\SagaStepInterface;
use Nexus\ComplianceOperations\DTOs\SagaStepContext;
use Nexus\ComplianceOperations\DTOs\SagaStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Saga step: Alert Generation.
 *
 * Forward action: Generates compliance alerts based on screening and threshold results.
 * Compensation: Dismisses generated alerts.
 */
final readonly class AlertGenerationStep implements SagaStepInterface
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
        return 'alert_generation';
    }

    public function getName(): string
    {
        return 'Alert Generation';
    }

    public function getDescription(): string
    {
        return 'Generates compliance alerts based on screening and threshold results';
    }

    public function execute(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Starting alert generation', [
            'saga_instance_id' => $context->sagaInstanceId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            $transactionId = $context->get('transaction_id');

            if ($transactionId === null) {
                return SagaStepResult::failure(
                    errorMessage: 'Transaction ID is required for alert generation',
                    canRetry: false,
                );
            }

            // Get previous step results
            $screeningResult = $context->getStepOutput('transaction_screening');
            $thresholdResult = $context->getStepOutput('risk_threshold_evaluation');

            // Determine if alerts should be generated
            $alerts = [];
            $alertRequired = false;

            // Check screening results
            if ($screeningResult !== null) {
                $sanctionsStatus = $screeningResult['sanctions_check']['status'] ?? 'clear';
                $amlStatus = $screeningResult['aml_patterns']['status'] ?? 'clear';
                $riskIndicators = $screeningResult['risk_indicators'] ?? [];

                if ($sanctionsStatus !== 'clear') {
                    $alertRequired = true;
                    $alerts[] = $this->createAlert(
                        type: 'sanctions_match',
                        severity: 'critical',
                        transactionId: $transactionId,
                        details: $screeningResult['sanctions_check']
                    );
                }

                if ($amlStatus !== 'clear') {
                    $alertRequired = true;
                    $alerts[] = $this->createAlert(
                        type: 'aml_pattern_detected',
                        severity: 'high',
                        transactionId: $transactionId,
                        details: $screeningResult['aml_patterns']
                    );
                }

                foreach ($riskIndicators as $indicator) {
                    $alertRequired = true;
                    $alerts[] = $this->createAlert(
                        type: 'risk_indicator',
                        severity: 'medium',
                        transactionId: $transactionId,
                        details: ['indicator' => $indicator]
                    );
                }
            }

            // Check threshold results
            if ($thresholdResult !== null) {
                $thresholdBreached = $thresholdResult['threshold_breached'] ?? false;
                $breachSeverity = $thresholdResult['breach_severity'] ?? 'low';
                $breaches = $thresholdResult['threshold_results']['breaches'] ?? [];
                $velocityFlags = $thresholdResult['velocity_metrics']['velocity_flags'] ?? [];

                if ($thresholdBreached) {
                    $alertRequired = true;
                    $alerts[] = $this->createAlert(
                        type: 'threshold_breach',
                        severity: $breachSeverity,
                        transactionId: $transactionId,
                        details: [
                            'breaches' => $breaches,
                            'velocity_flags' => $velocityFlags,
                        ]
                    );
                }
            }

            $generationResult = [
                'transaction_id' => $transactionId,
                'generation_id' => sprintf('ALG-%s-%s', $transactionId, bin2hex(random_bytes(8))),
                'generation_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'alert_required' => $alertRequired,
                'alerts_generated' => count($alerts),
                'alerts' => $alerts,
                'summary' => [
                    'critical_alerts' => count(array_filter($alerts, fn($a) => $a['severity'] === 'critical')),
                    'high_alerts' => count(array_filter($alerts, fn($a) => $a['severity'] === 'high')),
                    'medium_alerts' => count(array_filter($alerts, fn($a) => $a['severity'] === 'medium')),
                    'low_alerts' => count(array_filter($alerts, fn($a) => $a['severity'] === 'low')),
                ],
            ];

            $this->getLogger()->info('Alert generation completed', [
                'transaction_id' => $transactionId,
                'generation_id' => $generationResult['generation_id'],
                'alerts_generated' => $generationResult['alerts_generated'],
                'alert_required' => $alertRequired,
            ]);

            return SagaStepResult::success($generationResult);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Alert generation failed', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::failure(
                errorMessage: 'Alert generation failed: ' . $e->getMessage(),
                canRetry: true,
            );
        }
    }

    public function compensate(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Compensating: Dismissing generated alerts', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $alerts = $context->getStepOutput('alert_generation', 'alerts') ?? [];

            if (empty($alerts)) {
                return SagaStepResult::compensated([
                    'message' => 'No alerts to dismiss',
                ]);
            }

            $dismissedAlertIds = [];
            foreach ($alerts as $alert) {
                $dismissedAlertIds[] = $alert['alert_id'];
                // In production, this would dismiss the alert in the alerting system
            }

            $this->getLogger()->info('Alerts dismissed', [
                'dismissed_count' => count($dismissedAlertIds),
            ]);

            return SagaStepResult::compensated([
                'dismissed_alert_ids' => $dismissedAlertIds,
                'reason' => 'Transaction monitoring workflow compensation',
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to dismiss alerts during compensation', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::compensationFailed(
                'Failed to dismiss alerts: ' . $e->getMessage()
            );
        }
    }

    public function hasCompensation(): bool
    {
        return true;
    }

    public function getOrder(): int
    {
        return 3;
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
     * Create an alert structure.
     *
     * @param string $type Alert type
     * @param string $severity Alert severity
     * @param string $transactionId Transaction ID
     * @param array<string, mixed> $details Alert details
     * @return array<string, mixed>
     */
    private function createAlert(
        string $type,
        string $severity,
        string $transactionId,
        array $details
    ): array {
        return [
            'alert_id' => sprintf('ALT-%s-%s', $type, bin2hex(random_bytes(8))),
            'alert_type' => $type,
            'severity' => $severity,
            'transaction_id' => $transactionId,
            'status' => 'open',
            'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'details' => $details,
            'assigned_to' => null,
            'due_date' => $this->calculateDueDate($severity),
        ];
    }

    /**
     * Calculate due date based on severity.
     */
    private function calculateDueDate(string $severity): string
    {
        $hours = match ($severity) {
            'critical' => 4,
            'high' => 24,
            'medium' => 72,
            default => 168, // 1 week
        };

        return (new \DateTimeImmutable())
            ->modify("+{$hours} hours")
            ->format('Y-m-d H:i:s');
    }
}

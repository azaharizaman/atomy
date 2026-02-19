<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Workflows\Monitoring\Steps;

use Nexus\ComplianceOperations\Contracts\SagaStepInterface;
use Nexus\ComplianceOperations\DTOs\SagaStepContext;
use Nexus\ComplianceOperations\DTOs\SagaStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Saga step: Transaction Screening.
 *
 * Forward action: Screens transaction against AML patterns and sanctions lists.
 * Compensation: Clears screening results and flags transaction for re-screening.
 */
final readonly class TransactionScreeningStep implements SagaStepInterface
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
        return 'transaction_screening';
    }

    public function getName(): string
    {
        return 'Transaction Screening';
    }

    public function getDescription(): string
    {
        return 'Screens transaction against AML patterns and sanctions lists';
    }

    public function execute(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Starting transaction screening', [
            'saga_instance_id' => $context->sagaInstanceId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            $transactionId = $context->get('transaction_id');
            $transactionAmount = $context->get('transaction_amount', 0);
            $transactionCurrency = $context->get('transaction_currency', 'USD');
            $counterpartyId = $context->get('counterparty_id');
            $counterpartyCountry = $context->get('counterparty_country', 'US');

            if ($transactionId === null) {
                return SagaStepResult::failure(
                    errorMessage: 'Transaction ID is required for screening',
                    canRetry: false,
                );
            }

            // Simulate transaction screening process
            // In production, this would call the AmlCompliance package via adapter
            $screeningResult = [
                'transaction_id' => $transactionId,
                'screening_id' => sprintf('TXS-%s-%s', $transactionId, bin2hex(random_bytes(8))),
                'screening_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'amount' => $transactionAmount,
                'currency' => $transactionCurrency,
                'counterparty_id' => $counterpartyId,
                'counterparty_country' => $counterpartyCountry,
                'sanctions_check' => [
                    'status' => 'clear',
                    'lists_checked' => ['ofac', 'un', 'eu'],
                    'matches' => [],
                ],
                'aml_patterns' => [
                    'status' => 'clear',
                    'patterns_checked' => [
                        'structuring',
                        'rapid_movement',
                        'high_risk_jurisdiction',
                        'unusual_volume',
                    ],
                    'flags' => [],
                ],
                'overall_status' => 'clear',
                'risk_indicators' => $this->identifyRiskIndicators($transactionAmount, $counterpartyCountry),
            ];

            $this->getLogger()->info('Transaction screening completed', [
                'transaction_id' => $transactionId,
                'screening_id' => $screeningResult['screening_id'],
                'overall_status' => $screeningResult['overall_status'],
            ]);

            return SagaStepResult::success($screeningResult);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Transaction screening failed', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::failure(
                errorMessage: 'Transaction screening failed: ' . $e->getMessage(),
                canRetry: true,
            );
        }
    }

    public function compensate(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Compensating: Clearing transaction screening', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $screeningId = $context->getStepOutput('transaction_screening', 'screening_id');

            if ($screeningId === null) {
                return SagaStepResult::compensated([
                    'message' => 'No transaction screening to clear',
                ]);
            }

            // In production, this would mark the screening as voided
            $this->getLogger()->info('Transaction screening cleared', [
                'screening_id' => $screeningId,
            ]);

            return SagaStepResult::compensated([
                'voided_screening_id' => $screeningId,
                'reason' => 'Transaction monitoring workflow compensation',
                'requires_rescreening' => true,
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to clear transaction screening during compensation', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::compensationFailed(
                'Failed to clear transaction screening: ' . $e->getMessage()
            );
        }
    }

    public function hasCompensation(): bool
    {
        return true;
    }

    public function getOrder(): int
    {
        return 1;
    }

    public function isRequired(): bool
    {
        return true;
    }

    public function getTimeout(): int
    {
        return 120; // 2 minutes
    }

    public function getRetryAttempts(): int
    {
        return 3;
    }

    /**
     * Identify risk indicators based on transaction details.
     *
     * @return array<string>
     */
    private function identifyRiskIndicators(float $amount, string $country): array
    {
        $indicators = [];

        // High-risk countries
        $highRiskCountries = ['IR', 'KP', 'SY', 'CU', 'SD', 'MM', 'BY'];
        if (in_array($country, $highRiskCountries, true)) {
            $indicators[] = 'high_risk_jurisdiction';
        }

        // Large transaction threshold
        if ($amount > 10000) {
            $indicators[] = 'large_amount';
        }

        // Very large transaction
        if ($amount > 50000) {
            $indicators[] = 'very_large_amount';
        }

        return $indicators;
    }
}

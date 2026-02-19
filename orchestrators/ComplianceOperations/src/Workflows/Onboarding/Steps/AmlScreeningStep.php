<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Workflows\Onboarding\Steps;

use Nexus\ComplianceOperations\Contracts\SagaStepInterface;
use Nexus\ComplianceOperations\DTOs\SagaStepContext;
use Nexus\ComplianceOperations\DTOs\SagaStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Saga step: AML (Anti-Money Laundering) Screening.
 *
 * Forward action: Screens party against AML databases and performs risk evaluation.
 * Compensation: Clears screening results and flags for re-screening.
 */
final readonly class AmlScreeningStep implements SagaStepInterface
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
        return 'aml_screening';
    }

    public function getName(): string
    {
        return 'AML Screening';
    }

    public function getDescription(): string
    {
        return 'Screens party against AML databases and performs risk evaluation';
    }

    public function execute(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Starting AML screening', [
            'saga_instance_id' => $context->sagaInstanceId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            $partyId = $context->get('party_id');
            $partyName = $context->get('party_name', '');
            $countryCode = $context->get('country_code', 'US');

            if ($partyId === null) {
                return SagaStepResult::failure(
                    errorMessage: 'Party ID is required for AML screening',
                    canRetry: false,
                );
            }

            // Simulate AML screening process
            // In production, this would call the AmlCompliance package via adapter
            $screeningResult = [
                'party_id' => $partyId,
                'screening_id' => sprintf('AML-%s-%s', $partyId, bin2hex(random_bytes(8))),
                'status' => 'clear',
                'screening_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'databases_checked' => [
                    'fincen',
                    'ofac',
                    'eu_sanctions',
                    'interpol',
                ],
                'matches_found' => 0,
                'false_positive_matches' => 0,
                'risk_score' => $this->calculateRiskScore($countryCode),
                'risk_level' => 'low',
                'next_review_date' => (new \DateTimeImmutable())->modify('+1 year')->format('Y-m-d'),
            ];

            $this->getLogger()->info('AML screening completed', [
                'party_id' => $partyId,
                'screening_id' => $screeningResult['screening_id'],
                'risk_score' => $screeningResult['risk_score'],
            ]);

            return SagaStepResult::success($screeningResult);
        } catch (\Throwable $e) {
            $this->getLogger()->error('AML screening failed', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::failure(
                errorMessage: 'AML screening failed: ' . $e->getMessage(),
                canRetry: true,
            );
        }
    }

    public function compensate(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Compensating: Clearing AML screening results', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $screeningId = $context->getStepOutput('aml_screening', 'screening_id');

            if ($screeningId === null) {
                return SagaStepResult::compensated([
                    'message' => 'No AML screening to clear',
                ]);
            }

            // In production, this would mark the screening as voided and flag for re-screening
            $this->getLogger()->info('AML screening results cleared', [
                'screening_id' => $screeningId,
            ]);

            return SagaStepResult::compensated([
                'voided_screening_id' => $screeningId,
                'reason' => 'Onboarding workflow compensation',
                'requires_rescreening' => true,
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to clear AML screening during compensation', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::compensationFailed(
                'Failed to clear AML screening: ' . $e->getMessage()
            );
        }
    }

    public function hasCompensation(): bool
    {
        return true;
    }

    public function getOrder(): int
    {
        return 2;
    }

    public function isRequired(): bool
    {
        return true;
    }

    public function getTimeout(): int
    {
        return 300; // 5 minutes
    }

    public function getRetryAttempts(): int
    {
        return 3;
    }

    /**
     * Calculate risk score based on country and other factors.
     */
    private function calculateRiskScore(string $countryCode): int
    {
        // Simplified risk scoring - in production this would be more sophisticated
        $highRiskCountries = ['IR', 'KP', 'SY', 'CU', 'SD'];
        $mediumRiskCountries = ['AE', 'SA', 'QA', 'KW', 'BH'];

        if (in_array($countryCode, $highRiskCountries, true)) {
            return 85;
        }

        if (in_array($countryCode, $mediumRiskCountries, true)) {
            return 50;
        }

        return 15;
    }
}

<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Workflows\Onboarding\Steps;

use Nexus\ComplianceOperations\Contracts\SagaStepInterface;
use Nexus\ComplianceOperations\DTOs\SagaStepContext;
use Nexus\ComplianceOperations\DTOs\SagaStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Saga step: Sanctions Check.
 *
 * Forward action: Checks party against international sanctions lists and watchlists.
 * Compensation: Clears sanctions check results.
 */
final readonly class SanctionsCheckStep implements SagaStepInterface
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
        return 'sanctions_check';
    }

    public function getName(): string
    {
        return 'Sanctions Check';
    }

    public function getDescription(): string
    {
        return 'Checks party against international sanctions lists and watchlists';
    }

    public function execute(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Starting sanctions check', [
            'saga_instance_id' => $context->sagaInstanceId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            $partyId = $context->get('party_id');
            $partyName = $context->get('party_name', '');
            $partyType = $context->get('party_type', 'individual');

            if ($partyId === null) {
                return SagaStepResult::failure(
                    errorMessage: 'Party ID is required for sanctions check',
                    canRetry: false,
                );
            }

            // Simulate sanctions check process
            // In production, this would call the Sanctions package via adapter
            $checkResult = [
                'party_id' => $partyId,
                'check_id' => sprintf('SAN-%s-%s', $partyId, bin2hex(random_bytes(8))),
                'status' => 'clear',
                'check_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'lists_checked' => [
                    'ofac_sdnl' => 'Office of Foreign Assets Control - Specially Designated Nationals',
                    'un_security_council' => 'UN Security Council Consolidated List',
                    'eu_consolidated' => 'EU Consolidated Financial Sanctions List',
                    'hm_treasury' => 'UK HM Treasury Sanctions List',
                    'dfat' => 'Australia DFAT Sanctions List',
                ],
                'total_lists_checked' => 5,
                'matches' => [],
                'potential_matches' => 0,
                'confirmed_matches' => 0,
                'pep_status' => $this->checkPepStatus($partyType),
                'pep_details' => null,
                'requires_manual_review' => false,
            ];

            $this->getLogger()->info('Sanctions check completed', [
                'party_id' => $partyId,
                'check_id' => $checkResult['check_id'],
                'status' => $checkResult['status'],
            ]);

            return SagaStepResult::success($checkResult);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Sanctions check failed', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::failure(
                errorMessage: 'Sanctions check failed: ' . $e->getMessage(),
                canRetry: true,
            );
        }
    }

    public function compensate(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Compensating: Clearing sanctions check results', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $checkId = $context->getStepOutput('sanctions_check', 'check_id');

            if ($checkId === null) {
                return SagaStepResult::compensated([
                    'message' => 'No sanctions check to clear',
                ]);
            }

            // In production, this would mark the check as voided
            $this->getLogger()->info('Sanctions check results cleared', [
                'check_id' => $checkId,
            ]);

            return SagaStepResult::compensated([
                'voided_check_id' => $checkId,
                'reason' => 'Onboarding workflow compensation',
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to clear sanctions check during compensation', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::compensationFailed(
                'Failed to clear sanctions check: ' . $e->getMessage()
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
        return 300; // 5 minutes
    }

    public function getRetryAttempts(): int
    {
        return 3;
    }

    /**
     * Check PEP (Politically Exposed Person) status.
     */
    private function checkPepStatus(string $partyType): string
    {
        // Simplified PEP check - in production this would query actual PEP databases
        if ($partyType === 'government') {
            return 'requires_review';
        }

        return 'not_pep';
    }
}

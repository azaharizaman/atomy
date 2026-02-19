<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Workflows\Onboarding\Steps;

use Nexus\ComplianceOperations\Contracts\SagaStepInterface;
use Nexus\ComplianceOperations\DTOs\SagaStepContext;
use Nexus\ComplianceOperations\DTOs\SagaStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Saga step: KYC (Know Your Customer) Verification.
 *
 * Forward action: Verifies identity documents and performs biometric validation.
 * Compensation: Marks verification as void and logs the rollback.
 */
final readonly class KycVerificationStep implements SagaStepInterface
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
        return 'kyc_verification';
    }

    public function getName(): string
    {
        return 'KYC Verification';
    }

    public function getDescription(): string
    {
        return 'Verifies identity documents and performs biometric validation';
    }

    public function execute(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Starting KYC verification', [
            'saga_instance_id' => $context->sagaInstanceId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            $partyId = $context->get('party_id');
            $partyType = $context->get('party_type', 'customer');
            $documentReferences = $context->get('document_references', []);

            if ($partyId === null) {
                return SagaStepResult::failure(
                    errorMessage: 'Party ID is required for KYC verification',
                    canRetry: false,
                );
            }

            // Simulate KYC verification process
            // In production, this would call the KycVerification package via adapter
            $verificationResult = [
                'party_id' => $partyId,
                'party_type' => $partyType,
                'verification_id' => sprintf('KYC-%s-%s', $partyId, bin2hex(random_bytes(8))),
                'status' => 'verified',
                'verified_documents' => count($documentReferences),
                'verification_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'biometric_check' => $context->get('biometric_required', true) ? 'passed' : 'skipped',
                'risk_flags' => [],
            ];

            $this->getLogger()->info('KYC verification completed', [
                'party_id' => $partyId,
                'verification_id' => $verificationResult['verification_id'],
            ]);

            return SagaStepResult::success($verificationResult);
        } catch (\Throwable $e) {
            $this->getLogger()->error('KYC verification failed', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::failure(
                errorMessage: 'KYC verification failed: ' . $e->getMessage(),
                canRetry: true,
            );
        }
    }

    public function compensate(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Compensating: Voiding KYC verification', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $verificationId = $context->getStepOutput('kyc_verification', 'verification_id');

            if ($verificationId === null) {
                return SagaStepResult::compensated([
                    'message' => 'No KYC verification to void',
                ]);
            }

            // In production, this would mark the verification as voided
            $this->getLogger()->info('KYC verification voided', [
                'verification_id' => $verificationId,
            ]);

            return SagaStepResult::compensated([
                'voided_verification_id' => $verificationId,
                'reason' => 'Onboarding workflow compensation',
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to void KYC verification during compensation', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::compensationFailed(
                'Failed to void KYC verification: ' . $e->getMessage()
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
        return 600; // 10 minutes
    }

    public function getRetryAttempts(): int
    {
        return 3;
    }
}

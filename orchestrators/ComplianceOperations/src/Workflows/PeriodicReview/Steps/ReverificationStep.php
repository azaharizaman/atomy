<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Workflows\PeriodicReview\Steps;

use Nexus\ComplianceOperations\Contracts\SagaStepInterface;
use Nexus\ComplianceOperations\DTOs\SagaStepContext;
use Nexus\ComplianceOperations\DTOs\SagaStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Saga step: Reverification.
 *
 * Forward action: Re-verifies party identity and documents.
 * Compensation: Marks reverification as void.
 */
final readonly class ReverificationStep implements SagaStepInterface
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
        return 'reverification';
    }

    public function getName(): string
    {
        return 'Reverification';
    }

    public function getDescription(): string
    {
        return 'Re-verifies party identity and documents';
    }

    public function execute(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Starting reverification', [
            'saga_instance_id' => $context->sagaInstanceId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            $partyId = $context->get('party_id');

            if ($partyId === null) {
                return SagaStepResult::failure(
                    errorMessage: 'Party ID is required for reverification',
                    canRetry: false,
                );
            }

            // Get trigger results
            $triggerResult = $context->getStepOutput('review_trigger');
            $reviewType = $triggerResult['review_type'] ?? 'standard';
            $documentsRequired = $triggerResult['documents_required'] ?? [];

            // Perform reverification
            $verificationResults = $this->performReverification($partyId, $reviewType, $documentsRequired);

            $reverificationResult = [
                'party_id' => $partyId,
                'reverification_id' => sprintf('REV-%s-%s', $partyId, bin2hex(random_bytes(8))),
                'reverification_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'review_type' => $reviewType,
                'identity_verification' => $verificationResults['identity'],
                'document_verification' => $verificationResults['documents'],
                'address_verification' => $verificationResults['address'],
                'overall_status' => $verificationResults['overall_status'],
                'issues_found' => $verificationResults['issues'],
                'requires_action' => !empty($verificationResults['issues']),
                'documents_verified' => $verificationResults['documents_verified'],
                'documents_pending' => $verificationResults['documents_pending'],
            ];

            $this->getLogger()->info('Reverification completed', [
                'party_id' => $partyId,
                'reverification_id' => $reverificationResult['reverification_id'],
                'overall_status' => $reverificationResult['overall_status'],
            ]);

            return SagaStepResult::success($reverificationResult);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Reverification failed', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::failure(
                errorMessage: 'Reverification failed: ' . $e->getMessage(),
                canRetry: true,
            );
        }
    }

    public function compensate(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Compensating: Voiding reverification', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $reverificationId = $context->getStepOutput('reverification', 'reverification_id');

            if ($reverificationId === null) {
                return SagaStepResult::compensated([
                    'message' => 'No reverification to void',
                ]);
            }

            // In production, this would mark the reverification as voided
            $this->getLogger()->info('Reverification voided', [
                'reverification_id' => $reverificationId,
            ]);

            return SagaStepResult::compensated([
                'voided_reverification_id' => $reverificationId,
                'reason' => 'Periodic review workflow compensation',
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to void reverification during compensation', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::compensationFailed(
                'Failed to void reverification: ' . $e->getMessage()
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
     * Perform reverification checks.
     *
     * @param string $partyId Party ID
     * @param string $reviewType Review type
     * @param array<string> $documentsRequired Required documents
     * @return array<string, mixed>
     */
    private function performReverification(
        string $partyId,
        string $reviewType,
        array $documentsRequired
    ): array {
        // In production, this would call actual verification services
        $issues = [];
        $overallStatus = 'verified';

        // Identity verification
        $identityResult = [
            'status' => 'verified',
            'method' => 'document_review',
            'confidence_score' => 95,
            'last_verified' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        // Document verification
        $documentsVerified = 0;
        $documentsPending = 0;
        $documentResults = [];

        foreach ($documentsRequired as $docType) {
            // Simulate document verification
            $isVerified = rand(0, 10) > 2; // 80% pass rate

            $documentResults[$docType] = [
                'status' => $isVerified ? 'verified' : 'pending',
                'verified_at' => $isVerified ? (new \DateTimeImmutable())->format('Y-m-d H:i:s') : null,
                'expiry_date' => (new \DateTimeImmutable())->modify('+1 year')->format('Y-m-d'),
            ];

            if ($isVerified) {
                $documentsVerified++;
            } else {
                $documentsPending++;
                $issues[] = "Document {$docType} requires renewal";
            }
        }

        // Address verification
        $addressResult = [
            'status' => 'verified',
            'method' => 'database_check',
            'last_verified' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        // Determine overall status
        if ($documentsPending > 0) {
            $overallStatus = 'pending_documents';
        }

        if (count($issues) > 2) {
            $overallStatus = 'requires_attention';
        }

        return [
            'identity' => $identityResult,
            'documents' => $documentResults,
            'address' => $addressResult,
            'overall_status' => $overallStatus,
            'issues' => $issues,
            'documents_verified' => $documentsVerified,
            'documents_pending' => $documentsPending,
        ];
    }
}

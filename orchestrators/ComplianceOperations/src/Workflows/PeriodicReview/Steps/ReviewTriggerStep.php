<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Workflows\PeriodicReview\Steps;

use Nexus\ComplianceOperations\Contracts\SagaStepInterface;
use Nexus\ComplianceOperations\DTOs\SagaStepContext;
use Nexus\ComplianceOperations\DTOs\SagaStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Saga step: Review Trigger.
 *
 * Forward action: Triggers periodic review based on schedule or risk profile.
 * Compensation: Cancels the review trigger and logs the cancellation.
 */
final readonly class ReviewTriggerStep implements SagaStepInterface
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
        return 'review_trigger';
    }

    public function getName(): string
    {
        return 'Review Trigger';
    }

    public function getDescription(): string
    {
        return 'Triggers periodic review based on schedule or risk profile';
    }

    public function execute(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Starting review trigger', [
            'saga_instance_id' => $context->sagaInstanceId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            $partyId = $context->get('party_id');
            $partyType = $context->get('party_type', 'customer');
            $currentRiskLevel = $context->get('current_risk_level', 'low');
            $lastReviewDate = $context->get('last_review_date');

            if ($partyId === null) {
                return SagaStepResult::failure(
                    errorMessage: 'Party ID is required for review trigger',
                    canRetry: false,
                );
            }

            // Determine review type and scope
            $reviewType = $this->determineReviewType($currentRiskLevel);
            $reviewScope = $this->determineReviewScope($partyType, $currentRiskLevel);
            $documentsRequired = $this->getRequiredDocuments($partyType, $currentRiskLevel);

            $triggerResult = [
                'party_id' => $partyId,
                'party_type' => $partyType,
                'trigger_id' => sprintf('PRT-%s-%s', $partyId, bin2hex(random_bytes(8))),
                'trigger_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'review_type' => $reviewType,
                'review_scope' => $reviewScope,
                'trigger_reason' => $this->determineTriggerReason($currentRiskLevel, $lastReviewDate),
                'current_risk_level' => $currentRiskLevel,
                'documents_required' => $documentsRequired,
                'deadline' => $this->calculateDeadline($currentRiskLevel),
                'priority' => $this->determinePriority($currentRiskLevel),
                'assigned_to' => null, // Will be assigned in later steps
            ];

            $this->getLogger()->info('Review trigger completed', [
                'party_id' => $partyId,
                'trigger_id' => $triggerResult['trigger_id'],
                'review_type' => $reviewType,
            ]);

            return SagaStepResult::success($triggerResult);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Review trigger failed', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::failure(
                errorMessage: 'Review trigger failed: ' . $e->getMessage(),
                canRetry: true,
            );
        }
    }

    public function compensate(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Compensating: Cancelling review trigger', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $triggerId = $context->getStepOutput('review_trigger', 'trigger_id');

            if ($triggerId === null) {
                return SagaStepResult::compensated([
                    'message' => 'No review trigger to cancel',
                ]);
            }

            // In production, this would cancel the triggered review
            $this->getLogger()->info('Review trigger cancelled', [
                'trigger_id' => $triggerId,
            ]);

            return SagaStepResult::compensated([
                'cancelled_trigger_id' => $triggerId,
                'reason' => 'Periodic review workflow compensation',
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to cancel review trigger during compensation', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::compensationFailed(
                'Failed to cancel review trigger: ' . $e->getMessage()
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
        return 60; // 1 minute
    }

    public function getRetryAttempts(): int
    {
        return 3;
    }

    /**
     * Determine review type based on risk level.
     */
    private function determineReviewType(string $riskLevel): string
    {
        return match ($riskLevel) {
            'high' => 'enhanced',
            'medium' => 'standard',
            default => 'simplified',
        };
    }

    /**
     * Determine review scope based on party type and risk level.
     *
     * @return array<string>
     */
    private function determineReviewScope(string $partyType, string $riskLevel): array
    {
        $scope = ['identity_verification', 'risk_assessment'];

        if ($partyType === 'vendor') {
            $scope[] = 'beneficial_ownership';
        }

        if ($riskLevel === 'high') {
            $scope[] = 'source_of_funds';
            $scope[] = 'transaction_history_review';
        }

        return $scope;
    }

    /**
     * Get required documents for the review.
     *
     * @return array<string>
     */
    private function getRequiredDocuments(string $partyType, string $riskLevel): array
    {
        $documents = ['identification_document'];

        if ($partyType === 'vendor') {
            $documents[] = 'business_registration';
            $documents[] = 'tax_certificate';
        }

        if ($riskLevel === 'high') {
            $documents[] = 'proof_of_address';
            $documents[] = 'financial_statements';
        }

        return $documents;
    }

    /**
     * Determine trigger reason.
     */
    private function determineTriggerReason(string $riskLevel, ?string $lastReviewDate): string
    {
        if ($lastReviewDate === null) {
            return 'initial_review';
        }

        $lastReview = new \DateTimeImmutable($lastReviewDate);
        $now = new \DateTimeImmutable();
        $daysSinceReview = $lastReview->diff($now)->days;

        $intervals = [
            'high' => 90,
            'medium' => 180,
            'low' => 365,
        ];

        $threshold = $intervals[$riskLevel] ?? 365;

        if ($daysSinceReview >= $threshold) {
            return 'scheduled_periodic';
        }

        return 'risk_triggered';
    }

    /**
     * Calculate deadline for the review.
     */
    private function calculateDeadline(string $riskLevel): string
    {
        $days = match ($riskLevel) {
            'high' => 14,
            'medium' => 30,
            default => 60,
        };

        return (new \DateTimeImmutable())
            ->modify("+{$days} days")
            ->format('Y-m-d');
    }

    /**
     * Determine priority level.
     */
    private function determinePriority(string $riskLevel): string
    {
        return match ($riskLevel) {
            'high' => 'urgent',
            'medium' => 'high',
            default => 'normal',
        };
    }
}

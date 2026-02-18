<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Workflows\Steps;

use Nexus\SalesOperations\Contracts\CreditManagerInterface;
use Nexus\SalesOperations\Contracts\CustomerProviderInterface;
use Nexus\SalesOperations\Contracts\SagaStepInterface;
use Nexus\SalesOperations\DTOs\SagaStepContext;
use Nexus\SalesOperations\DTOs\SagaStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class ValidateCreditStep implements SagaStepInterface
{
    public function __construct(
        private CreditManagerInterface $creditManager,
        private CustomerProviderInterface $customerProvider,
        private ?LoggerInterface $logger = null,
    ) {}

    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    public function getId(): string
    {
        return 'validate_credit';
    }

    public function getName(): string
    {
        return 'Validate Customer Credit';
    }

    public function execute(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Validating customer credit', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $customerId = $context->get('customer_id')
                ?? $context->get('order_data.customer_id');

            if ($customerId === null) {
                return SagaStepResult::failure('Customer ID is required');
            }

            $orderAmount = $context->get('order_amount')
                ?? $context->get('order_data.total', 0);

            $isOnHold = $this->creditManager->isOnCreditHold(
                $context->tenantId,
                $customerId
            );

            if ($isOnHold) {
                $holdReason = $this->creditManager->getCreditHoldReason(
                    $context->tenantId,
                    $customerId
                );
                return SagaStepResult::failure(
                    "Customer is on credit hold: {$holdReason}"
                );
            }

            $creditLimit = $this->creditManager->getCreditLimit(
                $context->tenantId,
                $customerId
            );
            $creditUsed = $this->creditManager->getCreditUsed(
                $context->tenantId,
                $customerId
            );
            $availableCredit = $creditLimit - $creditUsed;

            if ($orderAmount > $availableCredit) {
                return SagaStepResult::failure(
                    sprintf(
                        'Credit limit exceeded. Available: %.2f, Required: %.2f',
                        $availableCredit,
                        $orderAmount
                    )
                );
            }

            return SagaStepResult::success([
                'customer_id' => $customerId,
                'credit_limit' => $creditLimit,
                'credit_used' => $creditUsed,
                'available_credit' => $availableCredit,
                'order_amount' => $orderAmount,
                'credit_validated' => true,
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Credit validation failed', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::failure(
                'Credit validation failed: ' . $e->getMessage()
            );
        }
    }

    public function compensate(SagaStepContext $context): SagaStepResult
    {
        return SagaStepResult::compensated([
            'message' => 'No compensation needed for credit validation',
        ]);
    }

    public function hasCompensation(): bool
    {
        return false;
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
        return 60;
    }

    public function getRetryAttempts(): int
    {
        return 2;
    }
}

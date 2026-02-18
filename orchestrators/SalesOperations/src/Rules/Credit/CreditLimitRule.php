<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Rules\Credit;

use Nexus\SalesOperations\Contracts\CreditManagerInterface;
use Nexus\SalesOperations\Contracts\CustomerProviderInterface;
use Nexus\SalesOperations\DTOs\CreditCheckRequest;
use Nexus\SalesOperations\Rules\RuleInterface;
use Nexus\SalesOperations\Rules\RuleResult;

final readonly class CreditLimitRule implements RuleInterface
{
    public function __construct(
        private CreditManagerInterface $creditManager,
        private CustomerProviderInterface $customerProvider
    ) {}

    public function check(object $context): RuleResult
    {
        if (!$context instanceof CreditCheckRequest) {
            return RuleResult::fail(
                $this->getName(),
                'Invalid context type: expected CreditCheckRequest'
            );
        }

        $customer = $this->customerProvider->findById(
            $context->tenantId,
            $context->customerId
        );

        if ($customer === null) {
            return RuleResult::fail(
                $this->getName(),
                sprintf('Customer %s not found', $context->customerId),
                ['customer_id' => $context->customerId]
            );
        }

        $creditLimit = $customer->getCreditLimit();
        $currentUsage = $this->creditManager->getCreditUsed(
            $context->tenantId,
            $context->customerId
        );
        $availableCredit = $creditLimit - $currentUsage;

        if ($context->orderAmount > $availableCredit) {
            return RuleResult::fail(
                $this->getName(),
                sprintf(
                    'Credit limit exceeded. Available: %.2f, Requested: %.2f',
                    $availableCredit,
                    $context->orderAmount
                ),
                [
                    'credit_limit' => $creditLimit,
                    'current_usage' => $currentUsage,
                    'available_credit' => $availableCredit,
                    'requested_amount' => $context->orderAmount,
                    'shortage' => $context->orderAmount - $availableCredit,
                ]
            );
        }

        return RuleResult::pass(
            $this->getName(),
            sprintf('Credit check passed. Available: %.2f', $availableCredit),
            [
                'credit_limit' => $creditLimit,
                'available_credit' => $availableCredit,
            ]
        );
    }

    public function getName(): string
    {
        return 'credit_limit';
    }
}

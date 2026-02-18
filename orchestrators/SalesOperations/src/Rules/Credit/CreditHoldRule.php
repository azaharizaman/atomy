<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Rules\Credit;

use Nexus\SalesOperations\Contracts\CreditManagerInterface;
use Nexus\SalesOperations\DTOs\CreditCheckRequest;
use Nexus\SalesOperations\Rules\RuleInterface;
use Nexus\SalesOperations\Rules\RuleResult;

final readonly class CreditHoldRule implements RuleInterface
{
    public function __construct(
        private CreditManagerInterface $creditManager
    ) {}

    public function check(object $context): RuleResult
    {
        if (!$context instanceof CreditCheckRequest) {
            return RuleResult::fail(
                $this->getName(),
                'Invalid context type: expected CreditCheckRequest'
            );
        }

        $isOnHold = $this->creditManager->isOnCreditHold(
            $context->tenantId,
            $context->customerId
        );

        if ($isOnHold) {
            $holdReason = $this->creditManager->getCreditHoldReason(
                $context->tenantId,
                $context->customerId
            );

            return RuleResult::fail(
                $this->getName(),
                sprintf(
                    'Customer %s is on credit hold: %s',
                    $context->customerId,
                    $holdReason ?? 'No reason specified'
                ),
                [
                    'customer_id' => $context->customerId,
                    'hold_reason' => $holdReason,
                ]
            );
        }

        return RuleResult::pass(
            $this->getName(),
            'Customer is not on credit hold'
        );
    }

    public function getName(): string
    {
        return 'credit_hold';
    }
}

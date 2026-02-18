<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Rules\Credit;

use Nexus\SalesOperations\Contracts\CustomerProviderInterface;
use Nexus\SalesOperations\DTOs\CreditCheckRequest;
use Nexus\SalesOperations\Rules\RuleInterface;
use Nexus\SalesOperations\Rules\RuleResult;

final readonly class PaymentTermsRule implements RuleInterface
{
    private const ALLOWED_TERMS = ['NET_15', 'NET_30', 'NET_45', 'NET_60', 'COD', 'PREPAID'];

    public function __construct(
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
                sprintf('Customer %s not found', $context->customerId)
            );
        }

        $customerTerms = $customer->getPaymentTerms();

        if (!in_array($customerTerms, self::ALLOWED_TERMS, true)) {
            return RuleResult::fail(
                $this->getName(),
                sprintf('Invalid payment terms: %s', $customerTerms),
                ['customer_terms' => $customerTerms]
            );
        }

        if ($customerTerms === 'COD' || $customerTerms === 'PREPAID') {
            return RuleResult::pass(
                $this->getName(),
                sprintf('Payment required upfront (%s)', $customerTerms),
                ['requires_upfront_payment' => true, 'terms' => $customerTerms]
            );
        }

        return RuleResult::pass(
            $this->getName(),
            sprintf('Payment terms validated: %s', $customerTerms),
            ['terms' => $customerTerms]
        );
    }

    public function getName(): string
    {
        return 'payment_terms';
    }
}

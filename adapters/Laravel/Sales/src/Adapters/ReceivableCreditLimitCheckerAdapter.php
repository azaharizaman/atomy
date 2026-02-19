<?php

declare(strict_types=1);

namespace Nexus\Laravel\Sales\Adapters;

use Nexus\Sales\Contracts\CreditLimitCheckerInterface;
use Nexus\Sales\Exceptions\CreditLimitExceededException;
use Nexus\Receivable\Contracts\CreditLimitCheckerInterface as ReceivableCreditLimitCheckerInterface;
use Psr\Log\LoggerInterface;

/**
 * Credit limit checker adapter that integrates with Nexus\Receivable package.
 *
 * This adapter implements the Sales package's CreditLimitCheckerInterface by
 * delegating to the Receivable package's credit checking functionality.
 *
 * This adapter belongs in the Laravel adapter layer because it requires
 * the Receivable package as a concrete dependency, which would violate
 * the atomic package independence rule if placed in the Sales package.
 */
final readonly class ReceivableCreditLimitCheckerAdapter implements CreditLimitCheckerInterface
{
    public function __construct(
        private ReceivableCreditLimitCheckerInterface $receivableCreditChecker,
        private LoggerInterface $logger
    ) {}

    /**
     * {@inheritDoc}
     */
    public function checkCreditLimit(
        string $tenantId,
        string $customerId,
        float $orderTotal,
        string $currencyCode
    ): bool {
        $this->logger->debug('Checking customer credit limit via Receivable adapter', [
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'order_total' => $orderTotal,
            'currency_code' => $currencyCode,
        ]);

        try {
            $result = $this->receivableCreditChecker->checkCreditLimit(
                $tenantId,
                $customerId,
                $orderTotal,
                $currencyCode
            );

            if ($result) {
                $this->logger->debug('Credit limit check passed', [
                    'tenant_id' => $tenantId,
                    'customer_id' => $customerId,
                    'order_total' => $orderTotal,
                ]);
            } else {
                // Get details for logging
                $currentBalance = $this->receivableCreditChecker->getOutstandingBalance($tenantId, $customerId);
                $creditLimit = $this->receivableCreditChecker->getCreditLimit($customerId);

                $this->logger->warning('Credit limit check failed', [
                    'tenant_id' => $tenantId,
                    'customer_id' => $customerId,
                    'order_total' => $orderTotal,
                    'current_balance' => $currentBalance,
                    'credit_limit' => $creditLimit,
                ]);
            }

            return $result;
        } catch (\Nexus\Receivable\Exceptions\CreditLimitExceededException $e) {
            // Translate to Sales package exception
            $currentBalance = $this->receivableCreditChecker->getOutstandingBalance($tenantId, $customerId);
            $creditLimit = $this->receivableCreditChecker->getCreditLimit($customerId);

            $this->logger->warning('Credit limit exceeded', [
                'tenant_id' => $tenantId,
                'customer_id' => $customerId,
                'order_total' => $orderTotal,
                'current_balance' => $currentBalance,
                'credit_limit' => $creditLimit,
            ]);

            throw CreditLimitExceededException::forCustomer(
                $customerId,
                $orderTotal,
                $creditLimit ?? 0.0
            );
        }
    }
}
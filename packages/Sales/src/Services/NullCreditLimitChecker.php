<?php

declare(strict_types=1);

namespace Nexus\Sales\Services;

use Nexus\Sales\Contracts\CreditLimitCheckerInterface;
use Nexus\Sales\Exceptions\CreditCheckUnavailableException;
use Psr\Log\LoggerInterface;

/**
 * Null credit limit checker implementation.
 *
 * This implementation throws CreditCheckUnavailableException to indicate
 * that credit checking requires the Receivable package adapter.
 *
 * Use this as the default binding when the Receivable package is not installed.
 * When Receivable is available, bind CreditLimitCheckerInterface to
 * ReceivableCreditLimitCheckerAdapter in the adapter layer.
 */
final readonly class NullCreditLimitChecker implements CreditLimitCheckerInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    /**
     * {@inheritDoc}
     *
     * @throws CreditCheckUnavailableException Always throws to indicate unavailable feature
     */
    public function checkCreditLimit(
        string $tenantId,
        string $customerId,
        float $orderTotal,
        string $currencyCode
    ): bool {
        $this->logger->warning('Credit limit check requested but feature is unavailable', [
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'order_total' => $orderTotal,
            'currency_code' => $currencyCode,
        ]);

        throw CreditCheckUnavailableException::unavailable();
    }
}

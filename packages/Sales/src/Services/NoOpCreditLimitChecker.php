<?php

declare(strict_types=1);

namespace Nexus\Sales\Services;

use Nexus\Sales\Contracts\CreditLimitCheckerInterface;
use Psr\Log\LoggerInterface;

/**
 * No-op credit limit checker (V1 stub implementation).
 * 
 * Always returns TRUE (no credit limit enforcement).
 * Phase 2: Replace with real integration to Nexus\Receivable.
 */
final readonly class NoOpCreditLimitChecker implements CreditLimitCheckerInterface
{
    public function __construct(
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
        $this->logger->debug('Credit limit check bypassed (NoOp implementation)', [
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'order_total' => $orderTotal,
            'currency_code' => $currencyCode,
        ]);

        // V1: Always approve
        return true;
    }
}

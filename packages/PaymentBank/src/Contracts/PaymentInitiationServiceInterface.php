<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentBank\DTOs\PaymentInitiationResult;
use Nexus\PaymentBank\ValueObjects\Beneficiary;

interface PaymentInitiationServiceInterface
{
    /**
     * Initiate a payment from a connected account.
     *
     * @param string $connectionId
     * @param string $sourceAccountId
     * @param Beneficiary $beneficiary
     * @param Money $amount
     * @param string|null $reference
     * @return PaymentInitiationResult
     */
    public function initiatePayment(
        string $connectionId,
        string $sourceAccountId,
        Beneficiary $beneficiary,
        Money $amount,
        ?string $reference = null
    ): PaymentInitiationResult;

    /**
     * Check the status of an initiated payment.
     *
     * @param string $connectionId
     * @param string $paymentId
     * @return string Status
     */
    public function getPaymentStatus(string $connectionId, string $paymentId): string;
}

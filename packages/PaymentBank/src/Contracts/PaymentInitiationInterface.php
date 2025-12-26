<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentBank\DTOs\Beneficiary;
use Nexus\PaymentBank\DTOs\PaymentInitiationResult;
use Nexus\PaymentBank\Entities\BankConnectionInterface;

interface PaymentInitiationInterface
{
    /**
     * Initiate a payment transfer.
     *
     * @param BankConnectionInterface $connection
     * @param string $sourceAccountId Provider's source account ID
     * @param Beneficiary $beneficiary Recipient details
     * @param Money $amount Amount to transfer
     * @param string $reference Payment reference/description
     * @param array<string, mixed> $options Provider-specific options
     * @return PaymentInitiationResult
     */
    public function initiatePayment(
        BankConnectionInterface $connection,
        string $sourceAccountId,
        Beneficiary $beneficiary,
        Money $amount,
        string $reference,
        array $options = []
    ): PaymentInitiationResult;

    /**
     * Get the status of a payment.
     *
     * @param BankConnectionInterface $connection
     * @param string $paymentId Provider's payment ID
     * @return string Payment status (standardized)
     */
    public function getPaymentStatus(
        BankConnectionInterface $connection,
        string $paymentId
    ): string;
}

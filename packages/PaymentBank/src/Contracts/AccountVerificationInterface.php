<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Contracts;

use Nexus\PaymentBank\Entities\BankConnectionInterface;
use Nexus\PaymentBank\Enums\VerificationMethod;
use Nexus\PaymentBank\Enums\VerificationStatus;

interface AccountVerificationInterface
{
    /**
     * Initiate account verification (e.g., micro-deposits).
     *
     * @param BankConnectionInterface $connection
     * @param string $accountId Provider's account ID
     * @param VerificationMethod $method
     * @return string Verification ID or reference
     */
    public function initiateVerification(
        BankConnectionInterface $connection,
        string $accountId,
        VerificationMethod $method
    ): string;

    /**
     * Complete verification (e.g., verify micro-deposit amounts).
     *
     * @param BankConnectionInterface $connection
     * @param string $accountId Provider's account ID
     * @param array<mixed> $data Verification data (e.g., amounts)
     * @return bool True if verified successfully
     */
    public function completeVerification(
        BankConnectionInterface $connection,
        string $accountId,
        array $data
    ): bool;

    /**
     * Get the current verification status of an account.
     *
     * @param BankConnectionInterface $connection
     * @param string $accountId Provider's account ID
     * @return VerificationStatus
     */
    public function getVerificationStatus(
        BankConnectionInterface $connection,
        string $accountId
    ): VerificationStatus;
}

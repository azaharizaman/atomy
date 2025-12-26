<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Contracts;

use Nexus\PaymentBank\DTOs\AccountVerificationResult;

interface VerificationServiceInterface
{
    /**
     * Verify account ownership using identity data (KYC match).
     *
     * @param string $connectionId
     * @param string $accountId
     * @param array<string, mixed> $identityData
     * @return AccountVerificationResult
     */
    public function verifyOwnership(string $connectionId, string $accountId, array $identityData): AccountVerificationResult;

    /**
     * Initiate micro-deposit verification.
     *
     * @param string $connectionId
     * @param string $accountId
     * @return string Verification ID
     */
    public function initiateMicroDeposits(string $connectionId, string $accountId): string;

    /**
     * Verify micro-deposit amounts.
     *
     * @param string $connectionId
     * @param string $verificationId
     * @param array<float> $amounts
     * @return bool
     */
    public function verifyMicroDeposits(string $connectionId, string $verificationId, array $amounts): bool;
}

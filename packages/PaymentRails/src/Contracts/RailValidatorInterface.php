<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Contracts;

use Nexus\PaymentRails\DTOs\AchBatchRequest;
use Nexus\PaymentRails\DTOs\AchEntryRequest;
use Nexus\PaymentRails\DTOs\CheckRequest;
use Nexus\PaymentRails\DTOs\VirtualCardRequest;
use Nexus\PaymentRails\DTOs\WireTransferRequest;

/**
 * Contract for validating payment rail transactions.
 *
 * Provides comprehensive validation for all rail types
 * before submission to ensure compliance and reduce rejections.
 */
interface RailValidatorInterface
{
    /**
     * Validate an ACH batch request.
     *
     * @return array<string> Validation errors
     */
    public function validateAchBatch(AchBatchRequest $request): array;

    /**
     * Validate an ACH entry request.
     *
     * @return array<string> Validation errors
     */
    public function validateAchEntry(AchEntryRequest $request): array;

    /**
     * Validate a wire transfer request.
     *
     * @return array<string> Validation errors
     */
    public function validateWireTransfer(WireTransferRequest $request): array;

    /**
     * Validate a check request.
     *
     * @return array<string> Validation errors
     */
    public function validateCheck(CheckRequest $request): array;

    /**
     * Validate a virtual card request.
     *
     * @return array<string> Validation errors
     */
    public function validateVirtualCard(VirtualCardRequest $request): array;

    /**
     * Validate a US ABA routing number.
     */
    public function isValidRoutingNumber(string $routingNumber): bool;

    /**
     * Validate a SWIFT/BIC code.
     */
    public function isValidSwiftCode(string $swiftCode): bool;

    /**
     * Validate an IBAN.
     */
    public function isValidIban(string $iban): bool;

    /**
     * Validate a bank account number for a specific country.
     */
    public function isValidAccountNumber(string $accountNumber, string $countryCode): bool;

    /**
     * Validate that an SEC code is appropriate for the transaction.
     *
     * @param string $secCode
     * @param array<string, mixed> $transactionContext
     */
    public function isValidSecCodeForTransaction(string $secCode, array $transactionContext): bool;

    /**
     * Check for OFAC/sanctions list matches.
     *
     * @param string $name Name to check
     * @param string|null $country Country code
     * @return array<string> Any matches found
     */
    public function checkSanctions(string $name, ?string $country = null): array;
}

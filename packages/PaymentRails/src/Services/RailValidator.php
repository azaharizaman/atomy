<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Contracts\PaymentRailInterface;
use Nexus\PaymentRails\Contracts\RailValidatorInterface;
use Nexus\PaymentRails\DTOs\RailTransactionRequest;
use Nexus\PaymentRails\Enums\RailType;
use Nexus\PaymentRails\Exceptions\RailValidationException;
use Nexus\PaymentRails\ValueObjects\BankAccount;
use Nexus\PaymentRails\ValueObjects\RoutingNumber;

/**
 * Rail transaction validator service.
 *
 * Validates payment transactions before submission to rails.
 * Enforces business rules, compliance requirements, and data integrity.
 */
final readonly class RailValidator implements RailValidatorInterface
{
    /**
     * OFAC and sanctions screen placeholder.
     */
    private const SANCTIONS_SCREEN_ENABLED = true;

    /**
     * Velocity limits per rail type (transactions per day).
     */
    private const VELOCITY_LIMITS = [
        'ach' => 1000,
        'wire' => 100,
        'check' => 500,
        'rtgs' => 50,
        'virtual_card' => 200,
    ];

    /**
     * Validate a rail transaction request.
     *
     * @throws RailValidationException
     */
    public function validate(RailTransactionRequest $request, PaymentRailInterface $rail): void
    {
        $errors = $this->collectValidationErrors($request, $rail);

        if (!empty($errors)) {
            throw RailValidationException::withErrors($errors);
        }
    }

    /**
     * Check if a transaction request is valid.
     */
    public function isValid(RailTransactionRequest $request, PaymentRailInterface $rail): bool
    {
        try {
            $this->validate($request, $rail);
            return true;
        } catch (RailValidationException) {
            return false;
        }
    }

    /**
     * Get validation errors for a request.
     *
     * @return array<string>
     */
    public function getValidationErrors(RailTransactionRequest $request, PaymentRailInterface $rail): array
    {
        return $this->collectValidationErrors($request, $rail);
    }

    /**
     * Validate bank account for a rail.
     *
     * @return array<string>
     */
    public function validateBankAccount(BankAccount $account, RailType $railType): array
    {
        $errors = [];

        // Account number validation
        if (strlen($account->accountNumber) < 4) {
            $errors[] = 'Account number is too short.';
        }

        if (strlen($account->accountNumber) > 17) {
            $errors[] = 'Account number exceeds maximum length.';
        }

        // Rail-specific validation
        switch ($railType) {
            case RailType::ACH:
                $errors = array_merge($errors, $this->validateAchBankAccount($account));
                break;
            case RailType::WIRE:
                $errors = array_merge($errors, $this->validateWireBankAccount($account));
                break;
        }

        return $errors;
    }

    /**
     * Validate routing number format.
     *
     * @return array<string>
     */
    public function validateRoutingNumber(RoutingNumber $routingNumber): array
    {
        $errors = [];
        $value = $routingNumber->getValue();

        // Length check
        if (strlen($value) !== 9) {
            $errors[] = 'Routing number must be exactly 9 digits.';
            return $errors;
        }

        // Numeric check
        if (!ctype_digit($value)) {
            $errors[] = 'Routing number must contain only digits.';
            return $errors;
        }

        // Checksum validation (ABA routing number algorithm)
        if (!$this->validateRoutingChecksum($value)) {
            $errors[] = 'Routing number checksum is invalid.';
        }

        // Federal Reserve routing number prefix check
        $prefix = (int) $value[0];
        if ($prefix === 5) {
            $errors[] = 'Routing numbers starting with 5 are not valid ABA numbers.';
        }

        return $errors;
    }

    /**
     * Validate amount for a rail.
     *
     * @return array<string>
     */
    public function validateAmount(Money $amount, PaymentRailInterface $rail): array
    {
        $errors = [];
        $capabilities = $rail->getCapabilities();
        $amountCents = (int) ($amount->getAmount() * 100);

        // Currency support
        if (!in_array($amount->getCurrency(), $capabilities->supportedCurrencies, true)) {
            $errors[] = sprintf(
                'Currency %s is not supported by %s rail.',
                $amount->getCurrency(),
                $rail->getRailType()->value
            );
        }

        // Minimum amount
        if ($amountCents < $capabilities->minimumAmountCents) {
            $errors[] = sprintf(
                'Amount is below minimum of $%.2f for %s rail.',
                $capabilities->minimumAmountCents / 100,
                $rail->getRailType()->value
            );
        }

        // Maximum amount
        if ($capabilities->maximumAmountCents !== null 
            && $amountCents > $capabilities->maximumAmountCents) {
            $errors[] = sprintf(
                'Amount exceeds maximum of $%.2f for %s rail.',
                $capabilities->maximumAmountCents / 100,
                $rail->getRailType()->value
            );
        }

        return $errors;
    }

    /**
     * Perform sanctions screening (placeholder).
     *
     * @return array<string>
     */
    public function screenSanctions(string $beneficiaryName, ?string $beneficiaryCountry = null): array
    {
        $errors = [];

        if (!self::SANCTIONS_SCREEN_ENABLED) {
            return $errors;
        }

        // Placeholder for actual OFAC/sanctions screening integration
        // In production, this would call Nexus\Sanctions package
        $blockedCountries = ['KP', 'IR', 'SY', 'CU', 'VE'];
        
        if ($beneficiaryCountry !== null && in_array($beneficiaryCountry, $blockedCountries, true)) {
            $errors[] = sprintf(
                'Transactions to %s are not permitted due to sanctions.',
                $beneficiaryCountry
            );
        }

        return $errors;
    }

    /**
     * Collect all validation errors.
     *
     * @return array<string>
     */
    private function collectValidationErrors(RailTransactionRequest $request, PaymentRailInterface $rail): array
    {
        $errors = [];

        // Basic field validation
        if (empty($request->beneficiaryName)) {
            $errors[] = 'Beneficiary name is required.';
        }

        if (strlen($request->beneficiaryName) > 35) {
            $errors[] = 'Beneficiary name exceeds maximum length (35 characters).';
        }

        // Amount validation
        $amountErrors = $this->validateAmount($request->amount, $rail);
        $errors = array_merge($errors, $amountErrors);

        // Bank account validation if provided
        if ($request->beneficiaryAccount !== null) {
            $accountErrors = $this->validateBankAccount(
                $request->beneficiaryAccount,
                $rail->getRailType()
            );
            $errors = array_merge($errors, $accountErrors);
        }

        // Routing number validation if provided
        if ($request->routingNumber !== null) {
            $routingErrors = $this->validateRoutingNumber($request->routingNumber);
            $errors = array_merge($errors, $routingErrors);
        }

        // Sanctions screening
        $sanctionsErrors = $this->screenSanctions(
            $request->beneficiaryName,
            $request->beneficiaryCountry
        );
        $errors = array_merge($errors, $sanctionsErrors);

        // Rail availability
        if (!$rail->isAvailable()) {
            $errors[] = sprintf('%s rail is currently unavailable.', $rail->getRailType()->value);
        }

        // Rail-specific validation
        $railErrors = $this->validateRailSpecific($request, $rail);
        $errors = array_merge($errors, $railErrors);

        return $errors;
    }

    /**
     * Validate ACH bank account.
     *
     * @return array<string>
     */
    private function validateAchBankAccount(BankAccount $account): array
    {
        $errors = [];

        // ACH requires routing number
        if ($account->routingNumber === null) {
            $errors[] = 'Routing number is required for ACH transactions.';
        } else {
            $routingErrors = $this->validateRoutingNumber($account->routingNumber);
            $errors = array_merge($errors, $routingErrors);
        }

        return $errors;
    }

    /**
     * Validate wire transfer bank account.
     *
     * @return array<string>
     */
    private function validateWireBankAccount(BankAccount $account): array
    {
        $errors = [];

        // Domestic wire requires routing number
        // International wire may use SWIFT/IBAN
        if ($account->routingNumber === null && $account->swiftCode === null) {
            $errors[] = 'Either routing number or SWIFT code is required for wire transfers.';
        }

        // SWIFT code validation
        if ($account->swiftCode !== null) {
            if (!$this->isValidSwiftCode($account->swiftCode)) {
                $errors[] = 'Invalid SWIFT/BIC code format.';
            }
        }

        // IBAN validation
        if ($account->iban !== null) {
            if (!$this->isValidIban($account->iban)) {
                $errors[] = 'Invalid IBAN format.';
            }
        }

        return $errors;
    }

    /**
     * Rail-specific validation.
     *
     * @return array<string>
     */
    private function validateRailSpecific(RailTransactionRequest $request, PaymentRailInterface $rail): array
    {
        return match ($rail->getRailType()) {
            RailType::ACH => $this->validateAchTransaction($request),
            RailType::WIRE => $this->validateWireTransaction($request),
            RailType::CHECK => $this->validateCheckTransaction($request),
            RailType::RTGS => $this->validateRtgsTransaction($request),
            RailType::VIRTUAL_CARD => $this->validateVirtualCardTransaction($request),
        };
    }

    /**
     * Validate ACH transaction.
     *
     * @return array<string>
     */
    private function validateAchTransaction(RailTransactionRequest $request): array
    {
        $errors = [];

        // ACH USD only
        if ($request->amount->getCurrency() !== 'USD') {
            $errors[] = 'ACH transactions must be in USD.';
        }

        // SEC code required
        if ($request->metadata === null || !isset($request->metadata['sec_code'])) {
            $errors[] = 'SEC code is required for ACH transactions.';
        }

        return $errors;
    }

    /**
     * Validate wire transaction.
     *
     * @return array<string>
     */
    private function validateWireTransaction(RailTransactionRequest $request): array
    {
        $errors = [];

        // Purpose of payment for international
        if ($request->isInternational && empty($request->purposeOfPayment)) {
            $errors[] = 'Purpose of payment is required for international wires.';
        }

        // Beneficiary address for international
        if ($request->isInternational && empty($request->beneficiaryAddress)) {
            $errors[] = 'Beneficiary address is required for international wires.';
        }

        return $errors;
    }

    /**
     * Validate check transaction.
     *
     * @return array<string>
     */
    private function validateCheckTransaction(RailTransactionRequest $request): array
    {
        $errors = [];

        // Payee address required
        if (empty($request->beneficiaryAddress)) {
            $errors[] = 'Payee address is required for check issuance.';
        }

        // Check memo length
        if ($request->memo !== null && strlen($request->memo) > 40) {
            $errors[] = 'Check memo exceeds maximum length (40 characters).';
        }

        return $errors;
    }

    /**
     * Validate RTGS transaction.
     *
     * @return array<string>
     */
    private function validateRtgsTransaction(RailTransactionRequest $request): array
    {
        $errors = [];

        // High-value only
        $amountCents = (int) ($request->amount->getAmount() * 100);
        if ($amountCents < 100000) { // $1,000
            $errors[] = 'RTGS is for high-value transactions only (minimum $1,000).';
        }

        return $errors;
    }

    /**
     * Validate virtual card transaction.
     *
     * @return array<string>
     */
    private function validateVirtualCardTransaction(RailTransactionRequest $request): array
    {
        $errors = [];

        // Vendor ID required
        if ($request->metadata === null || !isset($request->metadata['vendor_id'])) {
            $errors[] = 'Vendor ID is required for virtual card issuance.';
        }

        return $errors;
    }

    /**
     * Validate ABA routing number checksum.
     */
    private function validateRoutingChecksum(string $routingNumber): bool
    {
        $sum = 0;
        $weights = [3, 7, 1, 3, 7, 1, 3, 7, 1];

        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $routingNumber[$i] * $weights[$i];
        }

        return $sum % 10 === 0;
    }

    /**
     * Validate SWIFT code format.
     */
    private function isValidSwiftCode(string $swift): bool
    {
        // SWIFT: 8 or 11 characters
        // Format: AAAA BB CC (DDD)
        // AAAA = bank code (letters)
        // BB = country code (letters)
        // CC = location code (alphanumeric)
        // DDD = branch code (optional, alphanumeric)
        return (bool) preg_match('/^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}([A-Z0-9]{3})?$/', strtoupper($swift));
    }

    /**
     * Validate IBAN format.
     */
    private function isValidIban(string $iban): bool
    {
        // Remove spaces
        $iban = str_replace(' ', '', strtoupper($iban));

        // Length check (varies by country, 15-34)
        if (strlen($iban) < 15 || strlen($iban) > 34) {
            return false;
        }

        // Country code check
        if (!preg_match('/^[A-Z]{2}/', $iban)) {
            return false;
        }

        // Check digits
        if (!preg_match('/^[A-Z]{2}[0-9]{2}/', $iban)) {
            return false;
        }

        // Move first 4 characters to end
        $rearranged = substr($iban, 4) . substr($iban, 0, 4);

        // Replace letters with numbers (A=10, B=11, ..., Z=35)
        $numeric = '';
        for ($i = 0; $i < strlen($rearranged); $i++) {
            $char = $rearranged[$i];
            if (ctype_alpha($char)) {
                $numeric .= (string) (ord($char) - 55);
            } else {
                $numeric .= $char;
            }
        }

        // Mod 97 check
        return bcmod($numeric, '97') === '1';
    }
}

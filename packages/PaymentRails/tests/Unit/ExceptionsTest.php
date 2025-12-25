<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit;

use Nexus\PaymentRails\Enums\SecCode;
use Nexus\PaymentRails\Exceptions\AchValidationException;
use Nexus\PaymentRails\Exceptions\InvalidCheckNumberException;
use Nexus\PaymentRails\Exceptions\InvalidRoutingNumberException;
use Nexus\PaymentRails\Exceptions\InvalidSwiftCodeException;
use Nexus\PaymentRails\Exceptions\PaymentRailException;
use Nexus\PaymentRails\Exceptions\RailUnavailableException;
use Nexus\PaymentRails\Exceptions\WireValidationException;
use Nexus\PaymentRails\Enums\WireType;
use PHPUnit\Framework\TestCase;

final class ExceptionsTest extends TestCase
{
    // AchValidationException Tests
    public function test_AchValidationException_multipleErrors(): void
    {
        $errors = ['Error 1', 'Error 2'];
        $exception = AchValidationException::multipleErrors($errors);
        
        $this->assertSame("ACH validation failed with 2 error(s)", $exception->getMessage());
        $this->assertSame($errors, $exception->getErrors());
        $this->assertSame('ACH', $exception->getRailType());
    }

    public function test_AchValidationException_unbalancedBatch(): void
    {
        $exception = AchValidationException::unbalancedBatch(1000, 2000);
        
        $this->assertSame("Batch is unbalanced: debits (10.00) do not equal credits (20.00)", $exception->getMessage());
        $this->assertSame(["Debits: 10.00", "Credits: 20.00"], $exception->getErrors());
    }

    public function test_AchValidationException_invalidSecCode(): void
    {
        $exception = AchValidationException::invalidSecCode(SecCode::PPD, 'Reason');
        
        $this->assertSame("SEC code PPD cannot be used: Reason", $exception->getMessage());
        $this->assertSame(['Reason'], $exception->getErrors());
    }

    public function test_AchValidationException_exceedsBatchLimit(): void
    {
        $exception = AchValidationException::exceedsBatchLimit(100, 50);
        
        $this->assertSame("Batch contains 100 entries, exceeding maximum of 50", $exception->getMessage());
        $this->assertSame(["Entry count: 100", "Maximum allowed: 50"], $exception->getErrors());
    }

    public function test_AchValidationException_missingRequiredField(): void
    {
        $exception = AchValidationException::missingRequiredField('field_name');
        
        $this->assertSame("Required ACH field 'field_name' is missing", $exception->getMessage());
        $this->assertSame(["Missing: field_name"], $exception->getErrors());
    }

    public function test_AchValidationException_invalidFieldLength(): void
    {
        $exception = AchValidationException::invalidFieldLength('field_name', 10, 5);
        
        $this->assertSame("ACH field 'field_name' has invalid length: expected 10, got 5", $exception->getMessage());
        $this->assertSame(["Field: field_name", "Expected: 10", "Actual: 5"], $exception->getErrors());
    }

    public function test_AchValidationException_effectiveDateInPast(): void
    {
        $date = new \DateTimeImmutable('2023-01-01');
        $exception = AchValidationException::effectiveDateInPast($date);
        
        $this->assertSame("Effective entry date 2023-01-01 is in the past", $exception->getMessage());
        $this->assertSame(["Date: 2023-01-01"], $exception->getErrors());
    }

    public function test_AchValidationException_effectiveDateTooFar(): void
    {
        $date = new \DateTimeImmutable('2023-01-01');
        $exception = AchValidationException::effectiveDateTooFar($date, 30);
        
        $this->assertSame("Effective entry date 2023-01-01 is more than 30 days in the future", $exception->getMessage());
        $this->assertSame(["Date: 2023-01-01", "Maximum days ahead: 30"], $exception->getErrors());
    }

    // InvalidCheckNumberException Tests
    public function test_InvalidCheckNumberException_nonNumeric(): void
    {
        $exception = InvalidCheckNumberException::nonNumeric('ABC');
        
        $this->assertSame("Invalid check number 'ABC': Check number must contain only digits", $exception->getMessage());
        $this->assertSame('ABC', $exception->getCheckNumber());
    }

    public function test_InvalidCheckNumberException_belowMinimum(): void
    {
        $exception = InvalidCheckNumberException::belowMinimum('0');
        
        $this->assertSame("Invalid check number '0': Check number must be 1 or greater", $exception->getMessage());
    }

    public function test_InvalidCheckNumberException_exceedsMaximum(): void
    {
        $exception = InvalidCheckNumberException::exceedsMaximum('100', 50);
        
        $this->assertSame("Invalid check number '100': Check number exceeds maximum value of 50", $exception->getMessage());
    }

    public function test_InvalidCheckNumberException_negativeValue(): void
    {
        $exception = InvalidCheckNumberException::negativeValue('-1');
        
        $this->assertSame("Invalid check number '-1': Check number cannot be negative", $exception->getMessage());
    }

    public function test_InvalidCheckNumberException_duplicate(): void
    {
        $exception = InvalidCheckNumberException::duplicate('123');
        
        $this->assertSame("Invalid check number '123': Check number has already been used", $exception->getMessage());
    }

    public function test_InvalidCheckNumberException_outOfSequence(): void
    {
        $exception = InvalidCheckNumberException::outOfSequence('123', '100-120');
        
        $this->assertSame("Invalid check number '123': Check number is out of expected sequence. Expected: 100-120", $exception->getMessage());
    }

    // InvalidRoutingNumberException Tests
    public function test_InvalidRoutingNumberException_invalidFormat(): void
    {
        $exception = InvalidRoutingNumberException::invalidFormat('123');
        
        $this->assertSame("Invalid routing number '***': Must be exactly 9 digits", $exception->getMessage());
    }

    public function test_InvalidRoutingNumberException_invalidLength(): void
    {
        $exception = InvalidRoutingNumberException::invalidLength('123');
        
        $this->assertSame("Invalid routing number '***': Must be exactly 9 digits", $exception->getMessage());
    }

    public function test_InvalidRoutingNumberException_invalidChecksum(): void
    {
        $exception = InvalidRoutingNumberException::invalidChecksum('123456789');
        
        $this->assertSame("Invalid routing number '*****6789': Failed checksum validation (mod-10)", $exception->getMessage());
    }

    public function test_InvalidRoutingNumberException_invalidCheckDigit(): void
    {
        $exception = InvalidRoutingNumberException::invalidCheckDigit('123456789');
        
        $this->assertSame("Invalid routing number '*****6789': Failed checksum validation (mod-10)", $exception->getMessage());
    }

    public function test_InvalidRoutingNumberException_invalidFederalReserveDistrict(): void
    {
        $exception = InvalidRoutingNumberException::invalidFederalReserveDistrict('993456789');
        
        $this->assertSame("Invalid routing number '*****6789': Invalid Federal Reserve district code '99'", $exception->getMessage());
    }

    public function test_InvalidRoutingNumberException_notFound(): void
    {
        $exception = InvalidRoutingNumberException::notFound('123456789');
        
        $this->assertSame("Invalid routing number '*****6789': Routing number not found in ACH participant list", $exception->getMessage());
    }

    // InvalidSwiftCodeException Tests
    public function test_InvalidSwiftCodeException_invalidFormat(): void
    {
        $exception = InvalidSwiftCodeException::invalidFormat('INVALID');
        
        $this->assertSame("Invalid SWIFT/BIC code 'INVALID': Must be a valid SWIFT/BIC code (8 or 11 chars)", $exception->getMessage());
        $this->assertSame('INVALID', $exception->getSwiftCode());
    }

    public function test_InvalidSwiftCodeException_invalidLength(): void
    {
        $exception = InvalidSwiftCodeException::invalidLength('INV');
        
        $this->assertSame("Invalid SWIFT/BIC code 'INV': Must be 8 or 11 characters, got 3", $exception->getMessage());
    }

    public function test_InvalidSwiftCodeException_invalidBankCode(): void
    {
        $exception = InvalidSwiftCodeException::invalidBankCode('12345678');
        
        $this->assertSame("Invalid SWIFT/BIC code '12345678': Invalid bank code '1234' (must be 4 letters)", $exception->getMessage());
    }

    public function test_InvalidSwiftCodeException_invalidCountryCode(): void
    {
        $exception = InvalidSwiftCodeException::invalidCountryCode('BANK1234');
        
        $this->assertSame("Invalid SWIFT/BIC code 'BANK1234': Invalid ISO 3166-1 country code '12'", $exception->getMessage());
    }

    public function test_InvalidSwiftCodeException_invalidLocationCode(): void
    {
        $exception = InvalidSwiftCodeException::invalidLocationCode('BANKUS!!');
        
        $this->assertSame("Invalid SWIFT/BIC code 'BANKUS!!': Invalid location code '!!' (must be alphanumeric)", $exception->getMessage());
    }

    public function test_InvalidSwiftCodeException_invalidBranchCode(): void
    {
        $exception = InvalidSwiftCodeException::invalidBranchCode('BANKUSNY!!!');
        
        $this->assertSame("Invalid SWIFT/BIC code 'BANKUSNY!!!': Invalid branch code '!!!' (must be alphanumeric)", $exception->getMessage());
    }

    public function test_InvalidSwiftCodeException_unknownBank(): void
    {
        $exception = InvalidSwiftCodeException::unknownBank('BANKUSNY');
        
        $this->assertSame("Invalid SWIFT/BIC code 'BANKUSNY': SWIFT code not found in global bank directory", $exception->getMessage());
    }

    // PaymentRailException Tests
    public function test_PaymentRailException_railUnavailable(): void
    {
        $exception = PaymentRailException::railUnavailable('ACH', 'Reason');
        
        $this->assertSame("Payment rail 'ACH' is currently unavailable: Reason", $exception->getMessage());
        $this->assertSame('ACH', $exception->getRailType());
        $this->assertSame(['reason' => 'Reason'], $exception->getContext());
    }

    public function test_PaymentRailException_unsupportedOperation(): void
    {
        $exception = PaymentRailException::unsupportedOperation('ACH', 'Operation');
        
        $this->assertSame("Operation 'Operation' is not supported by rail 'ACH'", $exception->getMessage());
        $this->assertSame(['operation' => 'Operation'], $exception->getContext());
    }

    public function test_PaymentRailException_configurationError(): void
    {
        $exception = PaymentRailException::configurationError('ACH', 'Message');
        
        $this->assertSame("Configuration error for rail 'ACH': Message", $exception->getMessage());
    }

    // RailUnavailableException Tests
    public function test_RailUnavailableException_maintenance(): void
    {
        $date = new \DateTimeImmutable('2023-01-01 12:00:00 UTC');
        $exception = RailUnavailableException::maintenance('ACH', $date);
        
        $this->assertStringContainsString("Payment rail 'ACH' is unavailable: Scheduled maintenance", $exception->getMessage());
        $this->assertSame($date, $exception->getExpectedAvailability());
    }

    public function test_RailUnavailableException_outsideOperatingHours(): void
    {
        $date = new \DateTimeImmutable('2023-01-01 12:00:00 UTC');
        $exception = RailUnavailableException::outsideOperatingHours('ACH', $date);
        
        $this->assertStringContainsString("Payment rail 'ACH' is unavailable: Outside operating hours", $exception->getMessage());
        $this->assertSame($date, $exception->getExpectedAvailability());
    }

    public function test_RailUnavailableException_systemOutage(): void
    {
        $exception = RailUnavailableException::systemOutage('ACH');
        
        $this->assertSame("Payment rail 'ACH' is unavailable: System outage", $exception->getMessage());
        $this->assertNull($exception->getExpectedAvailability());
    }

    public function test_RailUnavailableException_cutoffPassed(): void
    {
        $date = new \DateTimeImmutable('2023-01-01 12:00:00 UTC');
        $exception = RailUnavailableException::cutoffPassed('ACH', $date);
        
        $this->assertStringContainsString("Payment rail 'ACH' is unavailable: Cutoff time has passed for today", $exception->getMessage());
        $this->assertSame($date, $exception->getExpectedAvailability());
    }

    // WireValidationException Tests
    public function test_WireValidationException_multipleErrors(): void
    {
        $errors = ['Error 1', 'Error 2'];
        $exception = WireValidationException::multipleErrors($errors);
        
        $this->assertSame("Wire transfer validation failed with 2 error(s)", $exception->getMessage());
        $this->assertSame($errors, $exception->getErrors());
    }

    public function test_WireValidationException_missingBeneficiary(): void
    {
        $exception = WireValidationException::missingBeneficiary('Field');
        
        $this->assertSame("Missing beneficiary information: Field", $exception->getMessage());
        $this->assertSame(['Field'], $exception->getErrors());
    }

    public function test_WireValidationException_invalidCurrency(): void
    {
        $exception = WireValidationException::invalidCurrency('USD', WireType::DOMESTIC);
        
        $this->assertSame("Currency 'USD' is not supported for domestic wire transfers", $exception->getMessage());
        $this->assertSame(["Currency: USD", "Wire type: domestic"], $exception->getErrors());
    }

    public function test_WireValidationException_amountBelowMinimum(): void
    {
        $exception = WireValidationException::amountBelowMinimum(100, 200);
        
        $this->assertSame("Wire amount 1.00 is below minimum of 2.00", $exception->getMessage());
        $this->assertSame(["Amount: 1.00", "Minimum: 2.00"], $exception->getErrors());
    }

    public function test_WireValidationException_amountAboveMaximum(): void
    {
        $exception = WireValidationException::amountAboveMaximum(200, 100);
        
        $this->assertSame("Wire amount 2.00 exceeds maximum of 1.00", $exception->getMessage());
        $this->assertSame(["Amount: 2.00", "Maximum: 1.00"], $exception->getErrors());
    }

    public function test_WireValidationException_missingIntermediaryBank(): void
    {
        $exception = WireValidationException::missingIntermediaryBank('Reason');
        
        $this->assertSame("Intermediary bank required: Reason", $exception->getMessage());
        $this->assertSame(['Reason'], $exception->getErrors());
    }

    public function test_WireValidationException_sanctionedCountry(): void
    {
        $exception = WireValidationException::sanctionedCountry('US');
        
        $this->assertSame("Wire transfers to country 'US' are prohibited due to sanctions", $exception->getMessage());
        $this->assertSame(["Country: US"], $exception->getErrors());
    }

    public function test_WireValidationException_insufficientBalance(): void
    {
        $exception = WireValidationException::insufficientBalance(200, 100);
        
        $this->assertSame("Insufficient balance for wire transfer: need 2.00, available 1.00", $exception->getMessage());
        $this->assertSame(["Required: 2.00", "Available: 1.00"], $exception->getErrors());
    }

    public function test_WireValidationException_missingPurpose(): void
    {
        $exception = WireValidationException::missingPurpose();
        
        $this->assertSame("Wire transfer purpose/reference is required for international transfers", $exception->getMessage());
        $this->assertSame(['Missing: purpose'], $exception->getErrors());
    }
}

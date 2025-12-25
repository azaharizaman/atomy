<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Enums\RailType;
use Nexus\PaymentRails\Services\RailValidator;
use Nexus\PaymentRails\ValueObjects\BankAccount;
use Nexus\PaymentRails\DTOs\RailTransactionResult;
use Nexus\PaymentRails\ValueObjects\RoutingNumber;
use Nexus\PaymentRails\DTOs\RailTransactionRequest;
use Nexus\PaymentRails\ValueObjects\RailCapabilities;
use Nexus\PaymentRails\Contracts\PaymentRailInterface;

final class RailValidatorTest extends TestCase
{
    private RailValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new RailValidator();
    }

    public function test_valid_ach_request_passes_validation(): void
    {
        $rail = $this->stubRail(RailType::ACH, RailCapabilities::forAch());

        $request = new RailTransactionRequest(
            beneficiaryName: 'Alice Doe',
            amount: Money::of(100.00, 'USD'),
            beneficiaryAccount: new BankAccount(
                accountNumber: '1234567890',
                routingNumber: RoutingNumber::fromString('021000021'),
            ),
            metadata: ['sec_code' => 'PPD'],
        );

        $errors = $this->validator->getValidationErrors($request, $rail);

        self::assertSame([], $errors);
    }

    public function test_rejects_unsupported_currency(): void
    {
        $rail = $this->stubRail(RailType::WIRE, RailCapabilities::forDomesticWire());

        $request = new RailTransactionRequest(
            beneficiaryName: 'Bob Smith',
            amount: Money::of(10.00, 'EUR'),
        );

        $errors = $this->validator->getValidationErrors($request, $rail);

        self::assertNotEmpty($errors);
        self::assertCount(1, $errors);
        self::assertStringContainsString('Currency EUR is not supported by wire rail.', $errors[0]);
    }

    public function test_rejects_amount_below_minimum(): void
    {
        $capabilities = new RailCapabilities(
            railType: RailType::WIRE,
            supportedCurrencies: ['USD'],
            minimumAmount: Money::of(50.00, 'USD'),
            maximumAmount: Money::of(1000.00, 'USD'),
        );
        $rail = $this->stubRail(RailType::WIRE, $capabilities);

        $request = new RailTransactionRequest(
            beneficiaryName: 'Carol Buyer',
            amount: Money::of(10.00, 'USD'),
        );

        $errors = $this->validator->getValidationErrors($request, $rail);

        self::assertNotEmpty($errors);
        self::assertCount(1, $errors);
        self::assertStringContainsString('Amount is below minimum', $errors[0]);
    }

    public function test_ach_requires_sec_code_metadata(): void
    {
        $rail = $this->stubRail(RailType::ACH, RailCapabilities::forAch());

        $request = new RailTransactionRequest(
            beneficiaryName: 'David Recipient',
            amount: Money::of(25.00, 'USD'),
            beneficiaryAccount: new BankAccount('987654321', RoutingNumber::fromString('021000021')),
        );

        $errors = $this->validator->getValidationErrors($request, $rail);

        self::assertContains('SEC code is required for ACH transactions.', $errors);
    }

    public function test_international_wire_requires_purpose_and_address(): void
    {
        $rail = $this->stubRail(RailType::WIRE, RailCapabilities::forInternationalWire());

        $request = new RailTransactionRequest(
            beneficiaryName: 'Eve Vendor',
            amount: Money::of(500.00, 'USD'),
            isInternational: true,
        );

        $errors = $this->validator->getValidationErrors($request, $rail);

        self::assertCount(2, $errors);
        self::assertContains('Purpose of payment is required for international wires.', $errors);
        self::assertContains('Beneficiary address is required for international wires.', $errors);
    }

    public function test_international_wire_minimum_amount_is_currency_safe(): void
    {
        $rail = $this->stubRail(RailType::WIRE, RailCapabilities::forInternationalWire());

        $request = new RailTransactionRequest(
            beneficiaryName: 'Euro Vendor',
            amount: Money::of(0.50, 'EUR'),
            isInternational: true,
            purposeOfPayment: 'Invoice payment',
            beneficiaryAddress: '123 Example Street, Berlin',
        );

        $errors = $this->validator->getValidationErrors($request, $rail);

        self::assertNotEmpty($errors);
        self::assertCount(1, $errors);
        self::assertStringContainsString('Amount is below minimum', $errors[0]);
    }

    public function test_rtgs_enforces_high_value_threshold(): void
    {
        $rail = $this->stubRail(RailType::RTGS, RailCapabilities::forRtgs());

        $request = new RailTransactionRequest(
            beneficiaryName: 'Frank Highvalue',
            amount: Money::of(500.00, 'USD'),
        );

        $errors = $this->validator->getValidationErrors($request, $rail);

        self::assertStringContainsString('RTGS is for high-value transactions only', $errors[0]);
    }

    public function test_beneficiary_name_is_required(): void
    {
        $rail = $this->stubRail(RailType::CHECK, RailCapabilities::forCheck());

        $request = new RailTransactionRequest(
            beneficiaryName: '',
            amount: Money::of(20.00, 'USD'),
            beneficiaryAddress: null,
        );

        $errors = $this->validator->getValidationErrors($request, $rail);

        self::assertContains('Beneficiary name is required.', $errors);
    }

    private function stubRail(RailType $type, RailCapabilities $capabilities, bool $available = true): PaymentRailInterface
    {
        return new class($type, $capabilities, $available) implements PaymentRailInterface {
            public function __construct(
                private RailType $type,
                private RailCapabilities $capabilities,
                private bool $available,
            ) {
            }

            public function getRailType(): RailType
            {
                return $this->type;
            }

            public function getCapabilities(): RailCapabilities
            {
                return $this->capabilities;
            }

            public function isAvailable(): bool
            {
                return $this->available;
            }

            public function supportsAmount(Money $amount): bool
            {
                return $this->capabilities->isAmountWithinLimits($amount);
            }

            public function supportsCurrency(string $currencyCode): bool
            {
                return $this->capabilities->supportsCurrency($currencyCode);
            }

            public function getEstimatedSettlementDays(): int
            {
                return $this->capabilities->typicalSettlementDays;
            }

            public function isRealTime(): bool
            {
                return $this->capabilities->isRealTime();
            }

            public function validateTransaction(array $transactionData): array
            {
                // This suite tests Nexus\PaymentRails\Services\RailValidator.
                // Rail-specific validation is out of scope for these unit tests.
                return [];
            }

            public function getTransactionStatus(string $transactionId): RailTransactionResult
            {
                return RailTransactionResult::pending($transactionId, $this->type, new Money(1, 'USD'));
            }

            public function cancelTransaction(string $transactionId, string $reason): bool
            {
                return false;
            }
        };
    }
}

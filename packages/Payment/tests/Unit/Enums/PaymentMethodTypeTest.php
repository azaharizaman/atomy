<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\Enums;

use Nexus\Payment\Enums\PaymentMethodType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PaymentMethodType::class)]
final class PaymentMethodTypeTest extends TestCase
{
    #[Test]
    public function it_has_all_expected_cases(): void
    {
        $cases = PaymentMethodType::cases();

        $this->assertCount(10, $cases);
        $this->assertContains(PaymentMethodType::CARD, $cases);
        $this->assertContains(PaymentMethodType::BANK_ACCOUNT, $cases);
        $this->assertContains(PaymentMethodType::WALLET, $cases);
        $this->assertContains(PaymentMethodType::CASH, $cases);
        $this->assertContains(PaymentMethodType::CHECK, $cases);
        $this->assertContains(PaymentMethodType::WIRE, $cases);
        $this->assertContains(PaymentMethodType::VIRTUAL_CARD, $cases);
        $this->assertContains(PaymentMethodType::BNPL, $cases);
        $this->assertContains(PaymentMethodType::CRYPTO, $cases);
        $this->assertContains(PaymentMethodType::OTHER, $cases);
    }

    #[Test]
    #[DataProvider('methodLabelsProvider')]
    public function it_returns_correct_labels(PaymentMethodType $method, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, $method->label());
    }

    public static function methodLabelsProvider(): iterable
    {
        yield 'CARD' => [PaymentMethodType::CARD, 'Card'];
        yield 'BANK_ACCOUNT' => [PaymentMethodType::BANK_ACCOUNT, 'Bank Account'];
        yield 'WALLET' => [PaymentMethodType::WALLET, 'Digital Wallet'];
        yield 'CASH' => [PaymentMethodType::CASH, 'Cash'];
        yield 'CHECK' => [PaymentMethodType::CHECK, 'Check'];
        yield 'WIRE' => [PaymentMethodType::WIRE, 'Wire Transfer'];
        yield 'VIRTUAL_CARD' => [PaymentMethodType::VIRTUAL_CARD, 'Virtual Card'];
        yield 'BNPL' => [PaymentMethodType::BNPL, 'Buy Now Pay Later'];
        yield 'CRYPTO' => [PaymentMethodType::CRYPTO, 'Cryptocurrency'];
        yield 'OTHER' => [PaymentMethodType::OTHER, 'Other'];
    }

    #[Test]
    #[DataProvider('electronicMethodsProvider')]
    public function it_correctly_identifies_electronic_methods(PaymentMethodType $method, bool $isElectronic): void
    {
        $this->assertSame($isElectronic, $method->isElectronic());
    }

    public static function electronicMethodsProvider(): iterable
    {
        yield 'CARD is electronic' => [PaymentMethodType::CARD, true];
        yield 'BANK_ACCOUNT is electronic' => [PaymentMethodType::BANK_ACCOUNT, true];
        yield 'WALLET is electronic' => [PaymentMethodType::WALLET, true];
        yield 'CASH is not electronic' => [PaymentMethodType::CASH, false];
        yield 'CHECK is not electronic' => [PaymentMethodType::CHECK, false];
        yield 'WIRE is electronic' => [PaymentMethodType::WIRE, true];
        yield 'VIRTUAL_CARD is electronic' => [PaymentMethodType::VIRTUAL_CARD, true];
        yield 'BNPL is electronic' => [PaymentMethodType::BNPL, true];
        yield 'CRYPTO is electronic' => [PaymentMethodType::CRYPTO, true];
        yield 'OTHER is not electronic' => [PaymentMethodType::OTHER, false];
    }

    #[Test]
    #[DataProvider('refundSupportProvider')]
    public function it_correctly_identifies_refund_support(PaymentMethodType $method, bool $supportsRefund): void
    {
        $this->assertSame($supportsRefund, $method->supportsRefund());
    }

    public static function refundSupportProvider(): iterable
    {
        yield 'CARD supports refund' => [PaymentMethodType::CARD, true];
        yield 'BANK_ACCOUNT supports refund' => [PaymentMethodType::BANK_ACCOUNT, true];
        yield 'WALLET supports refund' => [PaymentMethodType::WALLET, true];
        yield 'CASH does not support refund' => [PaymentMethodType::CASH, false];
        yield 'CHECK does not support refund' => [PaymentMethodType::CHECK, false];
        yield 'WIRE supports refund' => [PaymentMethodType::WIRE, true];
        yield 'VIRTUAL_CARD supports refund' => [PaymentMethodType::VIRTUAL_CARD, true];
        yield 'BNPL supports refund' => [PaymentMethodType::BNPL, true];
        yield 'CRYPTO does not support refund' => [PaymentMethodType::CRYPTO, false];
        yield 'OTHER does not support refund' => [PaymentMethodType::OTHER, false];
    }

    #[Test]
    #[DataProvider('tokenizationRequirementProvider')]
    public function it_correctly_identifies_tokenization_requirement(PaymentMethodType $method, bool $requiresTokenization): void
    {
        $this->assertSame($requiresTokenization, $method->requiresTokenization());
    }

    public static function tokenizationRequirementProvider(): iterable
    {
        yield 'CARD requires tokenization' => [PaymentMethodType::CARD, true];
        yield 'BANK_ACCOUNT does not require tokenization' => [PaymentMethodType::BANK_ACCOUNT, false];
        yield 'WALLET requires tokenization' => [PaymentMethodType::WALLET, true];
        yield 'CASH does not require tokenization' => [PaymentMethodType::CASH, false];
        yield 'CHECK does not require tokenization' => [PaymentMethodType::CHECK, false];
        yield 'WIRE does not require tokenization' => [PaymentMethodType::WIRE, false];
        yield 'VIRTUAL_CARD requires tokenization' => [PaymentMethodType::VIRTUAL_CARD, true];
        yield 'BNPL does not require tokenization' => [PaymentMethodType::BNPL, false];
        yield 'CRYPTO does not require tokenization' => [PaymentMethodType::CRYPTO, false];
        yield 'OTHER does not require tokenization' => [PaymentMethodType::OTHER, false];
    }

    #[Test]
    public function it_can_be_created_from_string(): void
    {
        $method = PaymentMethodType::from('bank_account');

        $this->assertSame(PaymentMethodType::BANK_ACCOUNT, $method);
    }

    #[Test]
    public function it_returns_null_for_invalid_method(): void
    {
        $method = PaymentMethodType::tryFrom('invalid');

        $this->assertNull($method);
    }
}

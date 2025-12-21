<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\Enums;

use Nexus\PaymentGateway\Enums\CardBrand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CardBrand::class)]
final class CardBrandTest extends TestCase
{
    #[Test]
    public function it_has_all_expected_brands(): void
    {
        $expectedBrands = [
            'VISA',
            'MASTERCARD',
            'AMEX',
            'DISCOVER',
            'DINERS',
            'JCB',
            'UNIONPAY',
            'UNKNOWN',
        ];

        $actualBrands = array_map(
            fn (CardBrand $brand) => $brand->name,
            CardBrand::cases()
        );

        $this->assertSame($expectedBrands, $actualBrands);
    }

    #[Test]
    #[DataProvider('brandLabelProvider')]
    public function it_returns_correct_labels(CardBrand $brand, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, $brand->label());
    }

    public static function brandLabelProvider(): array
    {
        return [
            'visa' => [CardBrand::VISA, 'Visa'],
            'mastercard' => [CardBrand::MASTERCARD, 'Mastercard'],
            'amex' => [CardBrand::AMEX, 'American Express'],
            'discover' => [CardBrand::DISCOVER, 'Discover'],
            'diners' => [CardBrand::DINERS, 'Diners Club'],
            'jcb' => [CardBrand::JCB, 'JCB'],
            'unionpay' => [CardBrand::UNIONPAY, 'UnionPay'],
            'unknown' => [CardBrand::UNKNOWN, 'Unknown'],
        ];
    }

    #[Test]
    public function amex_has_four_digit_cvv(): void
    {
        $this->assertSame(4, CardBrand::AMEX->cvvLength());
    }

    #[Test]
    public function other_brands_have_three_digit_cvv(): void
    {
        $this->assertSame(3, CardBrand::VISA->cvvLength());
        $this->assertSame(3, CardBrand::MASTERCARD->cvvLength());
        $this->assertSame(3, CardBrand::DISCOVER->cvvLength());
        $this->assertSame(3, CardBrand::DINERS->cvvLength());
        $this->assertSame(3, CardBrand::JCB->cvvLength());
        $this->assertSame(3, CardBrand::UNIONPAY->cvvLength());
        $this->assertSame(3, CardBrand::UNKNOWN->cvvLength());
    }

    #[Test]
    #[DataProvider('fromStringProvider')]
    public function it_creates_from_string_case_insensitive(string $input, CardBrand $expected): void
    {
        $this->assertSame($expected, CardBrand::fromString($input));
    }

    public static function fromStringProvider(): array
    {
        return [
            'visa' => ['visa', CardBrand::VISA],
            'visa uppercase' => ['VISA', CardBrand::VISA],
            'mastercard' => ['mastercard', CardBrand::MASTERCARD],
            'mastercard mc' => ['mc', CardBrand::MASTERCARD],
            'amex' => ['amex', CardBrand::AMEX],
            'amex full' => ['american_express', CardBrand::AMEX],
            'amex with space' => ['american express', CardBrand::AMEX],
            'discover' => ['discover', CardBrand::DISCOVER],
            'diners' => ['diners', CardBrand::DINERS],
            'diners club underscore' => ['diners_club', CardBrand::DINERS],
            'diners club space' => ['diners club', CardBrand::DINERS],
            'jcb' => ['jcb', CardBrand::JCB],
            'unionpay' => ['unionpay', CardBrand::UNIONPAY],
            'unionpay underscore' => ['union_pay', CardBrand::UNIONPAY],
            'unionpay cup' => ['cup', CardBrand::UNIONPAY],
            'unknown' => ['invalid', CardBrand::UNKNOWN],
            'unknown random' => ['random_brand', CardBrand::UNKNOWN],
        ];
    }
}

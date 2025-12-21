<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\ValueObjects;

use Nexus\PaymentGateway\Enums\CardBrand;
use Nexus\PaymentGateway\ValueObjects\CardMetadata;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CardMetadata::class)]
final class CardMetadataTest extends TestCase
{
    #[Test]
    public function it_creates_metadata_with_all_parameters(): void
    {
        $metadata = new CardMetadata(
            brand: CardBrand::VISA,
            lastFour: '4242',
            expiryMonth: 12,
            expiryYear: 2030,
            funding: 'credit',
            country: 'US',
            fingerprint: 'fp_123',
        );

        $this->assertSame(CardBrand::VISA, $metadata->brand);
        $this->assertSame('4242', $metadata->lastFour);
        $this->assertSame(12, $metadata->expiryMonth);
        $this->assertSame(2030, $metadata->expiryYear);
        $this->assertSame('credit', $metadata->funding);
        $this->assertSame('US', $metadata->country);
        $this->assertSame('fp_123', $metadata->fingerprint);
    }

    #[Test]
    public function it_creates_from_stripe_style_array(): void
    {
        $data = [
            'brand' => 'visa',
            'last4' => '4242',
            'exp_month' => 12,
            'exp_year' => 2030,
            'funding' => 'credit',
            'country' => 'US',
            'fingerprint' => 'fp_stripe',
        ];

        $metadata = CardMetadata::fromArray($data);

        $this->assertSame(CardBrand::VISA, $metadata->brand);
        $this->assertSame('4242', $metadata->lastFour);
        $this->assertSame(12, $metadata->expiryMonth);
        $this->assertSame(2030, $metadata->expiryYear);
        $this->assertSame('credit', $metadata->funding);
    }

    #[Test]
    public function it_creates_from_camel_case_array(): void
    {
        $data = [
            'brand' => 'mastercard',
            'lastFour' => '5555',
            'expiryMonth' => 6,
            'expiryYear' => 2025,
        ];

        $metadata = CardMetadata::fromArray($data);

        $this->assertSame(CardBrand::MASTERCARD, $metadata->brand);
        $this->assertSame('5555', $metadata->lastFour);
        $this->assertSame(6, $metadata->expiryMonth);
        $this->assertSame(2025, $metadata->expiryYear);
    }

    #[Test]
    public function it_uses_defaults_for_missing_keys(): void
    {
        $metadata = CardMetadata::fromArray([]);

        $this->assertSame(CardBrand::UNKNOWN, $metadata->brand);
        $this->assertSame('0000', $metadata->lastFour);
        $this->assertSame(1, $metadata->expiryMonth);
        $this->assertSame(2000, $metadata->expiryYear);
    }

    #[Test]
    public function it_formats_expiry_display(): void
    {
        $metadata = new CardMetadata(
            brand: CardBrand::VISA,
            lastFour: '4242',
            expiryMonth: 3,
            expiryYear: 2025,
        );

        $this->assertSame('03/25', $metadata->getExpiryDisplay());

        $metadata2 = new CardMetadata(
            brand: CardBrand::VISA,
            lastFour: '4242',
            expiryMonth: 12,
            expiryYear: 2030,
        );

        $this->assertSame('12/30', $metadata2->getExpiryDisplay());
    }

    #[Test]
    public function it_detects_expired_cards(): void
    {
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('n');

        // Past year - expired
        $pastYear = new CardMetadata(
            brand: CardBrand::VISA,
            lastFour: '4242',
            expiryMonth: 12,
            expiryYear: $currentYear - 1,
        );
        $this->assertTrue($pastYear->isExpired());

        // Current year, past month - expired
        if ($currentMonth > 1) {
            $pastMonth = new CardMetadata(
                brand: CardBrand::VISA,
                lastFour: '4242',
                expiryMonth: $currentMonth - 1,
                expiryYear: $currentYear,
            );
            $this->assertTrue($pastMonth->isExpired());
        }

        // Future year - not expired
        $futureYear = new CardMetadata(
            brand: CardBrand::VISA,
            lastFour: '4242',
            expiryMonth: 1,
            expiryYear: $currentYear + 5,
        );
        $this->assertFalse($futureYear->isExpired());

        // Current year, future month - not expired
        if ($currentMonth < 12) {
            $futureMonth = new CardMetadata(
                brand: CardBrand::VISA,
                lastFour: '4242',
                expiryMonth: $currentMonth + 1,
                expiryYear: $currentYear,
            );
            $this->assertFalse($futureMonth->isExpired());
        }
    }

    #[Test]
    public function it_generates_display_label(): void
    {
        $metadata = new CardMetadata(
            brand: CardBrand::AMEX,
            lastFour: '0005',
            expiryMonth: 6,
            expiryYear: 2025,
        );

        $this->assertSame('American Express •••• 0005', $metadata->getDisplayLabel());
    }

    #[Test]
    public function it_identifies_credit_cards(): void
    {
        $credit = new CardMetadata(
            brand: CardBrand::VISA,
            lastFour: '4242',
            expiryMonth: 12,
            expiryYear: 2030,
            funding: 'credit',
        );
        $this->assertTrue($credit->isCredit());
        $this->assertFalse($credit->isDebit());

        $debit = new CardMetadata(
            brand: CardBrand::VISA,
            lastFour: '4242',
            expiryMonth: 12,
            expiryYear: 2030,
            funding: 'debit',
        );
        $this->assertFalse($debit->isCredit());
        $this->assertTrue($debit->isDebit());

        $unknown = new CardMetadata(
            brand: CardBrand::VISA,
            lastFour: '4242',
            expiryMonth: 12,
            expiryYear: 2030,
        );
        $this->assertFalse($unknown->isCredit());
        $this->assertFalse($unknown->isDebit());
    }
}

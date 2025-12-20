<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\ValueObjects;

use Nexus\PaymentGateway\Enums\CardBrand;

/**
 * Metadata about a payment card (never contains full card number).
 *
 * SECURITY: Only stores safe-to-display information.
 */
final class CardMetadata
{
    /**
     * @param CardBrand $brand Card brand/network
     * @param string $lastFour Last 4 digits of card number
     * @param int $expiryMonth Expiry month (1-12)
     * @param int $expiryYear Expiry year (4-digit)
     * @param string|null $funding Card funding type (credit, debit, prepaid)
     * @param string|null $country Issuing country (ISO 3166-1 alpha-2)
     * @param string|null $fingerprint Card fingerprint for deduplication
     */
    public function __construct(
        public readonly CardBrand $brand,
        public readonly string $lastFour,
        public readonly int $expiryMonth,
        public readonly int $expiryYear,
        public readonly ?string $funding = null,
        public readonly ?string $country = null,
        public readonly ?string $fingerprint = null,
    ) {}

    /**
     * Create from gateway response array.
     *
     * @param array<string, mixed> $data Gateway response data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            brand: CardBrand::fromString($data['brand'] ?? 'unknown'),
            lastFour: (string) ($data['last4'] ?? $data['lastFour'] ?? $data['last_four'] ?? '0000'),
            expiryMonth: (int) ($data['exp_month'] ?? $data['expiryMonth'] ?? $data['expiry_month'] ?? 1),
            expiryYear: (int) ($data['exp_year'] ?? $data['expiryYear'] ?? $data['expiry_year'] ?? 2000),
            funding: $data['funding'] ?? null,
            country: $data['country'] ?? null,
            fingerprint: $data['fingerprint'] ?? null,
        );
    }

    /**
     * Get expiry as MM/YY format.
     */
    public function getExpiryDisplay(): string
    {
        return sprintf('%02d/%02d', $this->expiryMonth, $this->expiryYear % 100);
    }

    /**
     * Check if card is expired.
     */
    public function isExpired(): bool
    {
        $now = new \DateTimeImmutable();
        $currentYear = (int) $now->format('Y');
        $currentMonth = (int) $now->format('n');

        if ($this->expiryYear < $currentYear) {
            return true;
        }

        if ($this->expiryYear === $currentYear && $this->expiryMonth < $currentMonth) {
            return true;
        }

        return false;
    }

    /**
     * Get display label.
     */
    public function getDisplayLabel(): string
    {
        return sprintf('%s •••• %s', $this->brand->label(), $this->lastFour);
    }

    /**
     * Check if this is a credit card.
     */
    public function isCredit(): bool
    {
        return $this->funding === 'credit';
    }

    /**
     * Check if this is a debit card.
     */
    public function isDebit(): bool
    {
        return $this->funding === 'debit';
    }
}

<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\ValueObjects;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Enums\RailType;

/**
 * Describes the capabilities and limits of a payment rail.
 *
 * This value object encapsulates what a rail can do, including
 * supported currencies, amount limits, and timing characteristics.
 */
final class RailCapabilities
{
    /**
     * @param RailType $railType The payment rail type
     * @param array<string> $supportedCurrencies Supported currency codes
     * @param Money|null $minimumAmount Minimum transaction amount
     * @param Money|null $maximumAmount Maximum transaction amount
     * @param bool $supportsCredit Can process credit (push) transactions
     * @param bool $supportsDebit Can process debit (pull) transactions
     * @param bool $supportsScheduledPayments Can schedule future payments
     * @param bool $supportsRecurring Can set up recurring payments
     * @param bool $supportsBatchProcessing Can process in batches
     * @param bool $requiresPrenotification Requires prenote before first use
     * @param int $typicalSettlementDays Typical days to settle
     * @param int $cutoffTimeHour Daily cutoff hour (24-hour format)
     * @param int $cutoffTimeMinute Daily cutoff minute
     * @param string $cutoffTimezone Timezone for cutoff
     * @param array<string> $requiredFields Required fields for the rail
     * @param array<string, mixed> $additionalCapabilities Additional rail-specific caps
     */
    public function __construct(
        public readonly RailType $railType,
        public readonly array $supportedCurrencies,
        public readonly ?Money $minimumAmount = null,
        public readonly ?Money $maximumAmount = null,
        public readonly bool $supportsCredit = true,
        public readonly bool $supportsDebit = true,
        public readonly bool $supportsScheduledPayments = true,
        public readonly bool $supportsRecurring = true,
        public readonly bool $supportsBatchProcessing = true,
        public readonly bool $requiresPrenotification = false,
        public readonly int $typicalSettlementDays = 1,
        public readonly int $cutoffTimeHour = 17,
        public readonly int $cutoffTimeMinute = 0,
        public readonly string $cutoffTimezone = 'America/New_York',
        public readonly array $requiredFields = [],
        public readonly array $additionalCapabilities = [],
    ) {}

    /**
     * Create ACH capabilities.
     */
    public static function forAch(): self
    {
        return new self(
            railType: RailType::ACH,
            supportedCurrencies: ['USD'],
            minimumAmount: Money::of(0.01, 'USD'),
            maximumAmount: Money::of(99999999.99, 'USD'),
            supportsCredit: true,
            supportsDebit: true,
            supportsScheduledPayments: true,
            supportsRecurring: true,
            supportsBatchProcessing: true,
            requiresPrenotification: false,
            typicalSettlementDays: 2,
            cutoffTimeHour: 17,
            cutoffTimeMinute: 0,
            cutoffTimezone: 'America/New_York',
            requiredFields: ['routing_number', 'account_number', 'account_type'],
            additionalCapabilities: [
                'supports_addenda' => true,
                'max_addenda_records' => 9999,
                'supports_same_day' => true,
                'same_day_cutoff_hour' => 14,
            ],
        );
    }

    /**
     * Create domestic wire capabilities.
     */
    public static function forDomesticWire(): self
    {
        return new self(
            railType: RailType::WIRE,
            supportedCurrencies: ['USD'],
            minimumAmount: Money::of(1.00, 'USD'),
            maximumAmount: null, // No practical limit
            supportsCredit: true,
            supportsDebit: false, // Wire is push-only
            supportsScheduledPayments: true,
            supportsRecurring: false,
            supportsBatchProcessing: false,
            requiresPrenotification: false,
            typicalSettlementDays: 0, // Same day
            cutoffTimeHour: 17,
            cutoffTimeMinute: 0,
            cutoffTimezone: 'America/New_York',
            requiredFields: ['routing_number', 'account_number', 'beneficiary_name', 'beneficiary_bank_name'],
            additionalCapabilities: [
                'is_real_time' => true,
                'supports_intermediary_bank' => true,
            ],
        );
    }

    /**
     * Create international wire capabilities.
     */
    public static function forInternationalWire(): self
    {
        return new self(
            railType: RailType::WIRE,
            supportedCurrencies: ['USD', 'EUR', 'GBP', 'MYR', 'SGD', 'CAD', 'AUD', 'JPY', 'CHF'],
            minimumAmount: Money::of(1.00, 'USD'),
            maximumAmount: null,
            supportsCredit: true,
            supportsDebit: false,
            supportsScheduledPayments: true,
            supportsRecurring: false,
            supportsBatchProcessing: false,
            requiresPrenotification: false,
            typicalSettlementDays: 2,
            cutoffTimeHour: 15,
            cutoffTimeMinute: 0,
            cutoffTimezone: 'America/New_York',
            requiredFields: ['swift_code', 'beneficiary_name', 'beneficiary_bank_name', 'beneficiary_address'],
            additionalCapabilities: [
                'supports_iban' => true,
                'supports_intermediary_bank' => true,
                'requires_purpose_of_payment' => true,
            ],
        );
    }

    /**
     * Create check capabilities.
     */
    public static function forCheck(): self
    {
        return new self(
            railType: RailType::CHECK,
            supportedCurrencies: ['USD'],
            minimumAmount: Money::of(0.01, 'USD'),
            maximumAmount: Money::of(9999999.99, 'USD'),
            supportsCredit: true,
            supportsDebit: false,
            supportsScheduledPayments: true,
            supportsRecurring: true,
            supportsBatchProcessing: true,
            requiresPrenotification: false,
            typicalSettlementDays: 5,
            cutoffTimeHour: 23,
            cutoffTimeMinute: 59,
            cutoffTimezone: 'America/New_York',
            requiredFields: ['payee_name', 'payee_address'],
            additionalCapabilities: [
                'supports_positive_pay' => true,
                'supports_check_printing' => true,
            ],
        );
    }

    /**
     * Create RTGS capabilities.
     */
    public static function forRtgs(): self
    {
        return new self(
            railType: RailType::RTGS,
            supportedCurrencies: ['USD'],
            minimumAmount: Money::of(25000.00, 'USD'), // Typically high-value
            maximumAmount: null,
            supportsCredit: true,
            supportsDebit: false,
            supportsScheduledPayments: false,
            supportsRecurring: false,
            supportsBatchProcessing: false,
            requiresPrenotification: false,
            typicalSettlementDays: 0, // Real-time
            cutoffTimeHour: 18,
            cutoffTimeMinute: 0,
            cutoffTimezone: 'America/New_York',
            requiredFields: ['routing_number', 'account_number', 'beneficiary_name'],
            additionalCapabilities: [
                'is_real_time' => true,
                'is_irrevocable' => true,
            ],
        );
    }

    /**
     * Create virtual card capabilities.
     */
    public static function forVirtualCard(): self
    {
        return new self(
            railType: RailType::VIRTUAL_CARD,
            supportedCurrencies: ['USD', 'EUR', 'GBP', 'CAD'],
            minimumAmount: Money::of(0.01, 'USD'),
            maximumAmount: Money::of(250000.00, 'USD'),
            supportsCredit: true,
            supportsDebit: false,
            supportsScheduledPayments: true,
            supportsRecurring: true,
            supportsBatchProcessing: true,
            requiresPrenotification: false,
            typicalSettlementDays: 2,
            cutoffTimeHour: 23,
            cutoffTimeMinute: 59,
            cutoffTimezone: 'America/New_York',
            requiredFields: ['vendor_email', 'vendor_name'],
            additionalCapabilities: [
                'supports_single_use' => true,
                'supports_multi_use' => true,
                'supports_merchant_lock' => true,
                'max_card_validity_days' => 365,
            ],
        );
    }

    /**
     * Check if a currency is supported.
     */
    public function supportsCurrency(string $currencyCode): bool
    {
        return in_array(strtoupper($currencyCode), $this->supportedCurrencies, true);
    }

    /**
     * Check if an amount is within limits.
     */
    public function isAmountWithinLimits(Money $amount): bool
    {
        if ($this->minimumAmount !== null && $amount->lessThan($this->minimumAmount)) {
            return false;
        }

        if ($this->maximumAmount !== null && $amount->greaterThan($this->maximumAmount)) {
            return false;
        }

        return true;
    }

    /**
     * Check if current time is before cutoff.
     */
    public function isBeforeCutoff(?\DateTimeImmutable $dateTime = null): bool
    {
        $dateTime ??= new \DateTimeImmutable();
        $timezone = new \DateTimeZone($this->cutoffTimezone);
        $localTime = $dateTime->setTimezone($timezone);

        $cutoffTime = $localTime->setTime($this->cutoffTimeHour, $this->cutoffTimeMinute);

        return $localTime < $cutoffTime;
    }

    /**
     * Get the cutoff time as a formatted string.
     */
    public function getCutoffTimeFormatted(): string
    {
        return sprintf('%02d:%02d %s', $this->cutoffTimeHour, $this->cutoffTimeMinute, $this->cutoffTimezone);
    }

    /**
     * Check if a specific capability is available.
     */
    public function hasCapability(string $capability): bool
    {
        return isset($this->additionalCapabilities[$capability])
            && $this->additionalCapabilities[$capability] === true;
    }

    /**
     * Get a specific additional capability value.
     */
    public function getCapability(string $capability, mixed $default = null): mixed
    {
        return $this->additionalCapabilities[$capability] ?? $default;
    }

    /**
     * Check if this rail supports real-time settlement.
     */
    public function isRealTime(): bool
    {
        return $this->typicalSettlementDays === 0
            || ($this->additionalCapabilities['is_real_time'] ?? false);
    }
}

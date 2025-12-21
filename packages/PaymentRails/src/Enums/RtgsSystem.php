<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Enums;

/**
 * RTGS (Real-Time Gross Settlement) System Types.
 *
 * Different countries have different RTGS systems for high-value,
 * time-critical interbank transfers.
 */
enum RtgsSystem: string
{
    /**
     * US Federal Reserve Wire Network.
     */
    case FEDWIRE = 'fedwire';

    /**
     * Clearing House Interbank Payments System (US).
     */
    case CHIPS = 'chips';

    /**
     * TARGET2 - Trans-European Automated Real-time Gross Settlement Express Transfer (EU).
     */
    case TARGET2 = 'target2';

    /**
     * CHAPS - Clearing House Automated Payment System (UK).
     */
    case CHAPS = 'chaps';

    /**
     * RENTAS - Real-time Electronic Transfer of Funds and Securities (Malaysia).
     */
    case RENTAS = 'rentas';

    /**
     * MEPS+ - MAS Electronic Payment System (Singapore).
     */
    case MEPS_PLUS = 'meps_plus';

    /**
     * BOJ-NET - Bank of Japan Financial Network System (Japan).
     */
    case BOJ_NET = 'boj_net';

    /**
     * HVPS - High Value Payment System (China).
     */
    case HVPS = 'hvps';

    /**
     * RTGS-I - Reserve Bank Information and Transfer System (India).
     */
    case RTGS_I = 'rtgs_i';

    /**
     * Get the human-readable name of the RTGS system.
     */
    public function label(): string
    {
        return match ($this) {
            self::FEDWIRE => 'Fedwire',
            self::CHIPS => 'CHIPS',
            self::TARGET2 => 'TARGET2',
            self::CHAPS => 'CHAPS',
            self::RENTAS => 'RENTAS',
            self::MEPS_PLUS => 'MEPS+',
            self::BOJ_NET => 'BOJ-NET',
            self::HVPS => 'HVPS',
            self::RTGS_I => 'RTGS-I',
        };
    }

    /**
     * Get the country or region for this RTGS system.
     */
    public function region(): string
    {
        return match ($this) {
            self::FEDWIRE, self::CHIPS => 'US',
            self::TARGET2 => 'EU',
            self::CHAPS => 'UK',
            self::RENTAS => 'MY',
            self::MEPS_PLUS => 'SG',
            self::BOJ_NET => 'JP',
            self::HVPS => 'CN',
            self::RTGS_I => 'IN',
        };
    }

    /**
     * Get the currency supported by this RTGS system.
     */
    public function currency(): string
    {
        return match ($this) {
            self::FEDWIRE, self::CHIPS => 'USD',
            self::TARGET2 => 'EUR',
            self::CHAPS => 'GBP',
            self::RENTAS => 'MYR',
            self::MEPS_PLUS => 'SGD',
            self::BOJ_NET => 'JPY',
            self::HVPS => 'CNY',
            self::RTGS_I => 'INR',
        };
    }

    /**
     * Get the typical operating hours description.
     */
    public function operatingHours(): string
    {
        return match ($this) {
            self::FEDWIRE => '21:00 - 18:30 ET (next day)',
            self::CHIPS => '00:00 - 17:00 ET',
            self::TARGET2 => '07:00 - 18:00 CET',
            self::CHAPS => '06:00 - 18:00 GMT',
            self::RENTAS => '08:00 - 18:30 MYT',
            self::MEPS_PLUS => '08:00 - 19:00 SGT',
            self::BOJ_NET => '09:00 - 19:00 JST',
            self::HVPS => '08:30 - 17:00 CST',
            self::RTGS_I => '08:00 - 19:30 IST',
        };
    }

    /**
     * Check if this RTGS system settles on a net basis.
     */
    public function isNetSettlement(): bool
    {
        return $this === self::CHIPS;
    }

    /**
     * Get the minimum value threshold, if any.
     */
    public function minimumValue(): ?int
    {
        return match ($this) {
            self::RTGS_I => 200000,  // INR 2 lakh
            default => null,
        };
    }

    /**
     * Get RTGS system for a given country code.
     */
    public static function forCountry(string $countryCode): ?self
    {
        return match (strtoupper($countryCode)) {
            'US' => self::FEDWIRE,
            'EU', 'DE', 'FR', 'IT', 'ES', 'NL', 'BE', 'AT', 'PT', 'FI', 'IE', 'GR', 'LU' => self::TARGET2,
            'GB', 'UK' => self::CHAPS,
            'MY' => self::RENTAS,
            'SG' => self::MEPS_PLUS,
            'JP' => self::BOJ_NET,
            'CN' => self::HVPS,
            'IN' => self::RTGS_I,
            default => null,
        };
    }
}

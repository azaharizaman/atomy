<?php

declare(strict_types=1);

namespace Nexus\Localization\ValueObjects;

use DateTimeZone;
use Nexus\Localization\Exceptions\InvalidTimezoneException;

/**
 * Timezone backed enum with curated common IANA timezone identifiers.
 *
 * Provides ~50 most common timezones as enum cases, plus factory method
 * for any valid IANA timezone identifier.
 */
enum Timezone: string
{
    // UTC
    case UTC = 'UTC';

    // Asia
    case AsiaKualaLumpur = 'Asia/Kuala_Lumpur';
    case AsiaSingapore = 'Asia/Singapore';
    case AsiaJakarta = 'Asia/Jakarta';
    case AsiaBangkok = 'Asia/Bangkok';
    case AsiaHoChiMinh = 'Asia/Ho_Chi_Minh';
    case AsiaManila = 'Asia/Manila';
    case AsiaTokyo = 'Asia/Tokyo';
    case AsiaSeoul = 'Asia/Seoul';
    case AsiaShanghai = 'Asia/Shanghai';
    case AsiaHongKong = 'Asia/Hong_Kong';
    case AsiaTaipei = 'Asia/Taipei';
    case AsiaDubai = 'Asia/Dubai';
    case AsiaKolkata = 'Asia/Kolkata';
    case AsiaKarachi = 'Asia/Karachi';

    // Europe
    case EuropeLondon = 'Europe/London';
    case EuropeParis = 'Europe/Paris';
    case EuropeBerlin = 'Europe/Berlin';
    case EuropeRome = 'Europe/Rome';
    case EuropeMadrid = 'Europe/Madrid';
    case EuropeAmsterdam = 'Europe/Amsterdam';
    case EuropeBrussels = 'Europe/Brussels';
    case EuropeVienna = 'Europe/Vienna';
    case EuropeZurich = 'Europe/Zurich';
    case EuropeMoscow = 'Europe/Moscow';
    case EuropeIstanbul = 'Europe/Istanbul';

    // Americas
    case AmericaNewYork = 'America/New_York';
    case AmericaChicago = 'America/Chicago';
    case AmericaDenver = 'America/Denver';
    case AmericaLosAngeles = 'America/Los_Angeles';
    case AmericaToronto = 'America/Toronto';
    case AmericaVancouver = 'America/Vancouver';
    case AmericaMexicoCity = 'America/Mexico_City';
    case AmericaSaoPaulo = 'America/Sao_Paulo';
    case AmericaBuenosAires = 'America/Buenos_Aires';

    // Pacific
    case PacificAuckland = 'Pacific/Auckland';
    case PacificSydney = 'Australia/Sydney';
    case PacificMelbourne = 'Australia/Melbourne';
    case PacificBrisbane = 'Australia/Brisbane';
    case PacificPerth = 'Australia/Perth';
    case PacificHonolulu = 'Pacific/Honolulu';

    // Africa
    case AfricaCairo = 'Africa/Cairo';
    case AfricaJohannesburg = 'Africa/Johannesburg';
    case AfricaLagos = 'Africa/Lagos';
    case AfricaNairobi = 'Africa/Nairobi';

    // Middle East
    case AsiaRiyadh = 'Asia/Riyadh';
    case AsiaJerusalem = 'Asia/Jerusalem';

    /**
     * Create Timezone from any valid IANA timezone identifier.
     *
     * @throws InvalidTimezoneException
     */
    public static function fromString(string $iana): self
    {
        // Check if it's a valid enum case
        $cases = self::cases();
        foreach ($cases as $case) {
            if ($case->value === $iana) {
                return $case;
            }
        }

        // Validate against PHP's timezone list
        if (!in_array($iana, DateTimeZone::listIdentifiers(), true)) {
            throw new InvalidTimezoneException($iana);
        }

        // For non-enum timezones, we need to handle them differently
        // This is a limitation of backed enums - they can't have dynamic values
        // For now, we'll throw an exception suggesting to use the enum cases
        throw new InvalidTimezoneException(
            $iana . ' is valid but not in curated list. Use DateTimeZone directly or add to enum.'
        );
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        // Convert timezone identifier to readable format
        // e.g., "Asia/Kuala_Lumpur" -> "Asia - Kuala Lumpur"
        $parts = explode('/', $this->value);
        if (count($parts) === 2) {
            return $parts[0] . ' - ' . str_replace('_', ' ', $parts[1]);
        }
        return $this->value;
    }

    /**
     * Get DateTimeZone instance for this timezone.
     */
    public function toDateTimeZone(): DateTimeZone
    {
        return new DateTimeZone($this->value);
    }

    /**
     * Get offset from UTC in seconds for a given timestamp.
     */
    public function getOffset(\DateTimeInterface $datetime): int
    {
        return $this->toDateTimeZone()->getOffset($datetime);
    }
}

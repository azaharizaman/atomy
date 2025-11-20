<?php

declare(strict_types=1);

namespace Nexus\Localization\ValueObjects;

use Nexus\Localization\Exceptions\InvalidLocaleCodeException;

/**
 * Locale value object representing an IETF BCP 47 locale code.
 *
 * Format: language code (2 lowercase letters) optionally followed by
 * underscore and region code (2 uppercase letters).
 *
 * Examples: "en", "en_US", "ms_MY", "zh_CN"
 *
 * This value object enforces strict validation without normalization.
 */
final readonly class Locale
{
    private const PATTERN = '/^[a-z]{2}(_[A-Z]{2})?$/';

    private string $code;

    public function __construct(string $code)
    {
        if (!preg_match(self::PATTERN, $code)) {
            throw new InvalidLocaleCodeException($code);
        }

        $this->code = $code;
    }

    /**
     * Get the full locale code.
     */
    public function code(): string
    {
        return $this->code;
    }

    /**
     * Get the language portion (e.g., "en" from "en_US").
     */
    public function language(): string
    {
        $parts = explode('_', $this->code);
        return $parts[0];
    }

    /**
     * Get the region portion if present (e.g., "US" from "en_US").
     */
    public function region(): ?string
    {
        $parts = explode('_', $this->code);
        return $parts[1] ?? null;
    }

    /**
     * Check if this locale has a region code.
     */
    public function hasRegion(): bool
    {
        return $this->region() !== null;
    }

    /**
     * Check if this locale matches another locale code.
     */
    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }

    /**
     * String representation.
     */
    public function __toString(): string
    {
        return $this->code;
    }
}

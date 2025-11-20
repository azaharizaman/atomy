<?php

declare(strict_types=1);

namespace Nexus\Localization\ValueObjects;

use JsonSerializable;
use Nexus\Localization\Exceptions\CircularLocaleReferenceException;
use Nexus\Localization\Exceptions\UnsupportedLocaleException;

/**
 * Immutable fallback chain for locale resolution.
 *
 * Tracks visited locale codes to detect circular references and enforce
 * maximum depth limit (3 hops).
 *
 * Example: ms_MY → ms → en_US
 */
final readonly class FallbackChain implements JsonSerializable
{
    private const MAX_DEPTH = 3;

    /**
     * @param array<int, string> $visitedCodes
     */
    private function __construct(
        private array $visitedCodes,
    ) {
    }

    /**
     * Create a new fallback chain starting with the given locale.
     */
    public static function create(Locale $locale): self
    {
        return new self([$locale->code()]);
    }

    /**
     * Add a locale to the chain with cycle detection.
     *
     * @throws CircularLocaleReferenceException
     * @throws UnsupportedLocaleException
     */
    public function addLocale(Locale $locale): self
    {
        $code = $locale->code();

        // Check for circular reference
        if (in_array($code, $this->visitedCodes, true)) {
            throw new CircularLocaleReferenceException([...$this->visitedCodes, $code]);
        }

        $newCodes = [...$this->visitedCodes, $code];

        // Enforce maximum depth
        if (count($newCodes) > self::MAX_DEPTH) {
            throw new UnsupportedLocaleException(
                "Fallback chain exceeds maximum depth of " . self::MAX_DEPTH . ": " . implode(' → ', $newCodes)
            );
        }

        return new self($newCodes);
    }

    /**
     * Get all locale codes in the chain.
     *
     * @return array<int, string>
     */
    public function getCodes(): array
    {
        return $this->visitedCodes;
    }

    /**
     * Get the number of locales in the chain.
     */
    public function count(): int
    {
        return count($this->visitedCodes);
    }

    /**
     * Check if a locale code is in the chain.
     */
    public function contains(string $code): bool
    {
        return in_array($code, $this->visitedCodes, true);
    }

    /**
     * Get the first (original) locale in the chain.
     */
    public function first(): string
    {
        return $this->visitedCodes[0];
    }

    /**
     * Get the last (final fallback) locale in the chain.
     */
    public function last(): string
    {
        return $this->visitedCodes[count($this->visitedCodes) - 1];
    }

    /**
     * Format chain for display.
     */
    private function formatChain(): string
    {
        return implode(' → ', $this->visitedCodes);
    }

    /**
     * String representation.
     */
    public function __toString(): string
    {
        return $this->formatChain();
    }

    /**
     * JSON serialization.
     *
     * @return array<int, string>
     */
    public function jsonSerialize(): array
    {
        return $this->visitedCodes;
    }
}

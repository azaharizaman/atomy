<?php

declare(strict_types=1);

namespace Nexus\Accounting\Core\ValueObjects;

/**
 * Compliance standard identifier.
 *
 * Represents GAAP, IFRS, or custom accounting standards.
 */
final readonly class ComplianceStandard
{
    private const STANDARD_GAAP = 'GAAP';
    private const STANDARD_IFRS = 'IFRS';
    private const STANDARD_CUSTOM = 'CUSTOM';

    public function __construct(
        private string $code,
        private string $name,
        private string $version,
        private ?string $jurisdiction = null
    ) {
        if (!in_array($code, [self::STANDARD_GAAP, self::STANDARD_IFRS, self::STANDARD_CUSTOM], true)) {
            throw new \InvalidArgumentException("Invalid compliance standard code: {$code}");
        }
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getJurisdiction(): ?string
    {
        return $this->jurisdiction;
    }

    /**
     * Check if this is GAAP.
     */
    public function isGAAP(): bool
    {
        return $this->code === self::STANDARD_GAAP;
    }

    /**
     * Check if this is IFRS.
     */
    public function isIFRS(): bool
    {
        return $this->code === self::STANDARD_IFRS;
    }

    /**
     * Check if this is custom.
     */
    public function isCustom(): bool
    {
        return $this->code === self::STANDARD_CUSTOM;
    }

    /**
     * Create US GAAP standard.
     */
    public static function usGAAP(string $version = '2024'): self
    {
        return new self(self::STANDARD_GAAP, 'US GAAP', $version, 'US');
    }

    /**
     * Create IFRS standard.
     */
    public static function ifrs(string $version = '2024'): self
    {
        return new self(self::STANDARD_IFRS, 'IFRS', $version, 'IASB');
    }

    /**
     * Create custom standard.
     */
    public static function custom(string $name, string $version, ?string $jurisdiction = null): self
    {
        return new self(self::STANDARD_CUSTOM, $name, $version, $jurisdiction);
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'version' => $this->version,
            'jurisdiction' => $this->jurisdiction,
        ];
    }

    public function toString(): string
    {
        $parts = [$this->name, $this->version];
        if ($this->jurisdiction) {
            $parts[] = "({$this->jurisdiction})";
        }
        return implode(' ', $parts);
    }
}

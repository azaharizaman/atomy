<?php

declare(strict_types=1);

namespace Nexus\CashManagement\ValueObjects;

use InvalidArgumentException;

/**
 * AI Model Version Value Object
 *
 * Semantic versioning for AI models used in classification.
 */
final readonly class AIModelVersion
{
    public function __construct(
        private int $major,
        private int $minor,
        private int $patch
    ) {
        $this->validate();
    }

    /**
     * Validate version numbers
     */
    private function validate(): void
    {
        if ($this->major < 0 || $this->minor < 0 || $this->patch < 0) {
            throw new InvalidArgumentException('Version numbers cannot be negative');
        }
    }

    public function getMajor(): int
    {
        return $this->major;
    }

    public function getMinor(): int
    {
        return $this->minor;
    }

    public function getPatch(): int
    {
        return $this->patch;
    }

    /**
     * Get version string (e.g., "1.0.0")
     */
    public function toString(): string
    {
        return sprintf('%d.%d.%d', $this->major, $this->minor, $this->patch);
    }

    /**
     * Create from version string
     */
    public static function fromString(string $version): self
    {
        if (!preg_match('/^(\d+)\.(\d+)\.(\d+)$/', $version, $matches)) {
            throw new InvalidArgumentException('Invalid version format. Expected: major.minor.patch');
        }

        return new self(
            major: (int) $matches[1],
            minor: (int) $matches[2],
            patch: (int) $matches[3]
        );
    }

    /**
     * Compare with another version
     */
    public function isNewerThan(self $other): bool
    {
        if ($this->major !== $other->major) {
            return $this->major > $other->major;
        }

        if ($this->minor !== $other->minor) {
            return $this->minor > $other->minor;
        }

        return $this->patch > $other->patch;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}

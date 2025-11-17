<?php

declare(strict_types=1);

namespace Nexus\Setting\ValueObjects;

/**
 * Represents an encrypted setting value.
 *
 * This immutable value object marks a setting value as requiring encryption.
 * The actual encryption/decryption is handled by the application layer.
 */
final readonly class EncryptedSetting
{
    /**
     * Create a new encrypted setting value.
     *
     * @param mixed $value The value to be encrypted
     * @param bool $isEncrypted Whether the value is already encrypted
     */
    public function __construct(
        public mixed $value,
        public bool $isEncrypted = false,
    ) {
    }

    /**
     * Create from plaintext value (to be encrypted).
     */
    public static function fromPlaintext(mixed $value): self
    {
        return new self($value, false);
    }

    /**
     * Create from already encrypted value.
     */
    public static function fromEncrypted(string $encryptedValue): self
    {
        return new self($encryptedValue, true);
    }

    /**
     * Get the raw value.
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Check if the value requires encryption.
     */
    public function needsEncryption(): bool
    {
        return ! $this->isEncrypted;
    }

    /**
     * Check if the value is already encrypted.
     */
    public function isAlreadyEncrypted(): bool
    {
        return $this->isEncrypted;
    }
}

<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\ValueObjects;

use Nexus\DataPrivacy\Exceptions\InvalidDataSubjectIdException;

/**
 * Unique identifier for a data subject.
 *
 * This is separate from Party ID to allow privacy operations
 * on data subjects who may not be in the Party system (e.g., website visitors).
 */
final class DataSubjectId
{
    /**
     * @param string $value The unique identifier value
     */
    public function __construct(
        public readonly string $value,
    ) {
        if (trim($value) === '') {
            throw new InvalidDataSubjectIdException('Data subject ID cannot be empty');
        }

        if (strlen($value) > 255) {
            throw new InvalidDataSubjectIdException('Data subject ID cannot exceed 255 characters');
        }
    }

    /**
     * Create from a party ID (when data subject is in Party system).
     */
    public static function fromPartyId(string $partyId): self
    {
        return new self('party:' . $partyId);
    }

    /**
     * Create from an email address (for anonymous/external data subjects).
     */
    public static function fromEmail(string $email): self
    {
        $normalized = strtolower(trim($email));

        return new self('email:' . hash('sha256', $normalized));
    }

    /**
     * Create from a custom external identifier.
     */
    public static function fromExternal(string $system, string $externalId): self
    {
        return new self($system . ':' . $externalId);
    }

    /**
     * Check if this ID references a Party system entity.
     */
    public function isPartyReference(): bool
    {
        return str_starts_with($this->value, 'party:');
    }

    /**
     * Alias for isPartyReference() for backward compatibility.
     */
    public function isPartyId(): bool
    {
        return $this->isPartyReference();
    }

    /**
     * Get the Party ID if this references the Party system.
     */
    public function getPartyId(): ?string
    {
        if (!$this->isPartyReference()) {
            return null;
        }

        return substr($this->value, 6);
    }

    /**
     * Check if this ID references an email-based data subject.
     */
    public function isEmailReference(): bool
    {
        return str_starts_with($this->value, 'email:');
    }

    /**
     * Get the underlying value.
     *
     * Provided for consistency with other value objects that use accessor methods.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Check equality with another DataSubjectId.
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

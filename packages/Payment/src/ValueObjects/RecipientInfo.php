<?php

declare(strict_types=1);

namespace Nexus\Payment\ValueObjects;

use Nexus\Payment\Exceptions\InvalidRecipientInfoException;

/**
 * Disbursement recipient information value object.
 *
 * Contains the details of the recipient for an outbound payment/disbursement.
 */
final readonly class RecipientInfo
{
    /**
     * @param string $id Unique recipient identifier (party ID, vendor ID, employee ID)
     * @param string $name Recipient display name
     * @param string|null $accountId Payment instrument/account ID for the recipient
     * @param string|null $email Recipient email for notifications
     * @param array<string, mixed> $metadata Additional recipient metadata
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $accountId = null,
        public ?string $email = null,
        public array $metadata = [],
    ) {
        if (trim($id) === '') {
            throw new InvalidRecipientInfoException('Recipient ID cannot be empty');
        }

        if (trim($name) === '') {
            throw new InvalidRecipientInfoException('Recipient name cannot be empty');
        }

        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidRecipientInfoException('Invalid recipient email format');
        }
    }

    /**
     * Create recipient info with basic details.
     */
    public static function create(string $id, string $name): self
    {
        return new self($id, $name);
    }

    /**
     * Create recipient info with account.
     */
    public static function withAccount(string $id, string $name, string $accountId): self
    {
        return new self($id, $name, $accountId);
    }

    /**
     * Check if recipient has a linked payment account.
     */
    public function hasAccount(): bool
    {
        return $this->accountId !== null;
    }

    /**
     * Check if recipient has email for notifications.
     */
    public function hasEmail(): bool
    {
        return $this->email !== null;
    }

    /**
     * Get a metadata value.
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Create a copy with email.
     */
    public function withEmail(string $email): self
    {
        return new self(
            $this->id,
            $this->name,
            $this->accountId,
            $email,
            $this->metadata,
        );
    }

    /**
     * Create a copy with additional metadata.
     *
     * @param array<string, mixed> $metadata
     */
    public function withMetadata(array $metadata): self
    {
        return new self(
            $this->id,
            $this->name,
            $this->accountId,
            $this->email,
            array_merge($this->metadata, $metadata),
        );
    }

    /**
     * Check equality with another recipient.
     */
    public function equals(self $other): bool
    {
        return $this->id === $other->id;
    }
}

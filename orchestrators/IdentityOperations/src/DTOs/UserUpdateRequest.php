<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DTOs;

/**
 * Request DTO for user update with per-field presence tracking.
 * 
 * Immutable implementation using wither methods.
 */
final readonly class UserUpdateRequest
{
    /**
     * @param array<string, bool> $presentFields
     */
    private function __construct(
        public string $userId,
        private array $presentFields = [],
        private ?string $firstName = null,
        private ?string $lastName = null,
        private ?string $phone = null,
        private ?string $locale = null,
        private ?string $timezone = null,
        private ?array $metadata = null,
        private ?string $updatedBy = null,
    ) {}

    public static function create(string $userId): self
    {
        return new self($userId);
    }

    public function setFirstName(?string $firstName): self
    {
        return new self(
            userId: $this->userId,
            presentFields: array_merge($this->presentFields, ['first_name' => true]),
            firstName: $firstName,
            lastName: $this->lastName,
            phone: $this->phone,
            locale: $this->locale,
            timezone: $this->timezone,
            metadata: $this->metadata,
            updatedBy: $this->updatedBy,
        );
    }

    public function setLastName(?string $lastName): self
    {
        return new self(
            userId: $this->userId,
            presentFields: array_merge($this->presentFields, ['last_name' => true]),
            firstName: $this->firstName,
            lastName: $lastName,
            phone: $this->phone,
            locale: $this->locale,
            timezone: $this->timezone,
            metadata: $this->metadata,
            updatedBy: $this->updatedBy,
        );
    }

    public function setPhone(?string $phone): self
    {
        return new self(
            userId: $this->userId,
            presentFields: array_merge($this->presentFields, ['phone' => true]),
            firstName: $this->firstName,
            lastName: $this->lastName,
            phone: $phone,
            locale: $this->locale,
            timezone: $this->timezone,
            metadata: $this->metadata,
            updatedBy: $this->updatedBy,
        );
    }

    public function setLocale(?string $locale): self
    {
        return new self(
            userId: $this->userId,
            presentFields: array_merge($this->presentFields, ['locale' => true]),
            firstName: $this->firstName,
            lastName: $this->lastName,
            phone: $this->phone,
            locale: $locale,
            timezone: $this->timezone,
            metadata: $this->metadata,
            updatedBy: $this->updatedBy,
        );
    }

    public function setTimezone(?string $timezone): self
    {
        return new self(
            userId: $this->userId,
            presentFields: array_merge($this->presentFields, ['timezone' => true]),
            firstName: $this->firstName,
            lastName: $this->lastName,
            phone: $this->phone,
            locale: $this->locale,
            timezone: $timezone,
            metadata: $this->metadata,
            updatedBy: $this->updatedBy,
        );
    }

    public function setMetadata(?array $metadata): self
    {
        return new self(
            userId: $this->userId,
            presentFields: array_merge($this->presentFields, ['metadata' => true]),
            firstName: $this->firstName,
            lastName: $this->lastName,
            phone: $this->phone,
            locale: $this->locale,
            timezone: $this->timezone,
            metadata: $metadata,
            updatedBy: $this->updatedBy,
        );
    }

    public function setUpdatedBy(?string $updatedBy): self
    {
        return new self(
            userId: $this->userId,
            presentFields: array_merge($this->presentFields, ['updated_by' => true]),
            firstName: $this->firstName,
            lastName: $this->lastName,
            phone: $this->phone,
            locale: $this->locale,
            timezone: $this->timezone,
            metadata: $this->metadata,
            updatedBy: $updatedBy,
        );
    }

    public function has(string $field): bool
    {
        return isset($this->presentFields[$field]);
    }

    public function getFirstName(): ?string { return $this->firstName; }
    public function getLastName(): ?string { return $this->lastName; }
    public function getPhone(): ?string { return $this->phone; }
    public function getLocale(): ?string { return $this->locale; }
    public function getTimezone(): ?string { return $this->timezone; }
    public function getMetadata(): ?array { return $this->metadata; }
    public function getUpdatedBy(): ?string { return $this->updatedBy; }
    
    public function toArray(): array
    {
        $data = [];
        if ($this->has('first_name')) {
            $data['first_name'] = $this->firstName;
        }
        if ($this->has('last_name')) {
            $data['last_name'] = $this->lastName;
        }
        if ($this->has('phone')) {
            $data['phone'] = $this->phone;
        }
        if ($this->has('locale')) {
            $data['locale'] = $this->locale;
        }
        if ($this->has('timezone')) {
            $data['timezone'] = $this->timezone;
        }
        if ($this->has('metadata')) {
            $data['metadata'] = $this->metadata;
        }
        if ($this->has('updated_by')) {
            $data['updated_by'] = $this->updatedBy;
        }
        return $data;
    }
}

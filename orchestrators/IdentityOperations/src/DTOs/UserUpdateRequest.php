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

    private function rebuild(array $overrides, array $presence): self
    {
        return new self(
            userId: $this->userId,
            presentFields: array_merge($this->presentFields, $presence),
            firstName: array_key_exists('firstName', $overrides) ? $overrides['firstName'] : $this->firstName,
            lastName: array_key_exists('lastName', $overrides) ? $overrides['lastName'] : $this->lastName,
            phone: array_key_exists('phone', $overrides) ? $overrides['phone'] : $this->phone,
            locale: array_key_exists('locale', $overrides) ? $overrides['locale'] : $this->locale,
            timezone: array_key_exists('timezone', $overrides) ? $overrides['timezone'] : $this->timezone,
            metadata: array_key_exists('metadata', $overrides) ? $overrides['metadata'] : $this->metadata,
            updatedBy: array_key_exists('updatedBy', $overrides) ? $overrides['updatedBy'] : $this->updatedBy,
        );
    }

    public function setFirstName(?string $firstName): self
    {
        return $this->rebuild(['firstName' => $firstName], ['first_name' => true]);
    }

    public function setLastName(?string $lastName): self
    {
        return $this->rebuild(['lastName' => $lastName], ['last_name' => true]);
    }

    public function setPhone(?string $phone): self
    {
        return $this->rebuild(['phone' => $phone], ['phone' => true]);
    }

    public function setLocale(?string $locale): self
    {
        return $this->rebuild(['locale' => $locale], ['locale' => true]);
    }

    public function setTimezone(?string $timezone): self
    {
        return $this->rebuild(['timezone' => $timezone], ['timezone' => true]);
    }

    public function setMetadata(?array $metadata): self
    {
        return $this->rebuild(['metadata' => $metadata], ['metadata' => true]);
    }

    public function setUpdatedBy(?string $updatedBy): self
    {
        return $this->rebuild(['updatedBy' => $updatedBy], ['updated_by' => true]);
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

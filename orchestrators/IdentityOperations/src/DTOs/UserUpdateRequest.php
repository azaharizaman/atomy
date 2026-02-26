<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DTOs;

/**
 * Request DTO for user update with per-field presence tracking.
 */
final class UserUpdateRequest
{
    private array $presentFields = [];

    private function __construct(
        public readonly string $userId,
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
        $this->firstName = $firstName;
        $this->presentFields['first_name'] = true;
        return $this;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;
        $this->presentFields['last_name'] = true;
        return $this;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        $this->presentFields['phone'] = true;
        return $this;
    }

    public function setLocale(?string $locale): self
    {
        $this->locale = $locale;
        $this->presentFields['locale'] = true;
        return $this;
    }

    public function setTimezone(?string $timezone): self
    {
        $this->timezone = $timezone;
        $this->presentFields['timezone'] = true;
        return $this;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
        $this->presentFields['metadata'] = true;
        return $this;
    }

    public function setUpdatedBy(?string $updatedBy): self
    {
        $this->updatedBy = $updatedBy;
        $this->presentFields['updated_by'] = true;
        return $this;
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

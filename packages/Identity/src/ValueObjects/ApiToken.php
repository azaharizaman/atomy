<?php

declare(strict_types=1);

namespace Nexus\Identity\ValueObjects;

/**
 * API token value object
 * 
 * Represents an API authentication token
 */
final readonly class ApiToken
{
    /**
     * Create new API token
     * 
     * @param string[] $scopes
     */
    public function __construct(
        public string $id,
        public string $token,
        public string $userId,
        public string $name,
        public array $scopes,
        public ?\DateTimeInterface $expiresAt = null
    ) {
        if (empty($this->id)) {
            throw new \InvalidArgumentException('Token ID cannot be empty');
        }

        if (empty($this->token)) {
            throw new \InvalidArgumentException('Token cannot be empty');
        }

        if (empty($this->userId)) {
            throw new \InvalidArgumentException('User ID cannot be empty');
        }

        if (empty($this->name)) {
            throw new \InvalidArgumentException('Token name cannot be empty');
        }
    }

    /**
     * Check if token is expired
     */
    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false; // Permanent token
        }

        return $this->expiresAt < new \DateTimeImmutable();
    }

    /**
     * Check if token is valid
     */
    public function isValid(): bool
    {
        return !$this->isExpired();
    }

    /**
     * Check if token has a specific scope
     */
    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }

    /**
     * Check if token has any of the given scopes
     * 
     * @param string[] $scopes
     */
    public function hasAnyScope(array $scopes): bool
    {
        return !empty(array_intersect($scopes, $this->scopes));
    }

    /**
     * Check if token is permanent (no expiration)
     */
    public function isPermanent(): bool
    {
        return $this->expiresAt === null;
    }

    /**
     * Convert to array
     * 
     * @return array{id: string, token: string, user_id: string, name: string, scopes: string[], expires_at: string|null}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'token' => $this->token,
            'user_id' => $this->userId,
            'name' => $this->name,
            'scopes' => $this->scopes,
            'expires_at' => $this->expiresAt?->format('Y-m-d H:i:s'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\ValueObjects;

/**
 * Credentials value object
 * 
 * Represents user authentication credentials
 */
final readonly class Credentials
{
    /**
     * Create new credentials
     */
    public function __construct(
        public string $email,
        public string $password
    ) {
        if (empty($this->email)) {
            throw new \InvalidArgumentException('Email cannot be empty');
        }

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }

        if (empty($this->password)) {
            throw new \InvalidArgumentException('Password cannot be empty');
        }
    }

    /**
     * Create from array
     * 
     * @param array{email: string, password: string} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'] ?? '',
            password: $data['password'] ?? ''
        );
    }

    /**
     * Convert to array
     * 
     * @return array{email: string, password: string}
     */
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
        ];
    }
}

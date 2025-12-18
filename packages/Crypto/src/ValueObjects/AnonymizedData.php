<?php

declare(strict_types=1);

namespace Nexus\Crypto\ValueObjects;

use DateTimeImmutable;
use Nexus\Crypto\Enums\AnonymizationMethod;

/**
 * Anonymized Data Value Object
 *
 * Represents the result of an irreversible anonymization operation.
 * The original data cannot be recovered from this object under any circumstances.
 *
 * This value object is immutable and provides serialization for storage.
 *
 * @see https://gdpr-info.eu/recitals/no-26/ GDPR definition - data is no longer personal data
 */
final class AnonymizedData
{
    /**
     * @param string $anonymizedValue The resulting anonymized value (e.g., hash output)
     * @param AnonymizationMethod $method Method used for anonymization
     * @param DateTimeImmutable $anonymizedAt Timestamp of anonymization operation
     * @param string|null $salt Random salt used (for salted hash method), stored for verification only
     * @param array<string, mixed> $metadata Additional context (e.g., data type hint, purpose)
     */
    public function __construct(
        public readonly string $anonymizedValue,
        public readonly AnonymizationMethod $method,
        public readonly DateTimeImmutable $anonymizedAt,
        public readonly ?string $salt = null,
        public readonly array $metadata = [],
    ) {}

    /**
     * Check if anonymization used deterministic method
     *
     * Deterministic anonymization produces the same output for the same input,
     * allowing correlation of anonymized records.
     */
    public function isDeterministic(): bool
    {
        return $this->method->isDeterministic();
    }

    /**
     * Check if this anonymization can be correlated with another
     *
     * Only deterministic methods without random salt can be correlated.
     * Suppression is excluded as all suppressed values are identical.
     */
    public function isCorrelatable(): bool
    {
        // Suppression produces identical outputs for all inputs, making correlation meaningless
        if ($this->method === AnonymizationMethod::SUPPRESSION) {
            return false;
        }
        
        return $this->isDeterministic() && $this->salt === null;
    }

    /**
     * Get the security level of the anonymization
     *
     * @return string 'high' | 'medium' | 'low'
     */
    public function getSecurityLevel(): string
    {
        return $this->method->getSecurityLevel();
    }

    /**
     * Serialize for storage/transmission
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'anonymized_value' => $this->anonymizedValue,
            'method' => $this->method->value,
            'anonymized_at' => $this->anonymizedAt->format(DateTimeImmutable::ATOM),
            'salt' => $this->salt,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Reconstruct from serialized data
     *
     * @param array<string, mixed> $data
     * @return self
     * @throws \InvalidArgumentException If data is malformed
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['anonymized_value'], $data['method'], $data['anonymized_at'])) {
            throw new \InvalidArgumentException(
                'AnonymizedData requires anonymized_value, method, and anonymized_at fields'
            );
        }

        $anonymizedAt = DateTimeImmutable::createFromFormat(
            DateTimeImmutable::ATOM,
            $data['anonymized_at']
        );

        if ($anonymizedAt === false) {
            throw new \InvalidArgumentException(
                'Invalid anonymized_at format, expected ATOM (ISO 8601)'
            );
        }

        return new self(
            anonymizedValue: $data['anonymized_value'],
            method: AnonymizationMethod::from($data['method']),
            anonymizedAt: $anonymizedAt,
            salt: $data['salt'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Create a JSON-serializable representation
     */
    public function jsonSerialize(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * Compare two anonymized values for equality
     *
     * Uses constant-time comparison to prevent timing attacks.
     */
    public function equals(self $other): bool
    {
        if ($this->method !== $other->method) {
            return false;
        }

        return hash_equals($this->anonymizedValue, $other->anonymizedValue);
    }
}

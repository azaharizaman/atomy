<?php

declare(strict_types=1);

namespace Nexus\Crypto\ValueObjects;

use DateTimeImmutable;

/**
 * Pseudonymized Data Value Object
 *
 * Represents the result of a reversible pseudonymization operation.
 * The original data CAN be recovered with the proper key.
 *
 * This is NOT anonymization - the data is still considered personal data under GDPR
 * because it can be re-identified with additional information (the key).
 *
 * @see https://gdpr-info.eu/art-4-gdpr/ GDPR Article 4(5) - Definition of pseudonymization
 */
final class PseudonymizedData
{
    /**
     * @param string $pseudonym The pseudonymized value (encrypted form of original data)
     * @param string $keyId Identifier of the key used for pseudonymization
     * @param int $keyVersion Version of the key (for key rotation support)
     * @param DateTimeImmutable $pseudonymizedAt Timestamp of pseudonymization
     * @param array<string, mixed> $metadata Additional context (e.g., original data type, format hints)
     */
    public function __construct(
        public readonly string $pseudonym,
        public readonly string $keyId,
        public readonly int $keyVersion,
        public readonly DateTimeImmutable $pseudonymizedAt,
        public readonly array $metadata = [],
    ) {}

    /**
     * Check if pseudonymization uses the current/latest key version
     *
     * Used to determine if re-pseudonymization with a newer key is needed.
     *
     * @param int $currentVersion The current version of the key in storage
     */
    public function isCurrentKeyVersion(int $currentVersion): bool
    {
        return $this->keyVersion === $currentVersion;
    }

    /**
     * Check if this pseudonymized data needs key rotation
     *
     * Returns true if the key version is older than the current version.
     *
     * @param int $currentVersion The current version of the key
     * @param int $maxVersionsBehind Maximum allowed version difference (default: 1)
     */
    public function needsKeyRotation(int $currentVersion, int $maxVersionsBehind = 1): bool
    {
        return ($currentVersion - $this->keyVersion) > $maxVersionsBehind;
    }

    /**
     * Serialize for storage/transmission
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'pseudonym' => $this->pseudonym,
            'key_id' => $this->keyId,
            'key_version' => $this->keyVersion,
            'pseudonymized_at' => $this->pseudonymizedAt->format(DateTimeImmutable::ATOM),
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
        $requiredFields = ['pseudonym', 'key_id', 'key_version', 'pseudonymized_at'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException(
                    "PseudonymizedData requires {$field} field"
                );
            }
        }

        $pseudonymizedAt = DateTimeImmutable::createFromFormat(
            DateTimeImmutable::ATOM,
            $data['pseudonymized_at']
        );

        if ($pseudonymizedAt === false) {
            throw new \InvalidArgumentException(
                'Invalid pseudonymized_at format, expected ATOM (ISO 8601)'
            );
        }

        return new self(
            pseudonym: $data['pseudonym'],
            keyId: $data['key_id'],
            keyVersion: (int) $data['key_version'],
            pseudonymizedAt: $pseudonymizedAt,
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
     * Get a compact identifier for logging (doesn't expose the pseudonym value)
     *
     * Format: "keyId:v{version}@{timestamp}"
     */
    public function getLogIdentifier(): string
    {
        return sprintf(
            '%s:v%d@%s',
            $this->keyId,
            $this->keyVersion,
            $this->pseudonymizedAt->format('Y-m-d')
        );
    }
}

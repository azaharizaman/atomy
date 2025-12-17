<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\ValueObjects;

use DateTimeImmutable;
use Nexus\DataPrivacy\Enums\ConsentPurpose;
use Nexus\DataPrivacy\Enums\ConsentStatus;
use Nexus\DataPrivacy\Exceptions\InvalidConsentException;

/**
 * Represents consent given by a data subject for a specific purpose.
 *
 * Consent must be:
 * - Freely given
 * - Specific (to a purpose)
 * - Informed (data subject understands what they're consenting to)
 * - Unambiguous (clear affirmative action)
 */
final class Consent
{
    /**
     * @param string $id Unique consent identifier
     * @param DataSubjectId $dataSubjectId The data subject who gave consent
     * @param ConsentPurpose $purpose The purpose for which consent was given
     * @param ConsentStatus $status Current status of the consent
     * @param DateTimeImmutable $grantedAt When consent was granted
     * @param DateTimeImmutable|null $expiresAt When consent expires (null = no expiry)
     * @param DateTimeImmutable|null $withdrawnAt When consent was withdrawn
     * @param string $version Version of privacy policy/consent form
     * @param string|null $ipAddress IP address from which consent was given
     * @param string|null $userAgent User agent from which consent was given
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public readonly string $id,
        public readonly DataSubjectId $dataSubjectId,
        public readonly ConsentPurpose $purpose,
        public readonly ConsentStatus $status,
        public readonly DateTimeImmutable $grantedAt,
        public readonly ?DateTimeImmutable $expiresAt = null,
        public readonly ?DateTimeImmutable $withdrawnAt = null,
        public readonly string $version = '1.0',
        public readonly ?string $ipAddress = null,
        public readonly ?string $userAgent = null,
        public readonly array $metadata = [],
    ) {
        if (trim($id) === '') {
            throw new InvalidConsentException('Consent ID cannot be empty');
        }

        if ($this->expiresAt !== null && $this->expiresAt <= $this->grantedAt) {
            throw new InvalidConsentException('Expiry date must be after grant date');
        }

        if ($this->withdrawnAt !== null && $this->withdrawnAt < $this->grantedAt) {
            throw new InvalidConsentException('Withdrawal date cannot be before grant date');
        }
    }

    /**
     * Create a new granted consent.
     */
    public static function grant(
        DataSubjectId $dataSubjectId,
        ConsentPurpose $purpose,
        int|string $version = 1,
        ?DateTimeImmutable $expiresAt = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        array $metadata = [],
    ): self {
        return new self(
            id: bin2hex(random_bytes(16)),
            dataSubjectId: $dataSubjectId,
            purpose: $purpose,
            status: ConsentStatus::GRANTED,
            grantedAt: new DateTimeImmutable(),
            expiresAt: $expiresAt,
            version: (string) $version,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            metadata: $metadata,
        );
    }

    /**
     * Check if consent is currently valid for processing.
     */
    public function isValid(?DateTimeImmutable $asOf = null): bool
    {
        $asOf ??= new DateTimeImmutable();

        if ($this->status !== ConsentStatus::GRANTED) {
            return false;
        }

        if ($this->expiresAt !== null && $asOf > $this->expiresAt) {
            return false;
        }

        return true;
    }

    /**
     * Check if consent has expired.
     */
    public function isExpired(?DateTimeImmutable $asOf = null): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        $asOf ??= new DateTimeImmutable();

        return $asOf > $this->expiresAt;
    }

    /**
     * Create a new Consent with withdrawn status.
     */
    public function withdraw(DateTimeImmutable $withdrawnAt): self
    {
        return new self(
            id: $this->id,
            dataSubjectId: $this->dataSubjectId,
            purpose: $this->purpose,
            status: ConsentStatus::WITHDRAWN,
            grantedAt: $this->grantedAt,
            expiresAt: $this->expiresAt,
            withdrawnAt: $withdrawnAt,
            version: $this->version,
            ipAddress: $this->ipAddress,
            userAgent: $this->userAgent,
            metadata: $this->metadata,
        );
    }

    /**
     * Create a new Consent with expired status.
     */
    public function markExpired(): self
    {
        return new self(
            id: $this->id,
            dataSubjectId: $this->dataSubjectId,
            purpose: $this->purpose,
            status: ConsentStatus::EXPIRED,
            grantedAt: $this->grantedAt,
            expiresAt: $this->expiresAt,
            withdrawnAt: $this->withdrawnAt,
            version: $this->version,
            ipAddress: $this->ipAddress,
            userAgent: $this->userAgent,
            metadata: $this->metadata,
        );
    }

    /**
     * Get the duration of consent in days (if not expired/withdrawn).
     */
    public function getDurationDays(): ?int
    {
        if ($this->expiresAt === null) {
            return null;
        }

        return $this->grantedAt->diff($this->expiresAt)->days;
    }

    /**
     * Get days remaining until expiry.
     */
    public function getDaysUntilExpiry(?DateTimeImmutable $asOf = null): ?int
    {
        if ($this->expiresAt === null) {
            return null;
        }

        $asOf ??= new DateTimeImmutable();

        if ($asOf > $this->expiresAt) {
            return 0;
        }

        return $asOf->diff($this->expiresAt)->days;
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'data_subject_id' => $this->dataSubjectId->value,
            'purpose' => $this->purpose->value,
            'status' => $this->status->value,
            'granted_at' => $this->grantedAt->format(DateTimeImmutable::ATOM),
            'expires_at' => $this->expiresAt?->format(DateTimeImmutable::ATOM),
            'withdrawn_at' => $this->withdrawnAt?->format(DateTimeImmutable::ATOM),
            'version' => $this->version,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'metadata' => $this->metadata,
        ];
    }
}

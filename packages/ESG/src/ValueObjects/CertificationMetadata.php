<?php

declare(strict_types=1);

namespace Nexus\ESG\ValueObjects;

use Nexus\Common\Contracts\SerializableVO;

/**
 * Immutable value object for sustainability certification metadata.
 */
final readonly class CertificationMetadata implements SerializableVO
{
    /**
     * @param string $issuer Name of the issuing body (e.g., 'ISO', 'SAI')
     * @param string $certificateType The type/standard (e.g., 'ISO 14001', 'SA8000')
     * @param string $certificateNumber Unique identifier
     * @param \DateTimeImmutable $issuedAt Date of issuance
     * @param \DateTimeImmutable|null $expiresAt Expiry date, if any
     */
    public function __construct(
        public string $issuer,
        public string $certificateType,
        public string $certificateNumber,
        public \DateTimeImmutable $issuedAt,
        public ?\DateTimeImmutable $expiresAt = null
    ) {
    }

    /**
     * Check if the certificate is currently active based on a specific date.
     */
    public function isActiveAt(\DateTimeImmutable $date): bool
    {
        if ($date < $this->issuedAt) {
            return false;
        }

        if ($this->expiresAt !== null && $date > $this->expiresAt) {
            return false;
        }

        return true;
    }

    public function toArray(): array
    {
        return [
            'issuer' => $this->issuer,
            'certificate_type' => $this->certificateType,
            'certificate_number' => $this->certificateNumber,
            'issued_at' => $this->issuedAt->format(\DateTimeInterface::ATOM),
            'expires_at' => $this->expiresAt?->format(\DateTimeInterface::ATOM),
        ];
    }

    public function toString(): string
    {
        return sprintf('%s: %s (#%s)', $this->issuer, $this->certificateType, $this->certificateNumber);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public static function fromArray(array $data): static
    {
        return new self(
            issuer: $data['issuer'],
            certificateType: $data['certificate_type'],
            certificateNumber: $data['certificate_number'],
            issuedAt: new \DateTimeImmutable($data['issued_at']),
            expiresAt: isset($data['expires_at']) ? new \DateTimeImmutable($data['expires_at']) : null
        );
    }
}

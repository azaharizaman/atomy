<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Vendor;

/**
 * Vendor certification data DTO.
 */
final readonly class VendorCertificationData
{
    /**
     * @param string $certificationId Unique certification record ID
     * @param string $code Certification code (e.g., ISO9001, ISO14001)
     * @param string $name Certification name
     * @param string $issuingBody Certification issuing body
     * @param string $certificateNumber Certificate number
     * @param \DateTimeImmutable $issuedAt Date certification was issued
     * @param \DateTimeImmutable $expiresAt Date certification expires
     * @param string $status Certification status (active, expired, revoked)
     * @param string|null $documentUrl URL to uploaded certificate document
     * @param string|null $scope Scope of certification
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $certificationId,
        public string $code,
        public string $name,
        public string $issuingBody,
        public string $certificateNumber,
        public \DateTimeImmutable $issuedAt,
        public \DateTimeImmutable $expiresAt,
        public string $status = 'active',
        public ?string $documentUrl = null,
        public ?string $scope = null,
        public array $metadata = [],
    ) {}

    /**
     * Create ISO 9001 certification.
     */
    public static function iso9001(
        string $certificationId,
        string $certificateNumber,
        string $issuingBody,
        \DateTimeImmutable $issuedAt,
        \DateTimeImmutable $expiresAt,
        ?string $documentUrl = null,
    ): self {
        return new self(
            certificationId: $certificationId,
            code: 'ISO9001',
            name: 'ISO 9001:2015 Quality Management System',
            issuingBody: $issuingBody,
            certificateNumber: $certificateNumber,
            issuedAt: $issuedAt,
            expiresAt: $expiresAt,
            status: 'active',
            documentUrl: $documentUrl,
            scope: 'Quality Management System',
        );
    }

    /**
     * Create ISO 14001 certification.
     */
    public static function iso14001(
        string $certificationId,
        string $certificateNumber,
        string $issuingBody,
        \DateTimeImmutable $issuedAt,
        \DateTimeImmutable $expiresAt,
        ?string $documentUrl = null,
    ): self {
        return new self(
            certificationId: $certificationId,
            code: 'ISO14001',
            name: 'ISO 14001:2015 Environmental Management System',
            issuingBody: $issuingBody,
            certificateNumber: $certificateNumber,
            issuedAt: $issuedAt,
            expiresAt: $expiresAt,
            status: 'active',
            documentUrl: $documentUrl,
            scope: 'Environmental Management System',
        );
    }

    /**
     * Create ISO 27001 certification.
     */
    public static function iso27001(
        string $certificationId,
        string $certificateNumber,
        string $issuingBody,
        \DateTimeImmutable $issuedAt,
        \DateTimeImmutable $expiresAt,
        ?string $documentUrl = null,
    ): self {
        return new self(
            certificationId: $certificationId,
            code: 'ISO27001',
            name: 'ISO 27001:2022 Information Security Management System',
            issuingBody: $issuingBody,
            certificateNumber: $certificateNumber,
            issuedAt: $issuedAt,
            expiresAt: $expiresAt,
            status: 'active',
            documentUrl: $documentUrl,
            scope: 'Information Security Management System',
        );
    }

    public function isValid(): bool
    {
        return $this->status === 'active' && $this->expiresAt > new \DateTimeImmutable();
    }

    public function isExpired(): bool
    {
        return $this->expiresAt <= new \DateTimeImmutable();
    }

    public function isExpiringSoon(int $daysThreshold = 30): bool
    {
        $threshold = new \DateTimeImmutable("+{$daysThreshold} days");

        return $this->isValid() && $this->expiresAt <= $threshold;
    }

    public function getDaysUntilExpiry(): int
    {
        $now = new \DateTimeImmutable();

        if ($this->expiresAt <= $now) {
            return 0;
        }

        return (int) $now->diff($this->expiresAt)->days;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'certification_id' => $this->certificationId,
            'code' => $this->code,
            'name' => $this->name,
            'issuing_body' => $this->issuingBody,
            'certificate_number' => $this->certificateNumber,
            'issued_at' => $this->issuedAt->format('Y-m-d'),
            'expires_at' => $this->expiresAt->format('Y-m-d'),
            'status' => $this->status,
            'document_url' => $this->documentUrl,
            'scope' => $this->scope,
            'is_valid' => $this->isValid(),
            'days_until_expiry' => $this->getDaysUntilExpiry(),
            'metadata' => $this->metadata,
        ];
    }
}

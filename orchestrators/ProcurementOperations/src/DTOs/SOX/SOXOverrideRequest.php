<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\SOX;

use Nexus\ProcurementOperations\Enums\SOXControlPoint;

/**
 * Request to override a failed SOX control.
 */
final readonly class SOXOverrideRequest
{
    /**
     * @param array<string> $supportingDocuments Document IDs supporting the override
     * @param array<string, mixed> $metadata Additional request metadata
     */
    public function __construct(
        public string $tenantId,
        public string $transactionId,
        public string $transactionType,
        public SOXControlPoint $controlPoint,
        public string $requesterId,
        public string $approverId,
        public string $overrideReason,
        public string $businessJustification,
        public array $supportingDocuments = [],
        public ?\DateTimeImmutable $expiresAt = null,
        public array $metadata = [],
    ) {}

    /**
     * Check if this override request has expired.
     */
    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return $this->expiresAt < new \DateTimeImmutable();
    }

    /**
     * Check if the requester is different from approver (required for SOD).
     */
    public function hasProperSegregation(): bool
    {
        return $this->requesterId !== $this->approverId;
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'transaction_id' => $this->transactionId,
            'transaction_type' => $this->transactionType,
            'control_point' => $this->controlPoint->value,
            'control_point_description' => $this->controlPoint->description(),
            'requester_id' => $this->requesterId,
            'approver_id' => $this->approverId,
            'override_reason' => $this->overrideReason,
            'business_justification' => $this->businessJustification,
            'supporting_documents' => $this->supportingDocuments,
            'expires_at' => $this->expiresAt?->format(\DateTimeInterface::ATOM),
            'has_proper_segregation' => $this->hasProperSegregation(),
            'metadata' => $this->metadata,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\SOX;

use Nexus\ProcurementOperations\Enums\SOXControlPoint;

/**
 * Result of a SOX override approval.
 */
final readonly class SOXOverrideResult
{
    /**
     * @param array<string, mixed> $auditTrail Audit trail of the override process
     * @param array<string, mixed> $metadata Additional result metadata
     */
    public function __construct(
        public string $overrideId,
        public string $transactionId,
        public string $transactionType,
        public SOXControlPoint $controlPoint,
        public bool $approved,
        public string $requesterId,
        public string $approverId,
        public string $overrideReason,
        public string $businessJustification,
        public ?string $rejectionReason = null,
        public array $auditTrail = [],
        public \DateTimeImmutable $processedAt = new \DateTimeImmutable(),
        public ?\DateTimeImmutable $expiresAt = null,
        public array $metadata = [],
    ) {}

    /**
     * Create an approved override result.
     */
    public static function approved(
        string $overrideId,
        SOXOverrideRequest $request,
        ?\DateTimeImmutable $expiresAt = null,
    ): self {
        return new self(
            overrideId: $overrideId,
            transactionId: $request->transactionId,
            transactionType: $request->transactionType,
            controlPoint: $request->controlPoint,
            approved: true,
            requesterId: $request->requesterId,
            approverId: $request->approverId,
            overrideReason: $request->overrideReason,
            businessJustification: $request->businessJustification,
            expiresAt: $expiresAt,
            auditTrail: [
                [
                    'action' => 'override_requested',
                    'user_id' => $request->requesterId,
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                ],
                [
                    'action' => 'override_approved',
                    'user_id' => $request->approverId,
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                ],
            ],
        );
    }

    /**
     * Create a rejected override result.
     */
    public static function rejected(
        string $overrideId,
        SOXOverrideRequest $request,
        string $rejectionReason,
    ): self {
        return new self(
            overrideId: $overrideId,
            transactionId: $request->transactionId,
            transactionType: $request->transactionType,
            controlPoint: $request->controlPoint,
            approved: false,
            requesterId: $request->requesterId,
            approverId: $request->approverId,
            overrideReason: $request->overrideReason,
            businessJustification: $request->businessJustification,
            rejectionReason: $rejectionReason,
            auditTrail: [
                [
                    'action' => 'override_requested',
                    'user_id' => $request->requesterId,
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                ],
                [
                    'action' => 'override_rejected',
                    'user_id' => $request->approverId,
                    'reason' => $rejectionReason,
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                ],
            ],
        );
    }

    /**
     * Check if the override is still valid (not expired).
     */
    public function isValid(): bool
    {
        if (!$this->approved) {
            return false;
        }

        if ($this->expiresAt === null) {
            return true;
        }

        return $this->expiresAt > new \DateTimeImmutable();
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'override_id' => $this->overrideId,
            'transaction_id' => $this->transactionId,
            'transaction_type' => $this->transactionType,
            'control_point' => $this->controlPoint->value,
            'control_point_description' => $this->controlPoint->description(),
            'approved' => $this->approved,
            'requester_id' => $this->requesterId,
            'approver_id' => $this->approverId,
            'override_reason' => $this->overrideReason,
            'business_justification' => $this->businessJustification,
            'rejection_reason' => $this->rejectionReason,
            'is_valid' => $this->isValid(),
            'audit_trail' => $this->auditTrail,
            'processed_at' => $this->processedAt->format(\DateTimeInterface::ATOM),
            'expires_at' => $this->expiresAt?->format(\DateTimeInterface::ATOM),
            'metadata' => $this->metadata,
        ];
    }
}

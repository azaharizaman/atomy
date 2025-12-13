<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\SOX;

use Nexus\ProcurementOperations\Enums\SOXControlPoint;

/**
 * Event dispatched when a SOX control exemption is requested.
 */
final readonly class SOXExemptionRequestedEvent
{
    /**
     * @param array<string> $affectedControlPoints
     * @param array<string, mixed> $context
     */
    public function __construct(
        public string $tenantId,
        public string $exemptionId,
        public string $requestedByUserId,
        public string $entityType,
        public string $entityId,
        public string $justification,
        public array $affectedControlPoints,
        public int $riskScore,
        public ?string $managerUserId,
        public \DateTimeImmutable $requestedAt,
        public ?\DateTimeImmutable $expiresAt,
        public array $context,
    ) {}

    /**
     * Create for a single control point override.
     *
     * @param array<string, mixed> $context
     */
    public static function forControlPoint(
        string $tenantId,
        string $exemptionId,
        SOXControlPoint $controlPoint,
        string $requestedByUserId,
        string $entityType,
        string $entityId,
        string $justification,
        ?string $managerUserId = null,
        ?int $hoursToExpire = null,
        array $context = [],
    ): self {
        $expiresAt = $hoursToExpire
            ? (new \DateTimeImmutable())->modify("+{$hoursToExpire} hours")
            : null;

        return new self(
            tenantId: $tenantId,
            exemptionId: $exemptionId,
            requestedByUserId: $requestedByUserId,
            entityType: $entityType,
            entityId: $entityId,
            justification: $justification,
            affectedControlPoints: [$controlPoint->value],
            riskScore: $controlPoint->getRiskLevel(),
            managerUserId: $managerUserId,
            requestedAt: new \DateTimeImmutable(),
            expiresAt: $expiresAt,
            context: $context,
        );
    }

    /**
     * Create for multiple control points.
     *
     * @param array<SOXControlPoint> $controlPoints
     * @param array<string, mixed> $context
     */
    public static function forMultipleControlPoints(
        string $tenantId,
        string $exemptionId,
        array $controlPoints,
        string $requestedByUserId,
        string $entityType,
        string $entityId,
        string $justification,
        ?string $managerUserId = null,
        ?int $hoursToExpire = null,
        array $context = [],
    ): self {
        $expiresAt = $hoursToExpire
            ? (new \DateTimeImmutable())->modify("+{$hoursToExpire} hours")
            : null;

        $affectedPoints = array_map(
            fn(SOXControlPoint $cp) => $cp->value,
            $controlPoints,
        );

        $maxRisk = max(array_map(
            fn(SOXControlPoint $cp) => $cp->getRiskLevel(),
            $controlPoints,
        ));

        return new self(
            tenantId: $tenantId,
            exemptionId: $exemptionId,
            requestedByUserId: $requestedByUserId,
            entityType: $entityType,
            entityId: $entityId,
            justification: $justification,
            affectedControlPoints: $affectedPoints,
            riskScore: $maxRisk,
            managerUserId: $managerUserId,
            requestedAt: new \DateTimeImmutable(),
            expiresAt: $expiresAt,
            context: $context,
        );
    }

    /**
     * Get event name for dispatch.
     */
    public static function eventName(): string
    {
        return 'procurement.sox.exemption_requested';
    }

    /**
     * Check if this is a high-risk exemption request.
     */
    public function isHighRisk(): bool
    {
        return $this->riskScore >= 4;
    }

    /**
     * Check if manager approval is required.
     */
    public function requiresManagerApproval(): bool
    {
        return $this->riskScore >= 3;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'exemption_id' => $this->exemptionId,
            'requested_by_user_id' => $this->requestedByUserId,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'justification' => $this->justification,
            'affected_control_points' => $this->affectedControlPoints,
            'risk_score' => $this->riskScore,
            'manager_user_id' => $this->managerUserId,
            'requested_at' => $this->requestedAt->format(\DateTimeInterface::RFC3339),
            'expires_at' => $this->expiresAt?->format(\DateTimeInterface::RFC3339),
            'context' => $this->context,
        ];
    }
}

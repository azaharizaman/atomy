<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Entities;

use DateTimeInterface;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use JsonSerializable;

/**
 * Depreciation Method Entity
 *
 * Represents a configured depreciation method with its parameters.
 * Used for method configuration and validation.
 *
 * @package Nexus\FixedAssetDepreciation\Entities
 */
final class DepreciationMethod implements JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly DepreciationMethodType $methodType,
        public readonly string $name,
        public readonly string $description,
        public readonly array $parameters,
        public readonly int $tierLevel,
        public readonly bool $isActive = true,
        public readonly bool $isDefault = false,
        public readonly ?DateTimeInterface $createdAt = null,
        public readonly ?DateTimeInterface $updatedAt = null,
    ) {}

    public function getMethodType(): DepreciationMethodType
    {
        return $this->methodType;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParameter(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    public function isAccelerated(): bool
    {
        return $this->methodType->isAccelerated();
    }

    public function requiresUnitTracking(): bool
    {
        return $this->methodType === DepreciationMethodType::UNITS_OF_PRODUCTION;
    }

    public function getDecliningFactor(): float
    {
        return match ($this->methodType) {
            DepreciationMethodType::DOUBLE_DECLINING => $this->getParameter('factor', 2.0),
            DepreciationMethodType::DECLINING_150 => $this->getParameter('factor', 1.5),
            default => 1.0,
        };
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'method_type' => $this->methodType->value,
            'name' => $this->name,
            'description' => $this->description,
            'parameters' => $this->parameters,
            'tier_level' => $this->tierLevel,
            'is_active' => $this->isActive,
            'is_default' => $this->isDefault,
            'is_accelerated' => $this->isAccelerated(),
            'requires_unit_tracking' => $this->requiresUnitTracking(),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

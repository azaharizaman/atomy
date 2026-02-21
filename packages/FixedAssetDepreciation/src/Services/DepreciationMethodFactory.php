<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Services;

use Nexus\FixedAssetDepreciation\Contracts\DepreciationMethodInterface;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\Exceptions\InvalidDepreciationMethodException;
use Nexus\FixedAssetDepreciation\Methods\UnitsOfProductionDepreciationMethod;
use Nexus\FixedAssetDepreciation\Services\Methods\StraightLineDepreciationMethod;
use Nexus\FixedAssetDepreciation\Services\Methods\DoubleDecliningDepreciationMethod;
use Nexus\FixedAssetDepreciation\Services\Methods\Declining150DepreciationMethod;
use Nexus\FixedAssetDepreciation\Services\Methods\SumOfYearsDepreciationMethod;

/**
 * Depreciation Method Factory
 *
 * Creates and configures depreciation method instances based on method type.
 * Supports tier-based validation and method configuration.
 *
 * @package Nexus\FixedAssetDepreciation\Services
 */
final readonly class DepreciationMethodFactory
{
    private const TIER_HIERARCHY = [
        'basic' => 1,
        'advanced' => 2,
        'enterprise' => 3,
    ];

    public function __construct(
        private string $currentTier = 'basic',
        private array $methodConfigs = [],
    ) {}

    public function create(DepreciationMethodType $methodType): DepreciationMethodInterface
    {
        $this->validateTier($methodType);

        return match ($methodType) {
            DepreciationMethodType::STRAIGHT_LINE => $this->createStraightLine(),
            DepreciationMethodType::STRAIGHT_LINE_DAILY => $this->createStraightLineDaily(),
            DepreciationMethodType::DOUBLE_DECLINING => $this->createDoubleDeclining(),
            DepreciationMethodType::DECLINING_150 => $this->createDeclining150(),
            DepreciationMethodType::SUM_OF_YEARS => $this->createSumOfYears(),
            DepreciationMethodType::UNITS_OF_PRODUCTION => $this->createUnitsOfProduction(),
            DepreciationMethodType::ANNUITY => throw InvalidDepreciationMethodException::notSupported($methodType->value),
            DepreciationMethodType::MACRS => throw InvalidDepreciationMethodException::notSupported($methodType->value),
            DepreciationMethodType::BONUS => throw InvalidDepreciationMethodException::notSupported($methodType->value),
        };
    }

    public function isMethodAvailable(DepreciationMethodType $methodType): bool
    {
        $methodTier = $methodType->getTierLevel();
        $currentTierLevel = self::TIER_HIERARCHY[$this->currentTier] ?? 1;

        return $currentTierLevel >= $methodTier;
    }

    public function getAvailableMethods(): array
    {
        $currentTierLevel = self::TIER_HIERARCHY[$this->currentTier] ?? 1;

        return array_filter(
            DepreciationMethodType::cases(),
            fn(DepreciationMethodType $type) => $type->getTierLevel() <= $currentTierLevel
        );
    }

    public function getMethodTier(DepreciationMethodType $methodType): int
    {
        return $methodType->getTierLevel();
    }

    public function getCurrentTier(): string
    {
        return $this->currentTier;
    }

    public function getCurrentTierLevel(): int
    {
        return self::TIER_HIERARCHY[$this->currentTier] ?? 1;
    }

    private function validateTier(DepreciationMethodType $methodType): void
    {
        if (!$this->isMethodAvailable($methodType)) {
            throw InvalidDepreciationMethodException::tierNotAvailable(
                $methodType->value,
                $methodType->getTierLevel(),
                $this->getCurrentTierLevel()
            );
        }
    }

    private function createStraightLine(): StraightLineDepreciationMethod
    {
        $config = $this->methodConfigs['straight_line'] ?? [];
        return new StraightLineDepreciationMethod(
            prorateDaily: $config['prorate_daily'] ?? false
        );
    }

    private function createStraightLineDaily(): StraightLineDepreciationMethod
    {
        return new StraightLineDepreciationMethod(prorateDaily: true);
    }

    private function createDoubleDeclining(): DoubleDecliningDepreciationMethod
    {
        $config = $this->methodConfigs['double_declining'] ?? [];
        return new DoubleDecliningDepreciationMethod(
            decliningFactor: $config['factor'] ?? 2.0,
            switchToStraightLine: $config['switch_to_sl'] ?? true
        );
    }

    private function createDeclining150(): Declining150DepreciationMethod
    {
        $config = $this->methodConfigs['declining_150'] ?? [];
        return new Declining150DepreciationMethod(
            switchToStraightLine: $config['switch_to_sl'] ?? true
        );
    }

    private function createSumOfYears(): SumOfYearsDepreciationMethod
    {
        return new SumOfYearsDepreciationMethod();
    }

    private function createUnitsOfProduction(): UnitsOfProductionDepreciationMethod
    {
        return new UnitsOfProductionDepreciationMethod();
    }
}

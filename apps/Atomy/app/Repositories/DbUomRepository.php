<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Dimension;
use App\Models\Unit;
use App\Models\UnitConversion;
use App\Models\UnitSystem;
use Nexus\Uom\Contracts\ConversionRuleInterface;
use Nexus\Uom\Contracts\DimensionInterface;
use Nexus\Uom\Contracts\UnitInterface;
use Nexus\Uom\Contracts\UnitSystemInterface;
use Nexus\Uom\Contracts\UomRepositoryInterface;
use Nexus\Uom\Exceptions\DuplicateDimensionCodeException;
use Nexus\Uom\Exceptions\DuplicateUnitCodeException;
use Nexus\Uom\Exceptions\InvalidConversionRatioException;

/**
 * Eloquent-based repository implementation for UoM package.
 *
 * Implements all persistence operations using Laravel's Eloquent ORM.
 *
 * Requirements: ARC-UOM-0031, FR-UOM-A02
 */
class DbUomRepository implements UomRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function findUnitByCode(string $code): ?UnitInterface
    {
        return Unit::where('code', $code)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function findDimensionByCode(string $code): ?DimensionInterface
    {
        return Dimension::where('code', $code)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getUnitsByDimension(string $dimensionCode): array
    {
        return Unit::where('dimension_code', $dimensionCode)->get()->all();
    }

    /**
     * {@inheritDoc}
     */
    public function getUnitsBySystem(string $systemCode): array
    {
        return Unit::where('system_code', $systemCode)->get()->all();
    }

    /**
     * {@inheritDoc}
     */
    public function findConversion(string $fromUnitCode, string $toUnitCode): ?ConversionRuleInterface
    {
        return UnitConversion::where('from_unit_code', $fromUnitCode)
            ->where('to_unit_code', $toUnitCode)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getConversionsFrom(string $fromUnitCode): array
    {
        return UnitConversion::where('from_unit_code', $fromUnitCode)->get()->all();
    }

    /**
     * {@inheritDoc}
     */
    public function getConversionsByDimension(string $dimensionCode): array
    {
        return UnitConversion::whereHas('fromUnit', function ($query) use ($dimensionCode) {
            $query->where('dimension_code', $dimensionCode);
        })->get()->all();
    }

    /**
     * {@inheritDoc}
     */
    public function saveUnit(UnitInterface $unit): UnitInterface
    {
        // Check for duplicate code
        if (Unit::where('code', $unit->getCode())->exists()) {
            throw DuplicateUnitCodeException::forCode($unit->getCode());
        }

        $model = new Unit();
        $model->code = $unit->getCode();
        $model->name = $unit->getName();
        $model->symbol = $unit->getSymbol();
        $model->dimension_code = $unit->getDimension();
        $model->system_code = $unit->getSystem();
        $model->is_base_unit = $unit->isBaseUnit();
        $model->is_system_unit = $unit->isSystemUnit();
        $model->save();

        return $model;
    }

    /**
     * {@inheritDoc}
     */
    public function saveDimension(DimensionInterface $dimension): DimensionInterface
    {
        // Check for duplicate code
        if (Dimension::where('code', $dimension->getCode())->exists()) {
            throw DuplicateDimensionCodeException::forCode($dimension->getCode());
        }

        $model = new Dimension();
        $model->code = $dimension->getCode();
        $model->name = $dimension->getName();
        $model->base_unit_code = $dimension->getBaseUnit();
        $model->allows_offset = $dimension->allowsOffset();
        $model->description = $dimension->getDescription();
        $model->is_system_defined = true;
        $model->save();

        return $model;
    }

    /**
     * {@inheritDoc}
     */
    public function saveConversion(ConversionRuleInterface $rule): ConversionRuleInterface
    {
        // Validate ratio
        if ($rule->getRatio() <= 0) {
            throw InvalidConversionRatioException::forRatio($rule->getRatio());
        }

        $model = new UnitConversion();
        $model->from_unit_code = $rule->getFromUnit();
        $model->to_unit_code = $rule->getToUnit();
        $model->ratio = $rule->getRatio();
        $model->offset = $rule->getOffset();
        $model->is_bidirectional = $rule->isBidirectional();
        $model->version = 1;
        $model->save();

        return $model;
    }

    /**
     * {@inheritDoc}
     */
    public function ensureUniqueCode(string $code): bool
    {
        return !Unit::where('code', $code)->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getAllDimensions(): array
    {
        return Dimension::all()->all();
    }

    /**
     * {@inheritDoc}
     */
    public function getAllUnitSystems(): array
    {
        return UnitSystem::all()->all();
    }
}

<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Exceptions;

/**
 * Depreciation Calculation Exception
 *
 * Thrown when depreciation calculation fails due to invalid data,
 * insufficient parameters, or mathematical constraints.
 *
 * @package Nexus\FixedAssetDepreciation\Exceptions
 */
class DepreciationCalculationException extends DepreciationException
{
    protected string $errorCode = 'DEPRECIATION_CALCULATION_ERROR';

    public function __construct(
        public readonly string $assetId,
        string $message,
        array $context = [],
        ?\Throwable $previous = null
    ) {
        $fullMessage = sprintf(
            'Depreciation calculation failed for asset "%s": %s',
            $assetId,
            $message
        );
        
        if (!empty($context)) {
            $fullMessage .= ' Context: ' . json_encode($context, JSON_UNESCAPED_SLASHES);
        }
        
        parent::__construct($fullMessage, 0, $previous);
    }

    public static function invalidCost(string $assetId, float $cost): self
    {
        return new self(
            $assetId,
            'Invalid asset cost',
            ['cost' => $cost, 'reason' => 'Cost must be positive']
        );
    }

    public static function invalidUsefulLife(string $assetId, int $months): self
    {
        return new self(
            $assetId,
            'Invalid useful life',
            ['useful_life_months' => $months, 'reason' => 'Useful life must be positive']
        );
    }

    public static function salvageExceedsCost(string $assetId, float $salvage, float $cost): self
    {
        return new self(
            $assetId,
            'Salvage value cannot exceed cost',
            ['salvage_value' => $salvage, 'cost' => $cost]
        );
    }

    public static function alreadyFullyDepreciated(string $assetId, float $netBookValue): self
    {
        return new self(
            $assetId,
            'Asset is already fully depreciated',
            ['net_book_value' => $netBookValue]
        );
    }

    public static function methodNotSupported(string $assetId, string $method): self
    {
        return new self(
            $assetId,
            'Depreciation method not supported',
            ['method' => $method]
        );
    }

    public static function invalidPeriod(string $assetId, string $periodId): self
    {
        return new self(
            $assetId,
            sprintf('Invalid period: %s', $periodId)
        );
    }

    public static function missingRequiredData(string $assetId, string $field): self
    {
        return new self(
            $assetId,
            sprintf('Missing required data: %s', $field)
        );
    }
}

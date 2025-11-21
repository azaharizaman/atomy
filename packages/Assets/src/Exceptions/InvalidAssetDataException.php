<?php

declare(strict_types=1);

namespace Nexus\Assets\Exceptions;

/**
 * Invalid Asset Data Exception
 *
 * Thrown when asset data validation fails.
 */
class InvalidAssetDataException extends AssetException
{
    public static function missingRequired(string $field): self
    {
        return new self("Required field missing: {$field}");
    }

    public static function invalidValue(string $field, mixed $value, string $reason): self
    {
        $valueStr = is_scalar($value) ? (string)$value : gettype($value);
        return new self("Invalid value for {$field}: {$valueStr}. Reason: {$reason}");
    }

    public static function invalidUsefulLife(float $years): self
    {
        return new self("Useful life must be greater than 0, got: {$years}");
    }

    public static function invalidSalvageValue(float $salvage, float $cost): self
    {
        return new self("Salvage value ({$salvage}) cannot exceed acquisition cost ({$cost})");
    }

    public static function invalidAcquisitionDate(string $reason): self
    {
        return new self("Invalid acquisition date: {$reason}");
    }
}

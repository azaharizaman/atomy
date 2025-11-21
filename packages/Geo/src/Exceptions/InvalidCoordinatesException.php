<?php

declare(strict_types=1);

namespace Nexus\Geo\Exceptions;

/**
 * Exception thrown when coordinates are invalid
 */
class InvalidCoordinatesException extends GeoException
{
    public static function invalidLatitude(float $latitude): self
    {
        return new self("Invalid latitude: {$latitude}. Must be between -90 and 90 degrees.");
    }

    public static function invalidLongitude(float $longitude): self
    {
        return new self("Invalid longitude: {$longitude}. Must be between -180 and 180 degrees.");
    }

    public static function invalidFormat(string $message): self
    {
        return new self("Invalid coordinates format: {$message}");
    }
}

<?php

declare(strict_types=1);

namespace Nexus\Geo\Exceptions;

/**
 * Exception thrown when polygon complexity exceeds limits
 */
class PolygonComplexityException extends GeoException
{
    public static function tooManyVertices(int $count, int $maxAllowed): self
    {
        return new self(
            "Polygon has {$count} vertices, exceeding maximum of {$maxAllowed}. " .
            "Please simplify the polygon or increase the tolerance."
        );
    }

    public static function invalidPolygon(string $reason): self
    {
        return new self("Invalid polygon: {$reason}");
    }

    public static function simplificationFailed(string $reason): self
    {
        return new self("Polygon simplification failed: {$reason}");
    }
}

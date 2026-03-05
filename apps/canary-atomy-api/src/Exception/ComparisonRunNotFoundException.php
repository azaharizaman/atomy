<?php

declare(strict_types=1);

namespace App\Exception;

class ComparisonRunNotFoundException extends \DomainException
{
    public static function forId(string $runId): self
    {
        return new self(sprintf('Comparison run "%s" not found.', $runId));
    }
}

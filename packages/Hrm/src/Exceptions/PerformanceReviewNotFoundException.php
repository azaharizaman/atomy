<?php

declare(strict_types=1);

namespace Nexus\Hrm\Exceptions;

class PerformanceReviewNotFoundException extends HrmException
{
    public static function forId(string $id): self
    {
        return new self("Performance review with ID '{$id}' not found.");
    }
}

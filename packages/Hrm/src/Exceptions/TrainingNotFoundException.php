<?php

declare(strict_types=1);

namespace Nexus\Hrm\Exceptions;

class TrainingNotFoundException extends HrmException
{
    public static function forId(string $id): self
    {
        return new self("Training program with ID '{$id}' not found.");
    }
}

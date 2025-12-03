<?php

declare(strict_types=1);

namespace Nexus\Hrm\Exceptions;

class DisciplinaryDuplicateException extends HrmException
{
    public static function forCaseNumber(string $caseNumber): self
    {
        return new self("Disciplinary case number '{$caseNumber}' already exists.");
    }
}

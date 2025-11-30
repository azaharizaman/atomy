<?php

declare(strict_types=1);

namespace Nexus\Hrm\Exceptions;

class DisciplinaryNotFoundException extends HrmException
{
    public static function forId(string $id): self
    {
        return new self("Disciplinary case with ID '{$id}' not found.");
    }
    
    public static function forCaseNumber(string $tenantId, string $caseNumber): self
    {
        return new self("Disciplinary case with number '{$caseNumber}' not found for tenant '{$tenantId}'.");
    }
}

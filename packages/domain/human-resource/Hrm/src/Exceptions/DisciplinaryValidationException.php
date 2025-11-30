<?php

declare(strict_types=1);

namespace Nexus\Hrm\Exceptions;

class DisciplinaryValidationException extends HrmException
{
    public static function invalidStatus(string $status): self
    {
        return new self("Invalid disciplinary status: '{$status}'.");
    }
    
    public static function invalidSeverity(string $severity): self
    {
        return new self("Invalid disciplinary severity: '{$severity}'.");
    }
    
    public static function reportedDateBeforeIncidentDate(): self
    {
        return new self("Reported date cannot be before incident date.");
    }
    
    public static function cannotModifyClosed(): self
    {
        return new self("Cannot modify closed disciplinary case.");
    }
    
    public static function missingRequiredField(string $field): self
    {
        return new self("Required field '{$field}' is missing.");
    }
}

<?php

declare(strict_types=1);

namespace Nexus\Hrm\Exceptions;

class TrainingEnrollmentValidationException extends HrmException
{
    public static function invalidStatus(string $status): self
    {
        return new self("Invalid enrollment status: '{$status}'.");
    }
    
    public static function alreadyEnrolled(string $employeeId, string $trainingId): self
    {
        return new self("Employee '{$employeeId}' is already enrolled in training '{$trainingId}'.");
    }
    
    public static function cannotModifyCompleted(): self
    {
        return new self("Cannot modify completed enrollment.");
    }
    
    public static function missingRequiredField(string $field): self
    {
        return new self("Required field '{$field}' is missing.");
    }
}

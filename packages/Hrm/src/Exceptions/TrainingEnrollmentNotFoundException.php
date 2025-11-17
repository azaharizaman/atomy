<?php

declare(strict_types=1);

namespace Nexus\Hrm\Exceptions;

class TrainingEnrollmentNotFoundException extends HrmException
{
    public static function forId(string $id): self
    {
        return new self("Training enrollment with ID '{$id}' not found.");
    }
    
    public static function forEmployeeAndTraining(string $employeeId, string $trainingId): self
    {
        return new self("Training enrollment not found for employee '{$employeeId}' and training '{$trainingId}'.");
    }
}

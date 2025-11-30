<?php

declare(strict_types=1);

namespace Nexus\Hrm\Exceptions;

class TrainingEnrollmentDuplicateException extends HrmException
{
    public static function forEmployeeAndTraining(string $employeeId, string $trainingId): self
    {
        return new self("Employee '{$employeeId}' is already enrolled in training '{$trainingId}'.");
    }
}

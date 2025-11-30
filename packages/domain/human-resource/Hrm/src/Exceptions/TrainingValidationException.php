<?php

declare(strict_types=1);

namespace Nexus\Hrm\Exceptions;

class TrainingValidationException extends HrmException
{
    public static function invalidStatus(string $status): self
    {
        return new self("Invalid training status: '{$status}'.");
    }
    
    public static function endDateBeforeStartDate(): self
    {
        return new self("Training end date cannot be before start date.");
    }
    
    public static function maxParticipantsReached(string $trainingId): self
    {
        return new self("Maximum participants reached for training '{$trainingId}'.");
    }
    
    public static function missingRequiredField(string $field): self
    {
        return new self("Required field '{$field}' is missing.");
    }
}

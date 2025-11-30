<?php

declare(strict_types=1);

namespace Nexus\Hrm\Exceptions;

class PerformanceReviewValidationException extends HrmException
{
    public static function invalidStatus(string $status): self
    {
        return new self("Invalid review status: '{$status}'.");
    }
    
    public static function invalidReviewType(string $type): self
    {
        return new self("Invalid review type: '{$type}'.");
    }
    
    public static function endDateBeforeStartDate(): self
    {
        return new self("Review period end date cannot be before start date.");
    }
    
    public static function cannotModifyCompleted(): self
    {
        return new self("Cannot modify completed review.");
    }
    
    public static function invalidScore(float $score): self
    {
        return new self("Invalid score: {$score}. Score must be between 0 and 100.");
    }
    
    public static function missingRequiredField(string $field): self
    {
        return new self("Required field '{$field}' is missing.");
    }
}

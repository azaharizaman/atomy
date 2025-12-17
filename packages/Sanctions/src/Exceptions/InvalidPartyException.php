<?php

declare(strict_types=1);

namespace Nexus\Sanctions\Exceptions;

/**
 * Exception thrown when party data is invalid or insufficient for screening.
 * 
 * Screening requires minimum party information:
 * - Name (required): First name + Last name or full legal name
 * - Date of birth (recommended): Improves match accuracy
 * - Nationality (recommended): Jurisdiction-specific screening
 * - Identification documents (optional): Passport, national ID
 * 
 * This exception contains validation errors and required fields guidance.
 * 
 * @package Nexus\Sanctions\Exceptions
 */
final class InvalidPartyException extends SanctionsException
{
    private array $validationErrors;
    private array $requiredFields;

    /**
     * Create exception for missing required fields.
     *
     * @param string $partyId Party ID
     * @param array<string> $missingFields Required fields that are missing
     * @return self
     */
    public static function missingRequiredFields(
        string $partyId,
        array $missingFields
    ): self {
        $exception = new self(
            "Party {$partyId} is missing required fields for screening: " . 
            implode(', ', $missingFields),
            400
        );
        
        $exception->validationErrors = array_map(
            fn($field) => "{$field} is required",
            $missingFields
        );
        $exception->requiredFields = $missingFields;
        
        return $exception;
    }

    /**
     * Create exception for invalid name format.
     *
     * @param string $partyId Party ID
     * @param string $name Invalid name
     * @param string $reason Reason for invalidity
     * @return self
     */
    public static function invalidName(
        string $partyId,
        string $name,
        string $reason
    ): self {
        $exception = new self(
            "Party {$partyId} has invalid name '{$name}': {$reason}",
            400
        );
        
        $exception->validationErrors = ["name: {$reason}"];
        $exception->requiredFields = ['name'];
        
        return $exception;
    }

    /**
     * Create exception for invalid date of birth.
     *
     * @param string $partyId Party ID
     * @param string $dateOfBirth Invalid date of birth
     * @param string $reason Reason for invalidity
     * @return self
     */
    public static function invalidDateOfBirth(
        string $partyId,
        string $dateOfBirth,
        string $reason
    ): self {
        $exception = new self(
            "Party {$partyId} has invalid date of birth '{$dateOfBirth}': {$reason}",
            400
        );
        
        $exception->validationErrors = ["date_of_birth: {$reason}"];
        $exception->requiredFields = [];
        
        return $exception;
    }

    /**
     * Create exception for multiple validation errors.
     *
     * @param string $partyId Party ID
     * @param array<string> $errors Validation error messages
     * @return self
     */
    public static function multipleErrors(
        string $partyId,
        array $errors
    ): self {
        $exception = new self(
            "Party {$partyId} has " . count($errors) . " validation errors: " . 
            implode('; ', $errors),
            400
        );
        
        $exception->validationErrors = $errors;
        $exception->requiredFields = [];
        
        return $exception;
    }

    /**
     * Create exception for empty or null party ID.
     *
     * @return self
     */
    public static function emptyPartyId(): self
    {
        $exception = new self(
            "Party ID cannot be empty or null",
            400
        );
        
        $exception->validationErrors = ['party_id: cannot be empty'];
        $exception->requiredFields = ['party_id'];
        
        return $exception;
    }

    /**
     * Create exception for insufficient data.
     *
     * @param string $partyId Party ID
     * @param string $recommendation Recommendation for required data
     * @return self
     */
    public static function insufficientData(
        string $partyId,
        string $recommendation
    ): self {
        $exception = new self(
            "Party {$partyId} has insufficient data for reliable screening. " . 
            "Recommendation: {$recommendation}",
            400
        );
        
        $exception->validationErrors = ["Insufficient data for screening"];
        $exception->requiredFields = [];
        
        return $exception;
    }

    /**
     * Get validation errors.
     *
     * @return array<string>
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors ?? [];
    }

    /**
     * Get required fields that are missing.
     *
     * @return array<string>
     */
    public function getRequiredFields(): array
    {
        return $this->requiredFields ?? [];
    }

    /**
     * Check if specific field has error.
     *
     * @param string $fieldName Field name to check
     * @return bool
     */
    public function hasFieldError(string $fieldName): bool
    {
        foreach ($this->getValidationErrors() as $error) {
            if (str_starts_with($error, "{$fieldName}:")) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get all validation errors as associative array.
     *
     * @return array<string, string>
     */
    public function getErrorsArray(): array
    {
        $result = [];
        foreach ($this->getValidationErrors() as $error) {
            if (str_contains($error, ':')) {
                [$field, $message] = explode(':', $error, 2);
                $result[trim($field)] = trim($message);
            } else {
                $result['general'] = $error;
            }
        }
        return $result;
    }
}

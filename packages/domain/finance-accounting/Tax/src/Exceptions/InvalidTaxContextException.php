<?php

declare(strict_types=1);

namespace Nexus\Tax\Exceptions;

/**
 * Invalid Tax Context Exception
 * 
 * Thrown when TaxContext validation fails.
 */
final class InvalidTaxContextException extends \InvalidArgumentException
{
    public function __construct(
        private readonly string $fieldName,
        private readonly mixed $fieldValue,
        string $reason,
        ?\Throwable $previous = null
    ) {
        $message = sprintf(
            "Invalid tax context field '%s': %s (value: %s)",
            $fieldName,
            $reason,
            is_string($fieldValue) ? $fieldValue : json_encode($fieldValue)
        );

        parent::__construct($message, 0, $previous);
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function getFieldValue(): mixed
    {
        return $this->fieldValue;
    }

    public function getContext(): array
    {
        return [
            'field_name' => $this->fieldName,
            'field_value' => $this->fieldValue,
        ];
    }
}

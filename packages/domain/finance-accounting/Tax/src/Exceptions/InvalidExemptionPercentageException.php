<?php

declare(strict_types=1);

namespace Nexus\Tax\Exceptions;

/**
 * Invalid Exemption Percentage Exception
 * 
 * Thrown when exemption percentage is outside valid range (0-100).
 */
final class InvalidExemptionPercentageException extends \InvalidArgumentException
{
    public function __construct(
        private readonly string $percentage,
        string $reason = 'Exemption percentage must be between 0.0000 and 100.0000',
        ?\Throwable $previous = null
    ) {
        $message = sprintf(
            "Invalid exemption percentage '%s': %s",
            $percentage,
            $reason
        );

        parent::__construct($message, 0, $previous);
    }

    public function getPercentage(): string
    {
        return $this->percentage;
    }

    public function getContext(): array
    {
        return [
            'percentage' => $this->percentage,
        ];
    }
}

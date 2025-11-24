<?php

declare(strict_types=1);

namespace Nexus\Tax\Exceptions;

/**
 * No Nexus In Jurisdiction Exception
 * 
 * Thrown when business does not have tax nexus in the jurisdiction.
 */
final class NoNexusInJurisdictionException extends \RuntimeException
{
    public function __construct(
        private readonly string $jurisdictionCode,
        private readonly \DateTimeInterface $date,
        ?\Throwable $previous = null
    ) {
        $message = sprintf(
            "No tax nexus in jurisdiction '%s' on %s",
            $jurisdictionCode,
            $date->format('Y-m-d')
        );

        parent::__construct($message, 0, $previous);
    }

    public function getJurisdictionCode(): string
    {
        return $this->jurisdictionCode;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function getContext(): array
    {
        return [
            'jurisdiction_code' => $this->jurisdictionCode,
            'date' => $this->date->format('Y-m-d'),
        ];
    }
}

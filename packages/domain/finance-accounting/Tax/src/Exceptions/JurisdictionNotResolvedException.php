<?php

declare(strict_types=1);

namespace Nexus\Tax\Exceptions;

/**
 * Jurisdiction Not Resolved Exception
 * 
 * Thrown when jurisdiction cannot be determined from address.
 */
final class JurisdictionNotResolvedException extends \RuntimeException
{
    public function __construct(
        private readonly array $address,
        string $reason = 'Unable to resolve tax jurisdiction from address',
        ?\Throwable $previous = null
    ) {
        $message = sprintf(
            "%s: %s",
            $reason,
            json_encode($address)
        );

        parent::__construct($message, 0, $previous);
    }

    public function getAddress(): array
    {
        return $this->address;
    }

    public function getContext(): array
    {
        return [
            'address' => $this->address,
        ];
    }
}

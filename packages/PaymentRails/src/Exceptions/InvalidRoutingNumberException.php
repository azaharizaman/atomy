<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Exceptions;

/**
 * Exception thrown when a routing number is invalid.
 */
final class InvalidRoutingNumberException extends PaymentRailException
{
    /**
     * @param string $routingNumber
     * @param string $reason
     * @param \Throwable|null $previous
     */
    public function __construct(
        private readonly string $routingNumber,
        string $reason = 'Invalid format',
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: "Invalid routing number '{$this->maskRoutingNumber()}': {$reason}",
            railType: 'ACH',
            context: [
                'routing_number_last4' => $this->getLastFour(),
                'reason' => $reason,
            ],
            previous: $previous,
        );
    }

    /**
     * Get the last four digits of the routing number.
     */
    public function getLastFour(): string
    {
        return substr($this->routingNumber, -4);
    }

    /**
     * Mask the routing number for display.
     */
    private function maskRoutingNumber(): string
    {
        if (strlen($this->routingNumber) < 4) {
            return str_repeat('*', strlen($this->routingNumber));
        }

        return str_repeat('*', strlen($this->routingNumber) - 4) . $this->getLastFour();
    }

    /**
     * Create for invalid format.
     */
    public static function invalidFormat(string $routingNumber): self
    {
        return new self($routingNumber, 'Must be exactly 9 digits');
    }

    /**
     * Create for invalid length.
     *
     * Alias for historical call sites.
     */
    public static function invalidLength(string $routingNumber): self
    {
        return self::invalidFormat($routingNumber);
    }

    /**
     * Create for invalid checksum.
     */
    public static function invalidChecksum(string $routingNumber): self
    {
        return new self($routingNumber, 'Failed checksum validation (mod-10)');
    }

    /**
     * Create for invalid check digit.
     *
     * Alias for historical call sites.
     */
    public static function invalidCheckDigit(string $routingNumber): self
    {
        return self::invalidChecksum($routingNumber);
    }

    /**
     * Create for invalid Federal Reserve district.
     */
    public static function invalidFederalReserveDistrict(string $routingNumber): self
    {
        $firstTwo = substr($routingNumber, 0, 2);
        return new self($routingNumber, "Invalid Federal Reserve district code '{$firstTwo}'");
    }

    /**
     * Create for non-existent routing number.
     */
    public static function notFound(string $routingNumber): self
    {
        return new self($routingNumber, 'Routing number not found in ACH participant list');
    }
}

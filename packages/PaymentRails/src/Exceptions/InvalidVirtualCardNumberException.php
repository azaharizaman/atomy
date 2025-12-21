<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Exceptions;

/**
 * Exception thrown when a virtual card number is invalid.
 */
final class InvalidVirtualCardNumberException extends PaymentRailException
{
    /**
     * @param string $cardNumber
     * @param string $reason
     * @param \Throwable|null $previous
     */
    public function __construct(
        private readonly string $cardNumber,
        string $reason = 'Invalid format',
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: "Invalid virtual card number '{$this->maskCardNumber()}': {$reason}",
            railType: 'VIRTUAL_CARD',
            context: [
                'card_last4' => $this->getLastFour(),
                'reason' => $reason,
            ],
            previous: $previous,
        );
    }

    /**
     * Get the last four digits of the card number.
     */
    public function getLastFour(): string
    {
        return substr($this->cardNumber, -4);
    }

    /**
     * Mask the card number for display.
     */
    private function maskCardNumber(): string
    {
        $length = strlen($this->cardNumber);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 4) . $this->getLastFour();
    }

    /**
     * Create for invalid length.
     */
    public static function invalidLength(string $cardNumber): self
    {
        $length = strlen($cardNumber);
        return new self($cardNumber, "Card number must be 13-19 digits, got {$length}");
    }

    /**
     * Create for non-numeric value.
     */
    public static function nonNumeric(string $cardNumber): self
    {
        return new self($cardNumber, 'Card number must contain only digits');
    }

    /**
     * Create for invalid Luhn checksum.
     */
    public static function invalidLuhn(string $cardNumber): self
    {
        return new self($cardNumber, 'Failed Luhn checksum validation');
    }

    /**
     * Create for unknown card network.
     */
    public static function unknownNetwork(string $cardNumber): self
    {
        return new self($cardNumber, 'Card number does not match any known card network pattern');
    }

    /**
     * Create for expired card.
     */
    public static function expired(string $cardNumber): self
    {
        return new self($cardNumber, 'Virtual card has expired');
    }

    /**
     * Create for closed card.
     */
    public static function closed(string $cardNumber): self
    {
        return new self($cardNumber, 'Virtual card has been closed');
    }
}

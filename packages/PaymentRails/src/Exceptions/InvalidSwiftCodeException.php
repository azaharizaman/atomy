<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Exceptions;

/**
 * Exception thrown when a SWIFT/BIC code is invalid.
 */
final class InvalidSwiftCodeException extends PaymentRailException
{
    /**
     * @param string $swiftCode
     * @param string $reason
     * @param \Throwable|null $previous
     */
    public function __construct(
        private readonly string $swiftCode,
        string $reason = 'Invalid format',
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: "Invalid SWIFT/BIC code '{$this->swiftCode}': {$reason}",
            railType: 'WIRE',
            context: [
                'swift_code' => $this->swiftCode,
                'reason' => $reason,
            ],
            previous: $previous,
        );
    }

    /**
     * Get the SWIFT code.
     */
    public function getSwiftCode(): string
    {
        return $this->swiftCode;
    }

    /**
     * Create for invalid length.
     */
    public static function invalidLength(string $swiftCode): self
    {
        $length = strlen($swiftCode);
        return new self($swiftCode, "Must be 8 or 11 characters, got {$length}");
    }

    /**
     * Create for invalid bank code.
     */
    public static function invalidBankCode(string $swiftCode): self
    {
        $bankCode = substr($swiftCode, 0, 4);
        return new self($swiftCode, "Invalid bank code '{$bankCode}' (must be 4 letters)");
    }

    /**
     * Create for invalid country code.
     */
    public static function invalidCountryCode(string $swiftCode): self
    {
        $countryCode = substr($swiftCode, 4, 2);
        return new self($swiftCode, "Invalid ISO 3166-1 country code '{$countryCode}'");
    }

    /**
     * Create for invalid location code.
     */
    public static function invalidLocationCode(string $swiftCode): self
    {
        $locationCode = substr($swiftCode, 6, 2);
        return new self($swiftCode, "Invalid location code '{$locationCode}' (must be alphanumeric)");
    }

    /**
     * Create for invalid branch code.
     */
    public static function invalidBranchCode(string $swiftCode): self
    {
        $branchCode = substr($swiftCode, 8, 3);
        return new self($swiftCode, "Invalid branch code '{$branchCode}' (must be alphanumeric)");
    }

    /**
     * Create for unknown bank.
     */
    public static function unknownBank(string $swiftCode): self
    {
        return new self($swiftCode, 'SWIFT code not found in global bank directory');
    }
}

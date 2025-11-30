<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\ValueObjects;

use InvalidArgumentException;

/**
 * TOTP Secret Value Object
 * 
 * Represents a Time-based One-Time Password secret with validation
 * Immutable by design (readonly properties)
 */
final readonly class TotpSecret
{
    /**
     * @param string $secret Base32-encoded secret (minimum 16 characters)
     * @param string $algorithm Hash algorithm (sha1, sha256, sha512)
     * @param int $period Time period in seconds (typically 30)
     * @param int $digits Number of digits in generated code (6-8)
     * @throws InvalidArgumentException If validation fails
     */
    public function __construct(
        public string $secret,
        public string $algorithm = 'sha1',
        public int $period = 30,
        public int $digits = 6
    ) {
        $this->validateSecret($secret);
        $this->validateAlgorithm($algorithm);
        $this->validatePeriod($period);
        $this->validateDigits($digits);
    }

    /**
     * Validate Base32-encoded secret
     * 
     * @throws InvalidArgumentException
     */
    private function validateSecret(string $secret): void
    {
        if (strlen($secret) < 16) {
            throw new InvalidArgumentException(
                'TOTP secret must be at least 16 characters long for security'
            );
        }

        // Base32 validation: only uppercase A-Z and 2-7 allowed
        if (!preg_match('/^[A-Z2-7]+$/', $secret)) {
            throw new InvalidArgumentException(
                'TOTP secret must be Base32 encoded (A-Z, 2-7 characters only)'
            );
        }
    }

    /**
     * Validate hash algorithm
     * 
     * @throws InvalidArgumentException
     */
    private function validateAlgorithm(string $algorithm): void
    {
        $allowedAlgorithms = ['sha1', 'sha256', 'sha512'];
        
        if (!in_array($algorithm, $allowedAlgorithms, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid algorithm "%s". Allowed: %s',
                    $algorithm,
                    implode(', ', $allowedAlgorithms)
                )
            );
        }
    }

    /**
     * Validate time period
     * 
     * @throws InvalidArgumentException
     */
    private function validatePeriod(int $period): void
    {
        if ($period < 1) {
            throw new InvalidArgumentException(
                'TOTP period must be at least 1 second'
            );
        }

        // Warn against non-standard periods (standard is 30 seconds)
        if ($period !== 30 && $period !== 60) {
            // Still valid, but non-standard
            // Could add logging here if LoggerInterface was injected
        }
    }

    /**
     * Validate number of digits
     * 
     * @throws InvalidArgumentException
     */
    private function validateDigits(int $digits): void
    {
        if ($digits < 6 || $digits > 8) {
            throw new InvalidArgumentException(
                'TOTP digits must be between 6 and 8 (RFC 6238 recommendation)'
            );
        }
    }

    /**
     * Get URI for QR code generation (otpauth:// format)
     */
    public function getUri(string $issuer, string $accountName): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=%s&digits=%d&period=%d',
            rawurlencode($issuer),
            rawurlencode($accountName),
            $this->secret,
            rawurlencode($issuer),
            strtoupper($this->algorithm),
            $this->digits,
            $this->period
        );
    }

    /**
     * Check if this secret uses the default configuration
     */
    public function isDefault(): bool
    {
        return $this->algorithm === 'sha1'
            && $this->period === 30
            && $this->digits === 6;
    }

    /**
     * Get configuration as array for storage
     * 
     * @return array{secret: string, algorithm: string, period: int, digits: int}
     */
    public function toArray(): array
    {
        return [
            'secret' => $this->secret,
            'algorithm' => $this->algorithm,
            'period' => $this->period,
            'digits' => $this->digits,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Nexus\Domain\Identity\ValueObjects\TotpSecret;
use OTPHP\TOTP;

/**
 * TOTP (Time-based One-Time Password) engine implementing RFC 6238.
 *
 * Provides secret generation, QR code creation, and verification
 * for authenticator app-based MFA.
 */
final readonly class TotpManager
{
    /**
     * Generate a new TOTP secret.
     *
     * @param string $algorithm Hash algorithm (sha1, sha256, sha512)
     * @param int $period Time step in seconds
     * @param int $digits Number of digits in the code
     * @return TotpSecret The generated secret
     */
    public function generateSecret(
        string $algorithm = 'sha1',
        int $period = 30,
        int $digits = 6
    ): TotpSecret {
        $totp = TOTP::generate();
        
        return new TotpSecret(
            secret: $totp->getSecret(),
            algorithm: $algorithm,
            period: $period,
            digits: $digits
        );
    }

    /**
     * Generate QR code image for TOTP enrollment.
     *
     * @param TotpSecret $totpSecret The TOTP secret
     * @param string $issuer The application/service name
     * @param string $accountName User identifier (email or username)
     * @param int $size QR code size in pixels
     * @return string Base64-encoded PNG image
     */
    public function generateQrCode(
        TotpSecret $totpSecret,
        string $issuer,
        string $accountName,
        int $size = 300
    ): string {
        $uri = $totpSecret->getUri($issuer, $accountName);
        
        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($uri)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size($size)
            ->margin(10)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build();
        
        return base64_encode($result->getString());
    }

    /**
     * Generate QR code data URI for direct embedding in HTML.
     *
     * @param TotpSecret $totpSecret The TOTP secret
     * @param string $issuer The application/service name
     * @param string $accountName User identifier
     * @param int $size QR code size in pixels
     * @return string Data URI (data:image/png;base64,...)
     */
    public function generateQrCodeDataUri(
        TotpSecret $totpSecret,
        string $issuer,
        string $accountName,
        int $size = 300
    ): string {
        $base64 = $this->generateQrCode($totpSecret, $issuer, $accountName, $size);
        return "data:image/png;base64,{$base64}";
    }

    /**
     * Verify a TOTP code against a secret.
     *
     * Uses timing-attack-resistant comparison and supports a time window
     * to account for clock drift.
     *
     * @param TotpSecret $totpSecret The TOTP secret
     * @param string $userCode The 6-digit code provided by user
     * @param int $window Number of time steps to check before/after (default: 1)
     * @param int|null $timestamp Unix timestamp for verification (null = now)
     * @return bool True if code is valid
     */
    public function verify(
        TotpSecret $totpSecret,
        string $userCode,
        int $window = 1,
        ?int $timestamp = null
    ): bool {
        $totp = TOTP::createFromSecret($totpSecret->getSecret());
        $totp->setDigits($totpSecret->getDigits());
        $totp->setPeriod($totpSecret->getPeriod());
        $totp->setDigest($totpSecret->getAlgorithm());
        
        // OTPHP library already uses timing-safe comparison internally
        return $totp->verify($userCode, $timestamp, $window);
    }

    /**
     * Generate the current TOTP code for a secret.
     *
     * Useful for testing and debugging. Should not be exposed to end users.
     *
     * @param TotpSecret $totpSecret The TOTP secret
     * @param int|null $timestamp Unix timestamp (null = now)
     * @return string The current TOTP code
     */
    public function getCurrentCode(TotpSecret $totpSecret, ?int $timestamp = null): string
    {
        $totp = TOTP::createFromSecret($totpSecret->getSecret());
        $totp->setDigits($totpSecret->getDigits());
        $totp->setPeriod($totpSecret->getPeriod());
        $totp->setDigest($totpSecret->getAlgorithm());
        
        return $totp->at($timestamp ?? time());
    }

    /**
     * Get remaining seconds until next code.
     *
     * Useful for showing countdown timer in UI.
     *
     * @param TotpSecret $totpSecret The TOTP secret
     * @param int|null $timestamp Unix timestamp (null = now)
     * @return int Seconds remaining
     */
    public function getRemainingSeconds(TotpSecret $totpSecret, ?int $timestamp = null): int
    {
        $timestamp = $timestamp ?? time();
        $period = $totpSecret->getPeriod();
        
        return $period - ($timestamp % $period);
    }

    /**
     * Get provisioning URI for manual entry.
     *
     * @param TotpSecret $totpSecret The TOTP secret
     * @param string $issuer The application/service name
     * @param string $accountName User identifier
     * @return string The otpauth:// URI
     */
    public function getProvisioningUri(
        TotpSecret $totpSecret,
        string $issuer,
        string $accountName
    ): string {
        return $totpSecret->getUri($issuer, $accountName);
    }
}

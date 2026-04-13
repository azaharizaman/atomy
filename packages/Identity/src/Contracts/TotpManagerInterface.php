<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

use Nexus\Identity\ValueObjects\TotpSecret;

interface TotpManagerInterface
{
    public function generateSecret(string $algorithm = 'sha1', int $period = 30, int $digits = 6): TotpSecret;

    public function generateQrCode(TotpSecret $totpSecret, string $issuer, string $accountName, int $size = 300): string;

    public function generateQrCodeDataUri(TotpSecret $totpSecret, string $issuer, string $accountName, int $size = 300): string;

    /**
     * Verify a TOTP code against a secret.
     *
     * @param TotpSecret $totpSecret The TOTP secret
     * @param string $userCode The 6-digit code provided by user
     * @param int $window Number of time steps to check before/after (default: 1)
     * @param int|null $timestamp Unix timestamp for verification (null = now)
     * @return bool True if code is valid
     */
    public function verifyCode(TotpSecret $totpSecret, string $userCode, int $window = 1, ?int $timestamp = null): bool;

    public function getCurrentCode(TotpSecret $totpSecret, ?int $timestamp = null): string;

    public function getRemainingSeconds(TotpSecret $totpSecret, ?int $timestamp = null): int;

    public function getProvisioningUri(TotpSecret $totpSecret, string $issuer, string $accountName): string;
}

<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Models\BackupCode;
use App\Models\MfaEnrollment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Nexus\Identity\Contracts\MfaVerificationServiceInterface;
use Nexus\Identity\Exceptions\MfaVerificationException;
use Nexus\Identity\Services\TotpManager;
use Nexus\Identity\ValueObjects\TotpSecret;
use Nexus\Identity\ValueObjects\UserVerificationRequirement;
use Nexus\Identity\ValueObjects\WebAuthnAuthenticationOptions;

final readonly class AtomyMfaVerificationService implements MfaVerificationServiceInterface
{
    private const RATE_LIMIT_WINDOW_SECONDS = 900;
    private const RATE_LIMIT_MAX_ATTEMPTS = 5;
    private const DEFAULT_BACKUP_CODE_THRESHOLD = 2;

    public function __construct(
        private TotpManager $totpManager,
    ) {
    }

    public function verifyTotp(string $userId, string $code): bool
    {
        $totpEnrollment = MfaEnrollment::query()
            ->where('user_id', $userId)
            ->where('method', 'totp')
            ->where('is_active', true)
            ->latest('created_at')
            ->first();

        if ($totpEnrollment === null) {
            throw MfaVerificationException::methodNotEnrolled($userId, 'totp');
        }

        $secret = $this->resolveTotpSecret((string) $totpEnrollment->secret);
        if ($secret === null) {
            throw MfaVerificationException::verificationFailed('Stored TOTP secret is invalid');
        }

        if ($this->isRateLimited($userId, 'totp')) {
            throw MfaVerificationException::rateLimited(
                $userId,
                'totp',
                $this->getRateLimitRetryAfter($userId, 'totp')
            );
        }

        $valid = $this->totpManager->verify($secret, $this->normalizeCode($code));
        $this->recordVerificationAttempt($userId, 'totp', $valid);

        if (! $valid) {
            throw MfaVerificationException::invalidTotpCode($userId);
        }

        return true;
    }

    public function generateWebAuthnAuthenticationOptions(
        ?string $userId = null,
        bool $requireUserVerification = false
    ): WebAuthnAuthenticationOptions {
        return new WebAuthnAuthenticationOptions(
            challenge: base64_encode(random_bytes(32)),
            timeout: 60000,
            rpId: (string) config('app.url', 'localhost'),
            allowCredentials: [],
            userVerification: $requireUserVerification
                ? UserVerificationRequirement::REQUIRED
                : UserVerificationRequirement::PREFERRED,
        );
    }

    public function verifyWebAuthn(
        string $assertionResponseJson,
        string $expectedChallenge,
        string $expectedOrigin,
        ?string $userId = null
    ): array {
        throw new MfaVerificationException('WebAuthn is not enabled for Atomy-Q');
    }

    public function verifyBackupCode(string $userId, string $code): bool
    {
        if ($this->isRateLimited($userId, 'backup_code')) {
            throw MfaVerificationException::rateLimited(
                $userId,
                'backup_code',
                $this->getRateLimitRetryAfter($userId, 'backup_code')
            );
        }

        $normalizedCode = $this->normalizeCode($code);

        $backupCode = BackupCode::query()
            ->where('user_id', $userId)
            ->whereNull('used_at')
            ->orderBy('created_at')
            ->get()
            ->first(static function (BackupCode $row) use ($normalizedCode): bool {
                return Hash::check($normalizedCode, (string) $row->code_hash);
            });

        if ($backupCode === null) {
            $this->recordVerificationAttempt($userId, 'backup_code', false);
            throw MfaVerificationException::invalidBackupCode($userId);
        }

        $backupCode->used_at = now();
        $backupCode->save();

        $this->recordVerificationAttempt($userId, 'backup_code', true);

        return true;
    }

    public function verifyWithFallback(string $userId, array $credentials): array
    {
        $lastException = null;

        foreach (['totp', 'backup_code'] as $method) {
            $code = $credentials[$method] ?? null;
            if (! is_string($code) || trim($code) === '') {
                continue;
            }

            try {
                $verified = $method === 'totp'
                    ? $this->verifyTotp($userId, $code)
                    : $this->verifyBackupCode($userId, $code);
            } catch (MfaVerificationException $e) {
                $lastException = $e;
                continue;
            }

            if ($verified) {
                return [
                    'method' => $method,
                    'verified' => true,
                ];
            }
        }

        if ($lastException instanceof MfaVerificationException) {
            throw $lastException;
        }

        throw MfaVerificationException::allMethodsFailed($userId);
    }

    public function isRateLimited(string $userId, string $method): bool
    {
        return (int) Cache::get($this->rateLimitKey($userId, $method), 0) >= self::RATE_LIMIT_MAX_ATTEMPTS;
    }

    public function getRemainingBackupCodesCount(string $userId): int
    {
        return BackupCode::query()
            ->where('user_id', $userId)
            ->whereNull('used_at')
            ->count();
    }

    public function shouldRegenerateBackupCodes(string $userId): bool
    {
        return $this->getRemainingBackupCodesCount($userId) <= self::DEFAULT_BACKUP_CODE_THRESHOLD;
    }

    public function recordVerificationAttempt(
        string $userId,
        string $method,
        bool $success,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        $key = $this->rateLimitKey($userId, $method);

        if ($success) {
            Cache::forget($key);
            Cache::forget($key . ':retry_after');

            return;
        }

        $attempts = (int) Cache::get($key, 0);
        Cache::put($key, $attempts + 1, self::RATE_LIMIT_WINDOW_SECONDS);
        Cache::put($key . ':retry_after', self::RATE_LIMIT_WINDOW_SECONDS, self::RATE_LIMIT_WINDOW_SECONDS);
    }

    public function clearRateLimit(string $userId, string $method): bool
    {
        Cache::forget($this->rateLimitKey($userId, $method));
        Cache::forget($this->rateLimitKey($userId, $method) . ':retry_after');

        return true;
    }

    private function resolveTotpSecret(string $storedSecret): ?TotpSecret
    {
        $candidates = [$storedSecret];

        try {
            $decrypted = Crypt::decryptString($storedSecret);
            if (is_string($decrypted) && trim($decrypted) !== '') {
                $candidates[] = $decrypted;
            }
        } catch (\Throwable) {
            // Not encrypted with the app key; fall back to raw storage.
        }

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if ($candidate === '') {
                continue;
            }

            $decoded = json_decode($candidate, true);
            if (is_array($decoded) && isset($decoded['secret'])) {
                try {
                    return new TotpSecret(
                        secret: strtoupper((string) $decoded['secret']),
                        algorithm: isset($decoded['algorithm']) ? (string) $decoded['algorithm'] : 'sha1',
                        period: isset($decoded['period']) ? (int) $decoded['period'] : 30,
                        digits: isset($decoded['digits']) ? (int) $decoded['digits'] : 6,
                    );
                } catch (\Throwable) {
                    continue;
                }
            }

            $normalized = strtoupper($candidate);
            if (preg_match('/^[A-Z2-7]+$/', $normalized) === 1 && strlen($normalized) >= 16) {
                try {
                    return new TotpSecret($normalized);
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        return null;
    }

    private function normalizeCode(string $code): string
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($code))) ?? '';
    }

    private function rateLimitKey(string $userId, string $method): string
    {
        return sprintf('mfa:rate_limit:%s:%s', $userId, $method);
    }

    private function getRateLimitRetryAfter(string $userId, string $method): int
    {
        $key = $this->rateLimitKey($userId, $method);
        $ttl = (int) Cache::get($key . ':retry_after', self::RATE_LIMIT_WINDOW_SECONDS);

        return max(0, $ttl);
    }
}

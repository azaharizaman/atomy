<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Models\BackupCode;
use App\Models\MfaEnrollment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Nexus\Identity\Contracts\MfaVerificationServiceInterface;
use Nexus\Identity\Contracts\TotpManagerInterface;
use Nexus\Identity\Exceptions\WebAuthnVerificationException;
use Nexus\Identity\Exceptions\MfaVerificationException;
use Nexus\Identity\ValueObjects\TotpSecret;
use Nexus\Identity\ValueObjects\UserVerificationRequirement;
use Nexus\Identity\ValueObjects\WebAuthnAuthenticationOptions;
use Nexus\Tenant\Contracts\TenantContextInterface;

final readonly class AtomyMfaVerificationService implements MfaVerificationServiceInterface
{
    private const RATE_LIMIT_WINDOW_SECONDS = 900;
    private const RATE_LIMIT_MAX_ATTEMPTS = 5;
    private const DEFAULT_BACKUP_CODE_THRESHOLD = 2;

    public function __construct(
        private TotpManagerInterface $totpManager,
        private TenantContextInterface $tenantContext,
    ) {
    }

    public function verifyTotp(string $userId, string $code): bool
    {
        $requestMetadata = $this->resolveRequestMetadata();
        $tenantId = $this->resolveTenantId();

        if ($this->isRateLimited($userId, 'totp')) {
            throw MfaVerificationException::rateLimited(
                $userId,
                'totp',
                $this->getRateLimitRetryAfter($userId, 'totp')
            );
        }

        $totpEnrollment = MfaEnrollment::query()
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
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

        $valid = $this->totpManager->verifyCode($secret, $this->normalizeCode($code));
        $this->recordVerificationAttempt(
            $userId,
            'totp',
            $valid,
            $requestMetadata['ip'],
            $requestMetadata['user_agent']
        );

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
        throw WebAuthnVerificationException::webAuthnNotEnabled();
    }

    public function verifyBackupCode(string $userId, string $code): bool
    {
        $requestMetadata = $this->resolveRequestMetadata();
        $tenantId = $this->resolveTenantId();

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
            ->where('tenant_id', $tenantId)
            ->whereNull('used_at')
            ->orderBy('created_at')
            ->get()
            ->first(static function (BackupCode $row) use ($normalizedCode): bool {
                return Hash::check($normalizedCode, (string) $row->code_hash);
            });

        if ($backupCode === null) {
            $this->recordVerificationAttempt(
                $userId,
                'backup_code',
                false,
                $requestMetadata['ip'],
                $requestMetadata['user_agent']
            );
            throw MfaVerificationException::invalidBackupCode($userId);
        }

        $backupCode->used_at = now();
        $backupCode->save();

        $this->recordVerificationAttempt(
            $userId,
            'backup_code',
            true,
            $requestMetadata['ip'],
            $requestMetadata['user_agent']
        );

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
                if ($method === 'totp') {
                    $this->verifyTotp($userId, $code);
                } else {
                    $this->verifyBackupCode($userId, $code);
                }
            } catch (MfaVerificationException $e) {
                $lastException = $e;
                continue;
            }

            return [
                'method' => $method,
                'verified' => true,
            ];
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
        $tenantId = $this->resolveTenantId();

        return BackupCode::query()
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
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
        $normalizedIp = is_string($ipAddress) && trim($ipAddress) !== '' ? trim($ipAddress) : null;
        $normalizedUserAgent = is_string($userAgent) && trim($userAgent) !== '' ? trim($userAgent) : null;

        if ($success) {
            Cache::forget($key);
            Cache::forget($key . ':retry_after');
            Log::info('MFA verification attempt succeeded', [
                'user_id' => $userId,
                'method' => $method,
                'ip_address' => $normalizedIp,
                'user_agent' => $normalizedUserAgent,
            ]);

            return;
        }

        $attempts = (int) Cache::get($key, 0);
        Cache::put($key, $attempts + 1, self::RATE_LIMIT_WINDOW_SECONDS);
        $retryAfterEpoch = time() + self::RATE_LIMIT_WINDOW_SECONDS;
        Cache::put($key . ':retry_after', $retryAfterEpoch, self::RATE_LIMIT_WINDOW_SECONDS);
        Log::warning('MFA verification attempt failed', [
            'user_id' => $userId,
            'method' => $method,
            'ip_address' => $normalizedIp,
            'user_agent' => $normalizedUserAgent,
            'attempts' => $attempts + 1,
            'retry_after_epoch' => $retryAfterEpoch,
        ]);
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
        $retryAfterEpoch = (int) Cache::get($key . ':retry_after', 0);
        if ($retryAfterEpoch <= 0) {
            return 0;
        }

        return max(0, $retryAfterEpoch - time());
    }

    private function resolveTenantId(): string
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        if (! is_string($tenantId) || trim($tenantId) === '') {
            throw MfaVerificationException::verificationFailed('Tenant context is required for MFA verification');
        }

        return trim($tenantId);
    }

    /**
     * @return array{ip: ?string, user_agent: ?string}
     */
    private function resolveRequestMetadata(): array
    {
        try {
            $request = request();
            if (! method_exists($request, 'ip') || ! method_exists($request, 'userAgent')) {
                return ['ip' => null, 'user_agent' => null];
            }

            $ip = $request->ip();
            $userAgent = $request->userAgent();

            return [
                'ip' => is_string($ip) && trim($ip) !== '' ? trim($ip) : null,
                'user_agent' => is_string($userAgent) && trim($userAgent) !== '' ? trim($userAgent) : null,
            ];
        } catch (\Throwable) {
            return ['ip' => null, 'user_agent' => null];
        }
    }
}

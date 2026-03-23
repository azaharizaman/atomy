<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

use ArrayObject;
use DateTimeImmutable;
use Nexus\Common\Contracts\ClockInterface;
use Nexus\Crypto\Contracts\AsymmetricSignerInterface;
use Nexus\Crypto\Enums\AsymmetricAlgorithm;
use Nexus\IdentityOperations\Contracts\VerificationTokenServiceInterface;

final readonly class VerificationTokenService implements VerificationTokenServiceInterface
{
    private const string PURPOSE = 'email_verification';
    private const int TTL_SECONDS = 86400;

    public function __construct(
        private AsymmetricSignerInterface $signer,
        private string $secretKey,
        private ClockInterface $clock,
        private ArrayObject $tokenStore
    ) {}

    public function generate(string $userId, string $tenantId): string
    {
        $now = $this->clock->now();
        $issuedAt = $now->getTimestamp();
        $expiresAt = $now->modify('+24 hours')->getTimestamp();

        $payload = [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'purpose' => self::PURPOSE,
            'issued_at' => $issuedAt,
            'expires_at' => $expiresAt,
        ];

        $encoded = base64_encode(json_encode($payload));
        $signature = $this->signer->hmac($encoded, $this->secretKey, AsymmetricAlgorithm::HMACSHA256);

        $token = $encoded . '.' . $signature;

        $key = $this->getStoreKey($userId, $tenantId);
        $this->tokenStore[$key] = $token;

        return $token;
    }

    public function validate(string $token): ?array
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$encoded, $signature] = $parts;

        if (!$this->signer->verifyHmac($encoded, $signature, $this->secretKey, AsymmetricAlgorithm::HMACSHA256)) {
            return null;
        }

        $decoded = json_decode(base64_decode($encoded), true);
        if (!is_array($decoded)) {
            return null;
        }

        if (($decoded['purpose'] ?? '') !== self::PURPOSE) {
            return null;
        }

        $now = $this->clock->now()->getTimestamp();
        if (($decoded['expires_at'] ?? 0) < $now) {
            return null;
        }

        return [
            'user_id' => $decoded['user_id'],
            'tenant_id' => $decoded['tenant_id'],
            'purpose' => $decoded['purpose'],
            'issued_at' => $decoded['issued_at'],
            'expires_at' => $decoded['expires_at'],
        ];
    }

    public function invalidateForUser(string $userId): void
    {
        foreach ($this->tokenStore as $key => $token) {
            if (str_starts_with($key, 'verification:' . $userId . ':')) {
                unset($this->tokenStore[$key]);
            }
        }
    }

    private function getStoreKey(string $userId, string $tenantId): string
    {
        return 'verification:' . $userId . ':' . $tenantId;
    }
}

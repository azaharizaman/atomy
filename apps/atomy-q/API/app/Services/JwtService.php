<?php

declare(strict_types=1);

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final readonly class JwtService
{
    private string $secret;
    private int $ttl;
    private int $refreshTtl;
    private string $algo;
    private string $issuer;

    public function __construct()
    {
        $this->secret = (string) config('jwt.secret');
        $this->ttl = (int) config('jwt.ttl');
        $this->refreshTtl = (int) config('jwt.refresh_ttl');
        $this->algo = (string) config('jwt.algo');
        $this->issuer = (string) config('jwt.issuer');
    }

    /**
     * @param array<string, mixed> $claims
     */
    public function issueAccessToken(string $userId, string $tenantId, array $claims = []): string
    {
        $now = time();
        $payload = array_merge($claims, [
            'iss' => $this->issuer,
            'sub' => $userId,
            'tenant_id' => $tenantId,
            'iat' => $now,
            'exp' => $now + ($this->ttl * 60),
            'type' => 'access',
        ]);

        return JWT::encode($payload, $this->secret, $this->algo);
    }

    public function issueRefreshToken(string $userId, string $tenantId): string
    {
        $now = time();
        $payload = [
            'iss' => $this->issuer,
            'sub' => $userId,
            'tenant_id' => $tenantId,
            'iat' => $now,
            'exp' => $now + ($this->refreshTtl * 60),
            'type' => 'refresh',
        ];

        return JWT::encode($payload, $this->secret, $this->algo);
    }

    /**
     * @return object{sub: string, tenant_id: string, type: string, exp: int, iat: int, iss: string}
     */
    public function decode(string $token): object
    {
        return JWT::decode($token, new Key($this->secret, $this->algo));
    }

    public function getTtlMinutes(): int
    {
        return $this->ttl;
    }
}

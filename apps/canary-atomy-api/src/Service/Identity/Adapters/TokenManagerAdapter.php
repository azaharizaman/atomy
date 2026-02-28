<?php

declare(strict_types=1);

namespace App\Service\Identity\Adapters;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Nexus\IdentityOperations\DTOs\RefreshTokenPayload;
use Nexus\IdentityOperations\Services\TokenManagerInterface;
use Nexus\IdentityOperations\Services\SessionValidatorInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Uid\Ulid;

final readonly class TokenManagerAdapter implements TokenManagerInterface, SessionValidatorInterface
{
    private Configuration $config;

    public function __construct(
        private CacheItemPoolInterface $cache,
        string $jwtSecret = 'a-very-secret-key-that-should-be-in-env'
    ) {
        $this->config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($jwtSecret)
        );
    }

    public function generateAccessToken(string $userId, string $tenantId): string
    {
        $now = new \DateTimeImmutable();

        return $this->config->builder()
            ->issuedBy('https://api.atomy.example.com')
            ->permittedFor('https://atomy.example.com')
            ->identifiedBy((string) Ulid::generate())
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($now->modify('+1 hour'))
            ->withClaim('uid', $userId)
            ->withClaim('tid', $tenantId)
            ->getToken($this->config->signer(), $this->config->signingKey())
            ->toString();
    }

    public function generateRefreshToken(string $userId, string $tenantId): string
    {
        $token = (string) Ulid::generate();
        
        $item = $this->cache->getItem('refresh_token_' . $token);
        $item->set([
            'userId' => $userId,
            'tenantId' => $tenantId,
            'expiresAt' => (new \DateTimeImmutable())->modify('+30 days')->getTimestamp(),
        ]);
        $this->cache->save($item);

        return $token;
    }

    public function validateRefreshToken(string $refreshToken, string $tenantId): RefreshTokenPayload
    {
        $data = $this->cache->getItem('refresh_token_' . $refreshToken)->get();

        if (!$data || $data['tenantId'] !== $tenantId || $data['expiresAt'] < time()) {
            throw new \RuntimeException('Invalid refresh token');
        }

        return new RefreshTokenPayload(
            userId: $data['userId'],
            tenantId: $data['tenantId']
        );
    }

    public function createSession(string $userId, string $accessToken, string $tenantId): string
    {
        $sessionId = (string) Ulid::generate();
        
        $item = $this->cache->getItem('session_' . $sessionId);
        $item->set([
            'userId' => $userId,
            'tenantId' => $tenantId,
            'active' => true,
        ]);
        $this->cache->save($item);

        return $sessionId;
    }

    public function isValid(string $sessionId): bool
    {
        return $this->cache->hasItem('session_' . $sessionId);
    }

    public function invalidateUserSessions(string $userId, string $tenantId): void
    {
        // In a real app, we'd need a way to find all sessions for a user.
        // For now, we'll just ignore it or implement a simple lookup if needed.
    }

    public function invalidateSession(string $sessionId, string $tenantId): void
    {
        $this->cache->deleteItem('session_' . $sessionId);
    }
}

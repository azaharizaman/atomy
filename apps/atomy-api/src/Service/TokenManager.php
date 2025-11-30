<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ApiToken as ApiTokenEntity;
use App\Repository\ApiTokenRepository;
use App\Repository\UserRepository;
use Nexus\Domain\Identity\Contracts\TokenManagerInterface;
use Nexus\Domain\Identity\Contracts\UserInterface;
use Nexus\Domain\Identity\Exceptions\InvalidTokenException;
use Nexus\Domain\Identity\ValueObjects\ApiToken;
use Symfony\Component\Uid\Ulid;

final readonly class TokenManager implements TokenManagerInterface
{
    public function __construct(
        private ApiTokenRepository $tokenRepository,
        private UserRepository $userRepository,
    ) {}

    public function generateToken(
        string $userId,
        string $name,
        array $scopes = [],
        ?\DateTimeInterface $expiresAt = null
    ): ApiToken {
        // Generate secure random token
        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);

        $tokenEntity = new ApiTokenEntity(
            id: (string) new Ulid(),
            userId: $userId,
            name: $name,
            tokenHash: $tokenHash,
            scopes: $scopes,
            expiresAt: $expiresAt instanceof \DateTimeInterface 
                ? \DateTimeImmutable::createFromInterface($expiresAt) 
                : null,
        );

        $em = $this->tokenRepository->getEntityManager();
        $em->persist($tokenEntity);
        $em->flush();

        return new ApiToken(
            id: $tokenEntity->getId(),
            token: $plainToken,
            userId: $userId,
            name: $name,
            scopes: $scopes,
            expiresAt: $expiresAt,
        );
    }

    public function validateToken(string $token): UserInterface
    {
        $tokenHash = hash('sha256', $token);
        $tokenEntity = $this->tokenRepository->findByHash($tokenHash);

        if ($tokenEntity === null) {
            throw new InvalidTokenException('Invalid API token');
        }

        if ($tokenEntity->isExpired()) {
            throw new InvalidTokenException('API token has expired');
        }

        if ($tokenEntity->isRevoked()) {
            throw new InvalidTokenException('API token has been revoked');
        }

        // Update last used timestamp
        $tokenEntity->markUsed();
        $this->tokenRepository->getEntityManager()->flush();

        return $this->userRepository->findById($tokenEntity->getUserId());
    }

    public function isValid(string $token): bool
    {
        try {
            $this->validateToken($token);
            return true;
        } catch (InvalidTokenException) {
            return false;
        }
    }

    public function revokeToken(string $tokenId): void
    {
        $tokenEntity = $this->tokenRepository->find($tokenId);
        if ($tokenEntity !== null) {
            $tokenEntity->revoke();
            $this->tokenRepository->getEntityManager()->flush();
        }
    }

    public function revokeAllTokens(string $userId): void
    {
        $tokens = $this->tokenRepository->findByUserId($userId);
        foreach ($tokens as $token) {
            $token->revoke();
        }
        $this->tokenRepository->getEntityManager()->flush();
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getUserTokens(string $userId): array
    {
        $tokens = $this->tokenRepository->findByUserId($userId);
        return array_map(
            fn(ApiTokenEntity $t) => [
                'id' => $t->getId(),
                'name' => $t->getName(),
                'scopes' => $t->getScopes(),
                'expires_at' => $t->getExpiresAt()?->format('c'),
                'last_used_at' => $t->getLastUsedAt()?->format('c'),
                'created_at' => $t->getCreatedAt()->format('c'),
                'is_expired' => $t->isExpired(),
                'is_revoked' => $t->isRevoked(),
            ],
            $tokens
        );
    }

    /**
     * @return string[]
     */
    public function getTokenScopes(string $token): array
    {
        $tokenHash = hash('sha256', $token);
        $tokenEntity = $this->tokenRepository->findByHash($tokenHash);

        if ($tokenEntity === null || $tokenEntity->isExpired() || $tokenEntity->isRevoked()) {
            return [];
        }

        return $tokenEntity->getScopes();
    }

    public function cleanupExpiredTokens(): int
    {
        return $this->tokenRepository->deleteExpired();
    }
}

<?php

declare(strict_types=1);

namespace Nexus\SSO\Services;

use DateTimeImmutable;
use Nexus\SSO\Contracts\CallbackStateValidatorInterface;
use Nexus\SSO\Contracts\StateStorageInterface;
use Nexus\SSO\Exceptions\InvalidCallbackStateException;
use Nexus\SSO\ValueObjects\CallbackState;

/**
 * Callback state validator service
 * 
 * Prevents CSRF attacks during SSO callback by validating state tokens
 */
final readonly class CallbackStateValidator implements CallbackStateValidatorInterface
{
    public function __construct(
        private StateStorageInterface $storage,
        private int $ttlSeconds = 600, // 10 minutes default
    ) {
    }

    public function generateState(array $metadata = []): CallbackState
    {
        // Generate cryptographically secure random token
        $token = bin2hex(random_bytes(32)); // 64 character hex string (256 bits)

        $now = new DateTimeImmutable();
        $expiresAt = $now->modify("+{$this->ttlSeconds} seconds");

        // Store in temporary storage (Redis, cache, etc.)
        $this->storage->store($token, $metadata, $this->ttlSeconds);

        return new CallbackState(
            token: $token,
            metadata: $metadata,
            createdAt: $now,
            expiresAt: $expiresAt
        );
    }

    public function validateState(string $token): CallbackState
    {
        // Retrieve metadata from storage
        $metadata = $this->storage->retrieve($token);

        if ($metadata === null) {
            throw new InvalidCallbackStateException('Invalid or expired state token');
        }

        // Reconstruct CallbackState
        // Note: We don't have exact createdAt/expiresAt from storage, 
        // but we know it's valid if storage returned it
        $now = new DateTimeImmutable();
        
        return new CallbackState(
            token: $token,
            metadata: $metadata,
            createdAt: $now->modify("-{$this->ttlSeconds} seconds"), // Approximate
            expiresAt: $now // Will be deleted after use anyway
        );
    }

    public function invalidateState(string $token): void
    {
        $this->storage->delete($token);
    }
}

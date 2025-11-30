<?php

declare(strict_types=1);

namespace Nexus\SSO\Contracts;

use Nexus\SSO\ValueObjects\CallbackState;

/**
 * Callback state validator
 * 
 * Prevents CSRF attacks during SSO callback
 */
interface CallbackStateValidatorInterface
{
    /**
     * Generate random state token
     * 
     * @param array<string, mixed> $metadata Additional metadata to store with state
     * @return CallbackState State token with metadata
     */
    public function generateState(array $metadata = []): CallbackState;

    /**
     * Validate state token from callback
     * 
     * @param string $token State token from callback
     * @return CallbackState Validated state with metadata
     * @throws \Nexus\SSO\Exceptions\InvalidCallbackStateException
     */
    public function validateState(string $token): CallbackState;

    /**
     * Invalidate state token (one-time use)
     */
    public function invalidateState(string $token): void;
}

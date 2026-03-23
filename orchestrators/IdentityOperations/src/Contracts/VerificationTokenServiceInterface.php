<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Contracts;

use Nexus\Crypto\Contracts\AsymmetricSignerInterface;

interface VerificationTokenServiceInterface
{
    public function generate(string $userId, string $tenantId): string;

    /**
     * @return array{
     *     user_id: string,
     *     tenant_id: string,
     *     purpose: string,
     *     issued_at: int,
     *     expires_at: int
     * }|null
     */
    public function validate(string $token): ?array;

    public function invalidateForUser(string $userId, string $tenantId): void;
}

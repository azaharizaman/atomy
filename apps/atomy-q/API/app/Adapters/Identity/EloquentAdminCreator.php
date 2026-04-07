<?php

declare(strict_types=1);

namespace App\Adapters\Identity;

use Nexus\Identity\Contracts\PasswordHasherInterface;
use Nexus\Identity\Contracts\UserPersistInterface;
use Nexus\TenantOperations\Contracts\AdminCreatorAdapterInterface;

/**
 * Thin Laravel adapter for creating the first admin user during company onboarding.
 */
final readonly class EloquentAdminCreator implements AdminCreatorAdapterInterface
{
    public function __construct(
        private UserPersistInterface $userPersist,
        private PasswordHasherInterface $passwordHasher,
    ) {
    }

    public function create(
        string $tenantId,
        string $email,
        string $password,
        string $firstName,
        string $lastName,
        bool $isAdmin = true,
        ?string $locale = null,
        ?string $timezone = null,
        ?array $metadata = null
    ): string {
        $user = $this->userPersist->create([
            'tenant_id' => $tenantId,
            'email' => strtolower(trim($email)),
            'password_hash' => $this->passwordHasher->hash($password),
            'first_name' => trim($firstName),
            'last_name' => trim($lastName),
            'role' => $isAdmin ? 'admin' : 'user',
            'status' => 'active',
            'timezone' => $this->normalizeNullableString($timezone) ?? 'UTC',
            'locale' => $this->normalizeNullableString($locale) ?? 'en',
            'metadata' => $metadata ?? [],
            'email_verified_at' => now(),
        ]);

        return $user->getId();
    }

    private function normalizeNullableString(?string $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}

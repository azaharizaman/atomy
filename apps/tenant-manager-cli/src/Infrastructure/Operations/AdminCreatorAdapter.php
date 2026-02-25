<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations;

use Nexus\TenantOperations\Contracts\AdminCreatorAdapterInterface;

final readonly class AdminCreatorAdapter implements AdminCreatorAdapterInterface
{
    public function create(string $tenantId, string $email, string $password, bool $isAdmin = false): string
    {
        // For canary app, we just simulate creating an admin user in Identity package.
        // In real L3, this would use Nexus\Identity\Contracts\UserPersistInterface.
        return 'USER-' . bin2hex(random_bytes(8));
    }
}

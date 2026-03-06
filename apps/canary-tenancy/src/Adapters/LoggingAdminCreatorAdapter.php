<?php

declare(strict_types=1);

namespace App\Adapters;

use Nexus\TenantOperations\Contracts\AdminCreatorAdapterInterface;
use Psr\Log\LoggerInterface;

final readonly class LoggingAdminCreatorAdapter implements AdminCreatorAdapterInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function create(string $tenantId, string $email, string $password, bool $isAdmin = false): string
    {
        $adminId = (string) \Symfony\Component\Uid\Uuid::v4();

        $this->logger->info('Admin user created in canary adapter', [
            'adminId' => $adminId,
            'tenantId' => $tenantId,
            'email' => $email,
            'isAdmin' => $isAdmin,
        ]);

        return $adminId;
    }
}

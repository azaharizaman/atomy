<?php

declare(strict_types=1);

namespace App\Service\Identity\Adapters;

use Nexus\IdentityOperations\Services\AuditLoggerInterface;
use Psr\Log\LoggerInterface;

final readonly class UserAuditLoggerAdapter implements AuditLoggerInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function log(string $event, string $userId, array $context = []): void
    {
        $this->logger->info(sprintf('User audit event: %s for user %s', $event, $userId), $context);
    }
}

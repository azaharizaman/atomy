<?php

declare(strict_types=1);

namespace Nexus\Laravel\ProjectManagementOperations\Tests\Support\InMemory;

use Nexus\Laravel\ProjectManagementOperations\Contracts\ProjectMessagingSenderInterface;

/**
 * No-op in-memory ProjectMessagingSenderInterface for integration tests.
 */
final class InMemoryProjectMessagingSender implements ProjectMessagingSenderInterface
{
    public function send(
        string $tenantId,
        string $recipientId,
        string $template,
        array $data
    ): void {
        // No-op for tests
    }
}

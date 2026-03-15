<?php

declare(strict_types=1);

namespace Nexus\Laravel\ProjectManagementOperations\Tests\Support\InMemory;

use Nexus\Laravel\ProjectManagementOperations\Contracts\ProjectMessagingSenderInterface;

/**
 * In-memory ProjectMessagingSenderInterface for integration tests; records sent messages for assertion.
 */
final class InMemoryProjectMessagingSender implements ProjectMessagingSenderInterface
{
    /** @var list<array{tenantId: string, recipientId: string, template: string, data: array}> */
    private array $sentMessages = [];

    public function send(
        string $tenantId,
        string $recipientId,
        string $template,
        array $data
    ): void {
        $this->sentMessages[] = [
            'tenantId' => $tenantId,
            'recipientId' => $recipientId,
            'template' => $template,
            'data' => $data,
        ];
    }

    /**
     * @return list<array{tenantId: string, recipientId: string, template: string, data: array}>
     */
    public function getSentMessages(): array
    {
        return $this->sentMessages;
    }

    public function resetSentMessages(): void
    {
        $this->sentMessages = [];
    }
}

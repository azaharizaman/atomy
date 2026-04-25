<?php

declare(strict_types=1);

namespace App\Services\Ai\Contracts;

use Nexus\IntelligenceOperations\DTOs\AiStatusSnapshot;

interface AiOperationalAlertPublisherInterface
{
    /**
     * @return list<array<string, mixed>>
     */
    public function publishSnapshot(AiStatusSnapshot $snapshot): array;
}

<?php

declare(strict_types=1);

namespace App\Adapters\Ai\Contracts;

use Nexus\IntelligenceOperations\DTOs\AiCapabilityStatus;
use Nexus\IntelligenceOperations\DTOs\AiStatusSnapshot;

interface AiRuntimeStatusInterface
{
    public function snapshot(): AiStatusSnapshot;

    public function capabilityStatus(string $featureKey): ?AiCapabilityStatus;

    public function providerName(): string;
}

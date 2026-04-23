<?php

declare(strict_types=1);

namespace App\Adapters\Ai\Contracts;

use Nexus\MachineLearning\ValueObjects\AiEndpointConfig;

interface AiEndpointRegistryInterface
{
    public function mode(): string;

    public function providerName(): string;

    /**
     * @return list<string>
     */
    public function endpointGroups(): array;

    public function endpointConfig(string $endpointGroup): ?AiEndpointConfig;
}

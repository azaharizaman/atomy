<?php

declare(strict_types=1);

namespace App\Adapters\Ai\Contracts;

use App\Adapters\Ai\DTOs\GovernanceNarrativeRequest;

interface ProviderGovernanceClientInterface
{
    /**
     * @return array<string, mixed>
     */
    public function narrate(GovernanceNarrativeRequest $request): array;
}

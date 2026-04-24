<?php

declare(strict_types=1);

namespace App\Adapters\Ai;

use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use App\Adapters\Ai\Contracts\ProviderGovernanceClientInterface;
use App\Adapters\Ai\Contracts\ProviderAiTransportInterface;
use App\Adapters\Ai\DTOs\GovernanceNarrativeRequest;

final readonly class ProviderGovernanceClient implements ProviderGovernanceClientInterface
{
    public function __construct(
        private ProviderAiTransportInterface $transport,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function narrate(GovernanceNarrativeRequest $request): array
    {
        return $this->transport->invoke(AiStatusSchema::ENDPOINT_GROUP_GOVERNANCE, [
            ...$request->toPayload(),
            'action' => 'narrative',
        ]);
    }
}

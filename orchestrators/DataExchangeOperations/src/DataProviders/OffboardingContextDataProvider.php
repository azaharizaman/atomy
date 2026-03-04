<?php

declare(strict_types=1);

namespace Nexus\DataExchangeOperations\DataProviders;

use Nexus\DataExchangeOperations\DTOs\DataOffboardingRequest;

final readonly class OffboardingContextDataProvider
{
    /**
     * @return array<string, mixed>
     */
    public function build(DataOffboardingRequest $request): array
    {
        return [
            'query' => $request->query,
            'format' => strtolower($request->format),
            'destination' => $request->destination,
            'recipient_count' => count($request->recipients),
        ];
    }
}

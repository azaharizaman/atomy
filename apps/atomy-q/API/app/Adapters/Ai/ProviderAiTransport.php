<?php

declare(strict_types=1);

namespace App\Adapters\Ai;

use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

final readonly class ProviderAiTransport
{
    public function __construct(
        private ConfiguredAiEndpointRegistry $endpointRegistry,
        private HttpFactory $http,
    ) {}

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function invoke(string $endpointGroup, array $payload): array
    {
        $endpointConfig = $this->endpointRegistry->endpointConfig($endpointGroup);
        if ($endpointConfig === null || $endpointConfig->enabled === false) {
            throw new RuntimeException(sprintf('AI endpoint [%s] is unavailable.', $endpointGroup));
        }

        $request = $this->http
            ->acceptJson()
            ->asJson()
            ->timeout($endpointConfig->timeoutSeconds);

        $authToken = $endpointConfig->metadata['auth_token'] ?? null;
        if (is_string($authToken) && trim($authToken) !== '') {
            $request = $request->withToken(trim($authToken));
        }

        $response = $request->post($endpointConfig->endpointUri, $payload);
        if (!$response->successful()) {
            throw new RuntimeException(sprintf('AI endpoint [%s] returned an unsuccessful response.', $endpointGroup));
        }

        $data = $response->json();
        if (!is_array($data)) {
            throw new RuntimeException(sprintf('AI endpoint [%s] returned an invalid JSON payload.', $endpointGroup));
        }

        /** @var array<string, mixed> $data */
        return $data;
    }
}

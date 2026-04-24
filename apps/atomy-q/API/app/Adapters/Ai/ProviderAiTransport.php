<?php

declare(strict_types=1);

namespace App\Adapters\Ai;

use App\Adapters\Ai\Contracts\AiEndpointRegistryInterface;
use App\Adapters\Ai\Contracts\ProviderAiTransportInterface;
use App\Adapters\Ai\Exceptions\AiTransportFailedException;
use App\Adapters\Ai\Exceptions\AiTransportInvalidResponseException;
use App\Adapters\Ai\Exceptions\AiTransportUnavailableException;
use Illuminate\Http\Client\Factory as HttpFactory;

final readonly class ProviderAiTransport implements ProviderAiTransportInterface
{
    public function __construct(
        private AiEndpointRegistryInterface $endpointRegistry,
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
            throw new AiTransportUnavailableException(sprintf('AI endpoint [%s] is unavailable.', $endpointGroup));
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
            throw new AiTransportFailedException(sprintf('AI endpoint [%s] returned an unsuccessful response.', $endpointGroup));
        }

        $data = $response->json();
        if (!is_array($data) || array_is_list($data)) {
            throw new AiTransportInvalidResponseException(sprintf('AI endpoint [%s] returned an invalid JSON payload.', $endpointGroup));
        }

        /** @var array<string, mixed> $data */
        return $data;
    }
}

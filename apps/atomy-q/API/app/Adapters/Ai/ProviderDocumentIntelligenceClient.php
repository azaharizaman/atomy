<?php

declare(strict_types=1);

namespace App\Adapters\Ai;

use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;

use App\Adapters\Ai\Contracts\DocumentExtractionMapperInterface;
use App\Adapters\Ai\Contracts\DocumentPayloadFactoryInterface;
use App\Adapters\Ai\Contracts\ProviderAiTransportInterface;
use App\Adapters\Ai\Contracts\ProviderDocumentIntelligenceClientInterface;
use App\Adapters\Ai\DTOs\DocumentExtractionRequest;

final readonly class ProviderDocumentIntelligenceClient implements ProviderDocumentIntelligenceClientInterface
{
    public function __construct(
        private ProviderAiTransportInterface $transport,
        private DocumentPayloadFactoryInterface $payloadFactory,
        private DocumentExtractionMapperInterface $mapper,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function extract(array $payload): array
    {
        return $this->transport->invoke(AiStatusSchema::ENDPOINT_GROUP_DOCUMENT, $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function extractDocument(DocumentExtractionRequest $request): array
    {
        return $this->mapper->map($this->transport->invoke(
            AiStatusSchema::ENDPOINT_GROUP_DOCUMENT,
            $this->payloadFactory->build($request),
        ));
    }
}

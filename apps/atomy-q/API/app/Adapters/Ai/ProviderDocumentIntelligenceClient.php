<?php

declare(strict_types=1);

namespace App\Adapters\Ai;

use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;

use App\Adapters\Ai\Contracts\DocumentExtractionMapperInterface;
use App\Adapters\Ai\Contracts\DocumentPayloadFactoryInterface;
use App\Adapters\Ai\Contracts\ProviderAiTransportInterface;
use App\Adapters\Ai\Contracts\ProviderDocumentIntelligenceClientInterface;
use App\Adapters\Ai\DTOs\DocumentExtractionRequest;

/**
 * Invokes document-intelligence providers for quote extraction.
 *
 * Raw extraction supports direct associative payload callers, while typed
 * extraction builds provider-specific document payloads and maps responses back
 * into the quote-ingestion shape expected by Atomy-Q.
 */
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

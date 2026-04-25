<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters\Ai;

use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use App\Adapters\Ai\Contracts\ProviderAiTransportInterface;
use App\Adapters\Ai\DTOs\DocumentExtractionRequest;
use App\Adapters\Ai\ProviderDocumentIntelligenceClient;
use App\Adapters\Ai\Support\OpenRouterDocumentExtractionMapper;
use App\Adapters\Ai\Support\OpenRouterDocumentPayloadFactory;
use PHPUnit\Framework\TestCase;

final class ProviderDocumentIntelligenceClientTest extends TestCase
{
    public function testExtractDocumentBuildsPayloadAndMapsResponse(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'quote-pdf-');
        self::assertIsString($file);
        file_put_contents($file, '%PDF-1.7 sample');

        try {
            $transport = new class implements ProviderAiTransportInterface {
                /** @var array<string, mixed> */
                public array $payload = [];

                public function invoke(string $endpointGroup, array $payload): array
                {
                    TestCase::assertSame(AiStatusSchema::ENDPOINT_GROUP_DOCUMENT, $endpointGroup);
                    $this->payload = $payload;

                    return [
                        'choices' => [[
                            'message' => [
                                'content' => '{"line_items":[{"description":"Pump","quantity":2,"unit_price":10}]}',
                            ],
                        ]],
                    ];
                }
            };

            $client = new ProviderDocumentIntelligenceClient(
                transport: $transport,
                payloadFactory: new OpenRouterDocumentPayloadFactory('model-a', 'file-parser', 'mistral-ocr'),
                mapper: new OpenRouterDocumentExtractionMapper(),
            );

            $result = $client->extractDocument(new DocumentExtractionRequest(
                tenantId: 'tenant-1',
                rfqId: 'rfq-1',
                quoteSubmissionId: 'quote-1',
                filename: 'quote.pdf',
                mimeType: 'application/pdf',
                absolutePath: $file,
            ));

            self::assertSame('model-a', $transport->payload['model']);
            self::assertSame('Pump', $result['lines'][0]['description']);
        } finally {
            @unlink($file);
        }
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters\Ai;

use App\Adapters\Ai\Contracts\AiEndpointRegistryInterface;
use App\Adapters\Ai\Contracts\ProviderAiTransportInterface;
use App\Adapters\Ai\DTOs\InsightSummaryRequest;
use App\Adapters\Ai\Exceptions\AiTransportInvalidResponseException;
use App\Adapters\Ai\ProviderInsightClient;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Nexus\MachineLearning\Enums\AiEndpointGroup;
use Nexus\MachineLearning\ValueObjects\AiEndpointConfig;
use Tests\TestCase;

final class ProviderInsightClientTest extends TestCase
{
    public function testOpenRouterInsightRequestsUseChatCompletionsContract(): void
    {
        $transport = new InsightTransportSpy([
            'id' => 'provider-request-1',
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'headline' => 'RFQs need review',
                            'summary' => 'Three active RFQs have supplier risk.',
                            'bullets' => ['Review RFQ-1001'],
                            'recommendations' => ['Prioritize high-value events'],
                            'confidence' => 0.84,
                            'metadata' => ['source' => 'test'],
                        ], JSON_THROW_ON_ERROR),
                    ],
                ],
            ],
        ]);
        $client = new ProviderInsightClient(
            $transport,
            new InsightEndpointRegistryStub(
                providerName: 'openrouter',
                endpointUri: 'https://openrouter.ai/api/v1/chat/completions',
                modelId: 'google/gemma-4-26b-a4b-it:free',
            ),
        );

        $response = $client->summarize(new InsightSummaryRequest(
            featureKey: 'rfq_ai_insights',
            tenantId: 'tenant-1',
            subjectType: 'rfq',
            facts: ['rfq_number' => 'RFQ-1001'],
        ));

        self::assertSame(AiStatusSchema::ENDPOINT_GROUP_INSIGHT, $transport->endpointGroup);
        self::assertSame('google/gemma-4-26b-a4b-it:free', $transport->payload['model'] ?? null);
        self::assertArrayHasKey('messages', $transport->payload);
        self::assertArrayNotHasKey('facts', $transport->payload);
        self::assertArrayNotHasKey('feature_key', $transport->payload);
        self::assertStringContainsString('"feature_key":"rfq_ai_insights"', $transport->payload['messages'][1]['content']);
        self::assertSame('RFQs need review', $response['payload']['headline']);
        self::assertSame('openrouter', $response['provenance']['provider_name']);
        self::assertSame(AiStatusSchema::ENDPOINT_GROUP_INSIGHT, $response['provenance']['endpoint_group']);
        self::assertSame('google/gemma-4-26b-a4b-it:free', $response['provenance']['model']);
        self::assertSame('provider-request-1', $response['provenance']['provider_request_id']);
    }

    public function testOpenRouterInsightClientRejectsMissingChatContent(): void
    {
        $client = new ProviderInsightClient(
            new InsightTransportSpy(['choices' => []]),
            new InsightEndpointRegistryStub(
                providerName: 'openrouter',
                endpointUri: 'https://openrouter.ai/api/v1/chat/completions',
                modelId: 'google/gemma-4-26b-a4b-it:free',
            ),
        );

        $this->expectException(AiTransportInvalidResponseException::class);
        $this->expectExceptionMessage('message content');

        $client->summarize(new InsightSummaryRequest(
            featureKey: 'dashboard_ai_summary',
            tenantId: 'tenant-1',
            subjectType: 'dashboard',
            facts: [],
        ));
    }

    public function testNonChatInsightEndpointsKeepTheExistingProviderPayload(): void
    {
        $transport = new InsightTransportSpy(['headline' => 'Generated']);
        $client = new ProviderInsightClient(
            $transport,
            new InsightEndpointRegistryStub(
                providerName: 'internal-provider',
                endpointUri: 'https://ai.example.test/insight',
                modelId: null,
            ),
        );

        $response = $client->summarize(new InsightSummaryRequest(
            featureKey: 'dashboard_ai_summary',
            tenantId: 'tenant-1',
            subjectType: 'dashboard',
            facts: ['active_rfqs' => 16],
        ));

        self::assertSame('dashboard_ai_summary', $transport->payload['feature_key'] ?? null);
        self::assertSame(['active_rfqs' => 16], $transport->payload['facts'] ?? null);
        self::assertSame('summary', $transport->payload['action'] ?? null);
        self::assertSame(['headline' => 'Generated'], $response);
    }
}

final class InsightTransportSpy implements ProviderAiTransportInterface
{
    public ?string $endpointGroup = null;

    /**
     * @var array<string, mixed>
     */
    public array $payload = [];

    /**
     * @param array<string, mixed> $response
     */
    public function __construct(private readonly array $response)
    {
    }

    public function invoke(string $endpointGroup, array $payload): array
    {
        $this->endpointGroup = $endpointGroup;
        $this->payload = $payload;

        return $this->response;
    }
}

final readonly class InsightEndpointRegistryStub implements AiEndpointRegistryInterface
{
    public function __construct(
        private string $providerName,
        private string $endpointUri,
        private ?string $modelId,
    ) {
    }

    public function mode(): string
    {
        return 'provider';
    }

    public function providerName(): string
    {
        return $this->providerName;
    }

    public function endpointGroups(): array
    {
        return [AiStatusSchema::ENDPOINT_GROUP_INSIGHT];
    }

    public function endpointConfig(string $endpointGroup): ?AiEndpointConfig
    {
        if ($endpointGroup !== AiStatusSchema::ENDPOINT_GROUP_INSIGHT) {
            return null;
        }

        $metadata = [];
        if ($this->modelId !== null) {
            $metadata['model_id'] = $this->modelId;
        }

        return new AiEndpointConfig(
            endpointGroup: AiEndpointGroup::INSIGHT,
            providerName: $this->providerName,
            endpointUri: $this->endpointUri,
            metadata: $metadata,
        );
    }
}

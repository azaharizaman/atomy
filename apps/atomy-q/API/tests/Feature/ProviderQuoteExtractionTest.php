<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Adapters\Ai\ConfiguredAiEndpointRegistry;
use App\Adapters\Ai\Contracts\AiEndpointRegistryInterface;
use App\Adapters\Ai\Contracts\DocumentExtractionMapperInterface;
use App\Adapters\Ai\Contracts\DocumentPayloadFactoryInterface;
use App\Adapters\Ai\Contracts\ProviderAiTransportInterface;
use App\Adapters\Ai\Contracts\ProviderDocumentIntelligenceClientInterface;
use App\Adapters\Ai\Exceptions\AiTransportFailedException;
use App\Adapters\Ai\Support\OpenRouterDocumentExtractionMapper;
use App\Adapters\Ai\Support\OpenRouterDocumentPayloadFactory;
use App\Adapters\Ai\Support\SamplePath;
use App\Adapters\Ai\ProviderDocumentIntelligenceClient;
use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Models\RfqLineItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Nexus\QuoteIngestion\QuoteIngestionOrchestrator;
use Nexus\IntelligenceOperations\DTOs\AiCapabilityStatus;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Nexus\QuotationIntelligence\Contracts\OrchestratorContentProcessorInterface;
use Nexus\QuotationIntelligence\Contracts\QuotationIntelligenceCoordinatorInterface;
use Tests\Support\FakeOpenRouterDocumentResponses;
use Tests\Feature\Api\ApiTestCase;
use Tests\Feature\Api\Concerns\BindsAiRuntimeStatus;

final class ProviderQuoteExtractionTest extends ApiTestCase
{
    use BindsAiRuntimeStatus;
    use RefreshDatabase;

    public function createApplication(): \Illuminate\Foundation\Application
    {
        $app = parent::createApplication();
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $app['config']->set('queue.default', 'sync');

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->bindAiRuntimeStatus([
            'quote_document_extraction' => new AiCapabilityStatus(
                featureKey: 'quote_document_extraction',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_DOCUMENT_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_DOCUMENT,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                messageKey: 'ai.quote_document_extraction.available',
                status: AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
                available: true,
                reasonCodes: ['provider_available'],
                operatorCritical: true,
                diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
            ),
        ]);
    }

    private function createUser(): User
    {
        /** @var User $user */
        $user = User::query()->create([
            'tenant_id' => (string) Str::ulid(),
            'email' => 'provider-' . Str::lower((string) Str::ulid()) . '@example.com',
            'name' => 'Provider Test User',
            'password_hash' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
        ]);

        return $user;
    }

    private function createRfq(User $user): Rfq
    {
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-PROVIDER-' . Str::upper(Str::random(6)),
            'title' => 'Provider Test RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'draft',
        ]);

        RfqLineItem::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'description' => 'Breakdown assist jump start vehicle',
            'quantity' => 1,
            'uom' => 'EA',
            'unit_price' => 80.0,
            'currency' => 'RM',
            'sort_order' => 0,
        ]);

        return $rfq;
    }

    private function sampleQuotePdf(): UploadedFile
    {
        $path = realpath(SamplePath::root() . '/1A/1A-1.pdf');
        self::assertNotFalse($path);
        self::assertFileExists($path);

        return new UploadedFile($path, '1A-1.pdf', 'application/pdf', null, true);
    }

    private function refreshProviderBindings(): void
    {
        foreach ([
            ConfiguredAiEndpointRegistry::class,
            AiEndpointRegistryInterface::class,
            DocumentPayloadFactoryInterface::class,
            DocumentExtractionMapperInterface::class,
            ProviderAiTransportInterface::class,
            ProviderDocumentIntelligenceClient::class,
            ProviderDocumentIntelligenceClientInterface::class,
            OpenRouterDocumentPayloadFactory::class,
            OpenRouterDocumentExtractionMapper::class,
            OrchestratorContentProcessorInterface::class,
            QuotationIntelligenceCoordinatorInterface::class,
            QuoteIngestionOrchestrator::class,
        ] as $abstract) {
            app()->forgetInstance($abstract);
        }
    }

    public function testProviderModeUploadPersistsExtractedSourceLinesFromDocumentAi(): void
    {
        $this->configureProviderDocumentEndpoint();
        $transport = new class implements ProviderAiTransportInterface {
            /** @var array<string, mixed> */
            public array $payload = [];

            public function invoke(string $endpointGroup, array $payload): array
            {
                $this->payload = $payload;

                return FakeOpenRouterDocumentResponses::successfulQuoteExtraction();
            }
        };
        $this->app->instance(ProviderAiTransportInterface::class, $transport);
        $this->refreshProviderBindings();
        $this->app->instance(ProviderAiTransportInterface::class, $transport);

        $user = $this->createUser();
        $rfq = $this->createRfq($user);

        $response = $this->withHeaders($this->authHeaders((string) $user->tenant_id, (string) $user->id))
            ->post('/api/v1/quote-submissions/upload', [
                'rfq_id' => $rfq->id,
                'vendor_id' => (string) Str::ulid(),
                'vendor_name' => 'Kuching Utama Sdn Bhd',
                'file' => $this->sampleQuotePdf(),
            ]);

        $response->assertCreated();
        self::assertContains($response->json('data.status'), ['ready', 'needs_review']);

        $submission = QuoteSubmission::query()->findOrFail((string) $response->json('data.id'));
        self::assertContains($submission->status, ['ready', 'needs_review']);
        self::assertSame(1, $submission->normalizationSourceLines()->count());
        self::assertSame(
            'BREAKDOWN ASSIST JUMP START VEHICLE',
            $submission->normalizationSourceLines()->firstOrFail()->source_description,
        );

        self::assertSame('mistral-ocr', $transport->payload['plugins'][0]['pdf']['engine'] ?? null);
    }

    public function testProviderModeUploadStillDispatchesExtractionWhenHealthSnapshotIsUnavailableButEndpointIsConfigured(): void
    {
        $this->bindAiRuntimeStatus([
            'quote_document_extraction' => new AiCapabilityStatus(
                featureKey: 'quote_document_extraction',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_DOCUMENT_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_DOCUMENT,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                messageKey: 'ai.quote_document_extraction.manual_continuity',
                status: AiStatusSchema::CAPABILITY_STATUS_DEGRADED,
                available: false,
                reasonCodes: ['health_probe_failed'],
                operatorCritical: true,
                diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
            ),
        ]);

        $this->configureProviderDocumentEndpoint();
        $transport = new class implements ProviderAiTransportInterface {
            public function invoke(string $endpointGroup, array $payload): array
            {
                return FakeOpenRouterDocumentResponses::successfulQuoteExtraction();
            }
        };
        $this->app->instance(ProviderAiTransportInterface::class, $transport);
        $this->refreshProviderBindings();
        $this->app->instance(ProviderAiTransportInterface::class, $transport);

        $user = $this->createUser();
        $rfq = $this->createRfq($user);

        $response = $this->withHeaders($this->authHeaders((string) $user->tenant_id, (string) $user->id))
            ->post('/api/v1/quote-submissions/upload', [
                'rfq_id' => $rfq->id,
                'vendor_id' => (string) Str::ulid(),
                'vendor_name' => 'Probe Degraded Vendor',
                'file' => $this->sampleQuotePdf(),
            ]);

        $response->assertCreated();

        $submission = QuoteSubmission::query()->findOrFail((string) $response->json('data.id'));
        self::assertContains($submission->status, ['ready', 'needs_review']);
        self::assertSame(1, $submission->normalizationSourceLines()->count());
        self::assertNull($submission->error_code);
    }

    public function testProviderModeUploadHandlesTransportErrors(): void
    {
        $this->configureProviderDocumentEndpoint();
        $this->bindTransport(new class implements ProviderAiTransportInterface {
            public function invoke(string $endpointGroup, array $payload): array
            {
                throw new AiTransportFailedException('AI endpoint [document] returned an unsuccessful response.');
            }
        });

        $response = $this->uploadSampleQuote();

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'failed');
        $response->assertJsonPath('data.error_code', 'INTELLIGENCE_FAILED');
        self::assertSame('failed', QuoteSubmission::query()->findOrFail((string) $response->json('data.id'))->status);
        self::assertSame(0, QuoteSubmission::query()->findOrFail((string) $response->json('data.id'))->normalizationSourceLines()->count());
    }

    public function testProviderModeUploadHandlesInvalidJson(): void
    {
        $this->configureProviderDocumentEndpoint();
        $this->bindTransport(new class implements ProviderAiTransportInterface {
            public function invoke(string $endpointGroup, array $payload): array
            {
                return [
                    'choices' => [[
                        'message' => [
                            'content' => '{invalid-json',
                        ],
                    ]],
                ];
            }
        });

        $response = $this->uploadSampleQuote();

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'failed');
        $response->assertJsonPath('data.error_code', 'INTELLIGENCE_FAILED');
    }

    public function testProviderModeUploadHandlesOversizedFile(): void
    {
        $this->configureProviderDocumentEndpoint();
        config()->set('atomy.ai.endpoints.document.max_file_size_bytes', 1);
        $transport = new class implements ProviderAiTransportInterface {
            public function invoke(string $endpointGroup, array $payload): array
            {
                self::fail('Transport should not be invoked when the provider payload factory rejects an oversized file.');
            }
        };
        $this->bindTransport($transport);

        $response = $this->uploadSampleQuote();

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'failed');
        $response->assertJsonPath('data.error_code', 'INTELLIGENCE_FAILED');
    }

    private function configureProviderDocumentEndpoint(): void
    {
        config()->set('atomy.ai.mode', 'provider');
        config()->set('atomy.ai.endpoints.document.uri', 'https://openrouter.example.test/chat/completions');
        config()->set('atomy.ai.endpoints.document.model_id', 'baidu/qianfan-ocr-fast:free');
        config()->set('atomy.ai.endpoints.document.parser_plugin', 'file-parser');
        config()->set('atomy.ai.endpoints.document.pdf_engine', 'mistral-ocr');
        config()->set('atomy.ai.endpoints.document.health_url', 'https://openrouter.example.test/chat/completions/health');
    }

    private function bindTransport(ProviderAiTransportInterface $transport): void
    {
        $this->app->instance(ProviderAiTransportInterface::class, $transport);
        $this->refreshProviderBindings();
        $this->app->instance(ProviderAiTransportInterface::class, $transport);
    }

    private function uploadSampleQuote(): \Illuminate\Testing\TestResponse
    {
        $user = $this->createUser();
        $rfq = $this->createRfq($user);

        return $this->withHeaders($this->authHeaders((string) $user->tenant_id, (string) $user->id))
            ->post('/api/v1/quote-submissions/upload', [
                'rfq_id' => $rfq->id,
                'vendor_id' => (string) Str::ulid(),
                'vendor_name' => 'Kuching Utama Sdn Bhd',
                'file' => $this->sampleQuotePdf(),
            ]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ComparisonRun;
use App\Models\NormalizationSourceLine;
use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Models\RfqLineItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Nexus\QuotationIntelligence\Contracts\BatchQuoteComparisonCoordinatorInterface;
use Nexus\QuotationIntelligence\Exceptions\DocumentAccessDeniedException;
use Tests\Feature\Api\ApiTestCase;

final class ComparisonRunWorkflowTest extends ApiTestCase
{
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

        return $app;
    }

    private function createUser(): User
    {
        /** @var User $user */
        $user = User::query()->create([
            'tenant_id' => (string) Str::ulid(),
            'email' => 'cmp-' . Str::lower((string) Str::ulid()) . '@example.com',
            'name' => 'Comparison User',
            'password_hash' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
        ]);

        return $user;
    }

    /**
     * @return array{0: Rfq, 1: QuoteSubmission, 2: QuoteSubmission}
     */
    private function seedReadyComparisonContext(User $user): array
    {
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-CMP-' . Str::lower((string) Str::ulid()),
            'title' => 'Comparison RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(7),
            'closing_date' => now()->subDay(),
            'status' => 'published',
        ]);

        $lineItem = RfqLineItem::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'description' => 'Pump assembly',
            'quantity' => 1,
            'uom' => 'EA',
            'unit_price' => 100,
            'currency' => 'USD',
            'sort_order' => 0,
        ]);

        $quote = QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_id' => (string) Str::ulid(),
            'vendor_name' => 'Vendor One',
            'status' => 'ready',
            'file_path' => 'quote-submissions/' . Str::lower((string) Str::ulid()) . '.pdf',
            'file_type' => 'application/pdf',
            'original_filename' => 'quote.pdf',
            'submitted_at' => now(),
            'confidence' => 98.0,
            'line_items_count' => 1,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        NormalizationSourceLine::query()->create([
            'tenant_id' => $user->tenant_id,
            'quote_submission_id' => $quote->id,
            'rfq_line_item_id' => $lineItem->id,
            'source_description' => 'Pump assembly',
            'source_unit_price' => 110.0,
            'source_quantity' => 1.0,
            'source_uom' => 'EA',
            'sort_order' => 0,
        ]);

        $secondQuote = QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_id' => (string) Str::ulid(),
            'vendor_name' => 'Vendor Two',
            'status' => 'ready',
            'file_path' => 'quote-submissions/' . Str::lower((string) Str::ulid()) . '.pdf',
            'file_type' => 'application/pdf',
            'original_filename' => 'quote-2.pdf',
            'submitted_at' => now(),
            'confidence' => 97.0,
            'line_items_count' => 1,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        NormalizationSourceLine::query()->create([
            'tenant_id' => $user->tenant_id,
            'quote_submission_id' => $secondQuote->id,
            'rfq_line_item_id' => $lineItem->id,
            'source_description' => 'Pump assembly',
            'source_unit_price' => 115.0,
            'source_quantity' => 1.0,
            'source_uom' => 'EA',
            'sort_order' => 0,
        ]);

        return [$rfq, $quote, $secondQuote];
    }

    public function test_preview_comparison_run_returns_real_persisted_id_and_live_payloads(): void
    {
        $user = $this->createUser();
        [$rfq] = $this->seedReadyComparisonContext($user);

        $response = $this->postJson(
            '/api/v1/comparison-runs/preview',
            ['rfq_id' => (string) $rfq->id],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'preview');
        $response->assertJsonPath('data.is_preview', true);
        $response->assertJsonPath('data.rfq_id', (string) $rfq->id);
        $this->assertNotEmpty($response->json('data.id'));
        $this->assertFalse(str_starts_with((string) $response->json('data.id'), 'cr-preview-'));
        $this->assertNotEmpty($response->json('data.matrix.clusters'));
        $this->assertSame(true, $response->json('data.readiness.is_ready'));

        $this->assertDatabaseHas('comparison_runs', [
            'id' => $response->json('data.id'),
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'is_preview' => true,
            'status' => 'preview',
        ]);
    }

    public function test_final_comparison_uses_final_coordinator_path(): void
    {
        $user = $this->createUser();
        [$rfq] = $this->seedReadyComparisonContext($user);

        $coordinator = $this->createMock(BatchQuoteComparisonCoordinatorInterface::class);
        $coordinator->expects($this->once())
            ->method('compareQuotes')
            ->with(
                (string) $user->tenant_id,
                (string) $rfq->id,
                $this->callback(static fn (array $documentIds): bool => count($documentIds) === 2),
            )
            ->willReturn([
                'tenant_id' => (string) $user->tenant_id,
                'rfq_id' => (string) $rfq->id,
                'documents_processed' => 2,
                'matrix' => ['clusters' => []],
                'scoring' => [],
                'approval' => [],
                'readiness' => [
                    'is_ready' => true,
                    'is_preview_only' => false,
                    'blockers' => [],
                    'warnings' => [],
                ],
            ]);
        $coordinator->expects($this->never())->method('previewQuotes');

        $this->app->instance(BatchQuoteComparisonCoordinatorInterface::class, $coordinator);

        $response = $this->postJson(
            '/api/v1/comparison-runs/final',
            ['rfq_id' => (string) $rfq->id],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'final');
        $response->assertJsonPath('data.matrix.clusters', []);
        $response->assertJsonPath('data.readiness.is_ready', true);
        $this->assertDatabaseHas('comparison_runs', [
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'is_preview' => false,
            'status' => 'final',
        ]);
    }

    public function test_final_comparison_rolls_back_when_coordinator_fails(): void
    {
        $user = $this->createUser();
        [$rfq] = $this->seedReadyComparisonContext($user);

        $coordinator = $this->createMock(BatchQuoteComparisonCoordinatorInterface::class);
        $coordinator->expects($this->once())
            ->method('compareQuotes')
            ->willThrowException(new DocumentAccessDeniedException('Document denied'));
        $coordinator->expects($this->never())->method('previewQuotes');

        $this->app->instance(BatchQuoteComparisonCoordinatorInterface::class, $coordinator);

        $response = $this->postJson(
            '/api/v1/comparison-runs/final',
            ['rfq_id' => (string) $rfq->id],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertStatus(422);
        $response->assertJsonPath('code', 'DocumentAccessDeniedException');
        $this->assertDatabaseMissing('comparison_runs', [
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'status' => 'final',
        ]);
    }

    public function test_matrix_and_readiness_endpoints_return_stored_payloads_for_tenant(): void
    {
        $user = $this->createUser();
        [$rfq] = $this->seedReadyComparisonContext($user);

        $run = ComparisonRun::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'name' => 'Preview comparison',
            'description' => null,
            'idempotency_key' => null,
            'is_preview' => true,
            'created_by' => $user->id,
            'request_payload' => ['rfq_id' => $rfq->id],
            'matrix_payload' => [
                'clusters' => [
                    ['cluster_key' => 'rfq:line-1', 'offers' => []],
                ],
            ],
            'scoring_payload' => [],
            'approval_payload' => [],
            'response_payload' => [],
            'readiness_payload' => [
                'is_ready' => true,
                'blockers' => [],
                'warnings' => [],
            ],
            'status' => 'preview',
            'version' => 1,
            'expires_at' => null,
            'discarded_at' => null,
            'discarded_by' => null,
        ]);

        $matrixResponse = $this->getJson(
            '/api/v1/comparison-runs/' . $run->id . '/matrix',
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );
        $matrixResponse->assertOk();
        $matrixResponse->assertJsonPath('data.id', (string) $run->id);
        $matrixResponse->assertJsonPath('data.matrix.clusters.0.cluster_key', 'rfq:line-1');

        $readinessResponse = $this->getJson(
            '/api/v1/comparison-runs/' . $run->id . '/readiness',
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );
        $readinessResponse->assertOk();
        $readinessResponse->assertJsonPath('data.id', (string) $run->id);
        $readinessResponse->assertJsonPath('data.readiness.is_ready', true);
    }

    public function test_matrix_and_readiness_endpoints_return_404_for_wrong_tenant(): void
    {
        $owner = $this->createUser();
        [$rfq] = $this->seedReadyComparisonContext($owner);
        $otherUser = $this->createUser();

        $run = ComparisonRun::query()->create([
            'tenant_id' => $owner->tenant_id,
            'rfq_id' => $rfq->id,
            'name' => 'Final comparison',
            'description' => null,
            'idempotency_key' => null,
            'is_preview' => false,
            'created_by' => $owner->id,
            'request_payload' => ['rfq_id' => $rfq->id],
            'matrix_payload' => ['clusters' => []],
            'scoring_payload' => [],
            'approval_payload' => [],
            'response_payload' => [],
            'readiness_payload' => ['is_ready' => true, 'blockers' => [], 'warnings' => []],
            'status' => 'final',
            'version' => 1,
            'expires_at' => null,
            'discarded_at' => null,
            'discarded_by' => null,
        ]);

        $this->getJson(
            '/api/v1/comparison-runs/' . $run->id . '/matrix',
            $this->authHeaders((string) $otherUser->tenant_id, (string) $otherUser->id),
        )->assertStatus(404);

        $this->getJson(
            '/api/v1/comparison-runs/' . $run->id . '/readiness',
            $this->authHeaders((string) $otherUser->tenant_id, (string) $otherUser->id),
        )->assertStatus(404);
    }

    public function test_alpha_only_controls_return_explicit_not_supported_response(): void
    {
        $user = $this->createUser();
        [$rfq] = $this->seedReadyComparisonContext($user);

        $run = ComparisonRun::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'name' => 'Preview comparison',
            'description' => null,
            'idempotency_key' => null,
            'is_preview' => true,
            'created_by' => $user->id,
            'request_payload' => ['rfq_id' => $rfq->id],
            'matrix_payload' => ['clusters' => []],
            'scoring_payload' => [],
            'approval_payload' => [],
            'response_payload' => [],
            'readiness_payload' => ['is_ready' => true, 'blockers' => [], 'warnings' => []],
            'status' => 'preview',
            'version' => 1,
            'expires_at' => null,
            'discarded_at' => null,
            'discarded_by' => null,
        ]);

        $scoringModelResponse = $this->patchJson(
            '/api/v1/comparison-runs/' . $run->id . '/scoring-model',
            [],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );
        $scoringModelResponse->assertStatus(422);
        $scoringModelResponse->assertJsonPath('code', 'COMPARISON_CONTROL_DEFERRED');

        $lockResponse = $this->postJson(
            '/api/v1/comparison-runs/' . $run->id . '/lock',
            [],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );
        $lockResponse->assertStatus(422);
        $lockResponse->assertJsonPath('code', 'COMPARISON_CONTROL_DEFERRED');

        $unlockResponse = $this->postJson(
            '/api/v1/comparison-runs/' . $run->id . '/unlock',
            [],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );
        $unlockResponse->assertStatus(422);
        $unlockResponse->assertJsonPath('code', 'COMPARISON_CONTROL_DEFERRED');
    }

    public function test_preview_maps_quotation_intelligence_domain_errors_to_422(): void
    {
        $user = $this->createUser();
        [$rfq] = $this->seedReadyComparisonContext($user);

        $coordinator = $this->createMock(BatchQuoteComparisonCoordinatorInterface::class);
        $coordinator->expects($this->once())
            ->method('previewQuotes')
            ->willThrowException(new DocumentAccessDeniedException('Document denied'));
        $coordinator->expects($this->never())->method('compareQuotes');

        $this->app->instance(BatchQuoteComparisonCoordinatorInterface::class, $coordinator);

        $response = $this->postJson(
            '/api/v1/comparison-runs/preview',
            ['rfq_id' => (string) $rfq->id],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertStatus(422);
        $response->assertJsonPath('code', 'DocumentAccessDeniedException');
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Approval;
use App\Models\ComparisonRun;
use App\Models\NormalizationSourceLine;
use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Models\RfqLineItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Feature\Api\ApiTestCase;

final class ComparisonSnapshotWorkflowTest extends ApiTestCase
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
            'email' => 'snap-' . Str::lower((string) Str::ulid()) . '@example.com',
            'name' => 'Snapshot User',
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
     * @return array{0: User, 1: Rfq, 2: RfqLineItem, 3: QuoteSubmission}
     */
    private function seedReadyQuoteForFinal(User $user): array
    {
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-SNAP-' . Str::lower((string) Str::ulid()),
            'title' => 'Snapshot RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'published',
        ]);

        $lineItem = RfqLineItem::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'description' => 'Line',
            'quantity' => 1,
            'uom' => 'ea',
            'unit_price' => 10,
            'currency' => 'USD',
            'sort_order' => 0,
        ]);

        $quote = QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_id' => (string) Str::ulid(),
            'vendor_name' => 'Vendor',
            'status' => 'ready',
            'file_path' => 'quote-submissions/' . Str::lower((string) Str::ulid()) . '.pdf',
            'file_type' => 'application/pdf',
            'original_filename' => 'quote.pdf',
            'submitted_at' => now(),
            'confidence' => 100.0,
            'line_items_count' => 1,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        NormalizationSourceLine::query()->create([
            'tenant_id' => $user->tenant_id,
            'quote_submission_id' => $quote->id,
            'rfq_line_item_id' => $lineItem->id,
            'source_description' => 'Line',
            'source_unit_price' => 10,
            'sort_order' => 0,
        ]);

        return [$user, $rfq, $lineItem, $quote];
    }

    public function test_final_comparison_run_captures_snapshot_inputs(): void
    {
        [$user, $rfq] = $this->seedReadyQuoteForFinal($this->createUser());

        $response = $this->postJson(
            '/api/v1/comparison-runs/final',
            ['rfq_id' => $rfq->id],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'final');
        $this->assertNotNull($response->json('data.snapshot.normalized_lines'));
        $this->assertGreaterThan(0, count((array) $response->json('data.snapshot.normalized_lines')));
        $this->assertNotEmpty($response->json('data.matrix.clusters'));
        $this->assertSame(true, $response->json('data.readiness.is_ready'));

        /** @var ComparisonRun|null $run */
        $run = ComparisonRun::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('rfq_id', $rfq->id)
            ->where('is_preview', false)
            ->latest('created_at')
            ->first();

        $this->assertNotNull($run);
        $this->assertNotEmpty($run?->matrix_payload['clusters'] ?? []);
        $this->assertSame(true, $run?->readiness_payload['is_ready'] ?? false);
    }

    public function test_approval_cannot_proceed_when_submission_has_blocking_issues(): void
    {
        $user = $this->createUser();
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-APP-' . Str::lower((string) Str::ulid()),
            'title' => 'Approval RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'published',
        ]);

        $lineItem = RfqLineItem::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'description' => 'Line',
            'quantity' => 1,
            'uom' => 'ea',
            'unit_price' => 10,
            'currency' => 'USD',
            'sort_order' => 0,
        ]);

        QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_id' => (string) Str::ulid(),
            'vendor_name' => 'Vendor',
            'status' => 'needs_review',
            'submitted_at' => now(),
            'confidence' => 50.0,
            'line_items_count' => 1,
            'warnings_count' => 1,
            'errors_count' => 0,
        ]);

        $run = ComparisonRun::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'name' => 'Final',
            'description' => null,
            'idempotency_key' => null,
            'is_preview' => false,
            'created_by' => $user->id,
            'request_payload' => [],
            'matrix_payload' => [],
            'scoring_payload' => [],
            'approval_payload' => [],
            'response_payload' => [
                'snapshot' => [
                    'normalized_lines' => [['quote_submission_id' => 'x']],
                    'rfq_version' => 1,
                    'resolutions' => [],
                    'currency_meta' => [(string) $lineItem->id => 'USD'],
                ],
            ],
            'readiness_payload' => [],
            'status' => 'final',
            'version' => 1,
            'expires_at' => null,
            'discarded_at' => null,
            'discarded_by' => null,
        ]);

        $approval = Approval::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'comparison_run_id' => $run->id,
            'type' => 'comparison_approval',
            'status' => 'pending',
            'requested_by' => $user->id,
            'requested_at' => now(),
            'amount' => null,
            'currency' => null,
            'level' => 1,
            'notes' => null,
            'approved_at' => null,
            'approved_by' => null,
            'snoozed_until' => null,
        ]);

        $response = $this->postJson(
            '/api/v1/approvals/' . $approval->id . '/approve',
            [],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertStatus(422);
    }

    public function test_freezing_a_snapshot_writes_decision_trail_entry(): void
    {
        [$user, $rfq] = $this->seedReadyQuoteForFinal($this->createUser());

        $this->postJson(
            '/api/v1/comparison-runs/final',
            ['rfq_id' => $rfq->id],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        )->assertCreated();

        $this->assertDatabaseHas('decision_trail_entries', [
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'event_type' => 'comparison_snapshot_frozen',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DecisionTrailEntry;
use App\Models\NormalizationConflict;
use App\Models\NormalizationSourceLine;
use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Models\RfqLineItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Feature\Api\ApiTestCase;

final class NormalizationReviewWorkflowTest extends ApiTestCase
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
            'email' => 'norm-' . Str::lower((string) Str::ulid()) . '@example.com',
            'name' => 'Normalization User',
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
     * @return array{0: Rfq, 1: RfqLineItem, 2: QuoteSubmission}
     */
    private function createManualReviewFixture(User $user, string $status = 'needs_review'): array
    {
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-MANUAL-' . Str::upper(Str::random(6)),
            'title' => 'Manual Source Line RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'published',
        ]);

        $lineItem = RfqLineItem::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'description' => 'Manual Widget',
            'quantity' => 3,
            'uom' => 'EA',
            'unit_price' => 25,
            'currency' => 'USD',
            'sort_order' => 0,
        ]);

        $quote = QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_id' => (string) Str::ulid(),
            'vendor_name' => 'Manual Vendor',
            'status' => $status,
            'submitted_at' => now(),
            'confidence' => null,
            'line_items_count' => 0,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        return [$rfq, $lineItem, $quote];
    }

    public function test_submission_stays_in_needs_review_when_required_line_is_unmapped(): void
    {
        $user = $this->createUser();
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-NORM-1',
            'title' => 'Normalization RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'published',
        ]);

        $lineItem = RfqLineItem::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'description' => 'Widget',
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
            'status' => 'needs_review',
            'submitted_at' => now(),
            'confidence' => 80.0,
            'line_items_count' => 1,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        NormalizationSourceLine::query()->create([
            'tenant_id' => $user->tenant_id,
            'quote_submission_id' => $quote->id,
            'rfq_line_item_id' => null,
            'source_description' => 'Widget line',
            'source_unit_price' => 10,
            'sort_order' => 0,
        ]);

        $response = $this->getJson(
            '/api/v1/normalization/' . $rfq->id . '/conflicts',
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('meta.has_blocking_issues', true);
    }

    public function test_normalization_conflict_resolution_marks_submission_ready_only_when_no_blockers_remain(): void
    {
        $user = $this->createUser();
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-NORM-2',
            'title' => 'Normalization RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'published',
        ]);

        $lineItem = RfqLineItem::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'description' => 'Widget',
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
            'status' => 'needs_review',
            'submitted_at' => now(),
            'confidence' => 80.0,
            'line_items_count' => 1,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        $sourceLine = NormalizationSourceLine::query()->create([
            'tenant_id' => $user->tenant_id,
            'quote_submission_id' => $quote->id,
            'rfq_line_item_id' => $lineItem->id,
            'source_description' => 'Widget line',
            'source_unit_price' => 10,
            'sort_order' => 0,
        ]);

        $conflict = NormalizationConflict::query()->create([
            'tenant_id' => $user->tenant_id,
            'normalization_source_line_id' => $sourceLine->id,
            'conflict_type' => 'price_mismatch',
            'resolution' => null,
            'resolved_at' => null,
            'resolved_by' => null,
        ]);

        $response = $this->putJson(
            '/api/v1/normalization/conflicts/' . $conflict->id . '/resolve',
            ['resolution' => 'accept_extracted_value'],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertOk();

        $quote->refresh();

        $this->assertSame('ready', $quote->status);
    }

    public function test_source_lines_endpoint_returns_live_rows_and_mappings(): void
    {
        $user = $this->createUser();
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-NORM-3',
            'title' => 'Normalization RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'published',
        ]);

        $lineItem = RfqLineItem::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'description' => 'Widget',
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
            'submitted_at' => now(),
            'confidence' => 92.0,
            'line_items_count' => 1,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        $sourceLine = NormalizationSourceLine::query()->create([
            'tenant_id' => $user->tenant_id,
            'quote_submission_id' => $quote->id,
            'rfq_line_item_id' => $lineItem->id,
            'source_description' => 'Widget line',
            'source_quantity' => 1,
            'source_uom' => 'ea',
            'source_unit_price' => 10,
            'raw_data' => ['line_number' => 1],
            'sort_order' => 0,
        ]);

        NormalizationConflict::query()->create([
            'tenant_id' => $user->tenant_id,
            'normalization_source_line_id' => $sourceLine->id,
            'conflict_type' => 'price_mismatch',
            'resolution' => null,
            'resolved_at' => null,
            'resolved_by' => null,
        ]);

        $response = $this->getJson(
            '/api/v1/normalization/' . $rfq->id . '/source-lines',
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('meta.rfq_id', $rfq->id);
        $response->assertJsonPath('meta.source_line_count', 1);
    }

    public function test_manual_source_line_add_edit_and_delete_persists_manual_provenance_and_updates_readiness(): void
    {
        $user = $this->createUser();
        [, $lineItem, $quote] = $this->createManualReviewFixture($user);
        $headers = $this->authHeaders((string) $user->tenant_id, (string) $user->id);

        $create = $this->postJson(
            '/api/v1/quote-submissions/' . $quote->id . '/source-lines',
            [
                'source_description' => 'Manual Widget quoted line',
                'source_quantity' => '3',
                'source_uom' => 'EA',
                'source_unit_price' => '24.50',
                'rfq_line_item_id' => $lineItem->id,
                'note' => 'Typed from vendor email after extraction outage',
            ],
            $headers,
        );

        $create->assertCreated();
        $create->assertJsonPath('data.origin', 'manual');
        $create->assertJsonPath('data.provenance.origin', 'manual');
        $create->assertJsonPath('data.provenance.user_id', $user->id);
        $create->assertJsonPath('data.provenance.note', 'Typed from vendor email after extraction outage');
        $create->assertJsonPath('data.ai_confidence', null);
        $create->assertJsonPath('data.taxonomy_code', null);
        $create->assertJsonPath('data.provider_provenance', null);
        $create->assertJsonPath('meta.quote_submission_status', 'ready');
        $create->assertJsonPath('meta.manual_action_required', false);

        $lineId = (string) $create->json('data.id');
        $quote->refresh();
        self::assertSame('ready', $quote->status);
        self::assertSame(1, $quote->line_items_count);

        $update = $this->putJson(
            '/api/v1/quote-submissions/' . $quote->id . '/source-lines/' . $lineId,
            [
                'source_description' => 'Manual Widget quoted line revised',
                'source_quantity' => '3',
                'source_uom' => 'EA',
                'source_unit_price' => '23.75',
                'rfq_line_item_id' => $lineItem->id,
                'reason' => 'Corrected unit price from page 2',
            ],
            $headers,
        );

        $update->assertOk();
        $update->assertJsonPath('data.source_description', 'Manual Widget quoted line revised');
        $update->assertJsonPath('data.provenance.reason', 'Corrected unit price from page 2');
        $update->assertJsonPath('data.provenance.user_id', $user->id);
        $update->assertJsonPath('data.ai_confidence', null);
        $update->assertJsonPath('data.provider_provenance', null);

        $delete = $this->deleteJson(
            '/api/v1/quote-submissions/' . $quote->id . '/source-lines/' . $lineId,
            [],
            $headers,
        );

        $delete->assertOk();
        $delete->assertJsonPath('meta.quote_submission_status', 'needs_review');
        $delete->assertJsonPath('meta.manual_action_required', true);

        $quote->refresh();
        self::assertSame('needs_review', $quote->status);
        self::assertSame(0, $quote->line_items_count);
        self::assertSame(3, DecisionTrailEntry::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('comparison_run_id', $quote->id)
            ->whereIn('event_type', [
                'manual_source_line_created',
                'manual_source_line_updated',
                'manual_source_line_deleted',
            ])
            ->count());
    }

    public function test_manual_source_line_routes_are_tenant_scoped_not_found(): void
    {
        $owner = $this->createUser();
        $intruder = $this->createUser();
        [, $lineItem, $quote] = $this->createManualReviewFixture($owner);

        $sourceLine = NormalizationSourceLine::query()->create([
            'tenant_id' => $owner->tenant_id,
            'quote_submission_id' => $quote->id,
            'rfq_line_item_id' => $lineItem->id,
            'source_description' => 'Owner only line',
            'source_quantity' => 1,
            'source_uom' => 'EA',
            'source_unit_price' => 10,
            'raw_data' => ['provenance' => ['origin' => 'manual']],
            'sort_order' => 0,
        ]);

        $headers = $this->authHeaders((string) $intruder->tenant_id, (string) $intruder->id);

        $this->postJson('/api/v1/quote-submissions/' . $quote->id . '/source-lines', [
            'source_description' => 'Cross tenant line',
        ], $headers)->assertNotFound();

        $this->putJson('/api/v1/quote-submissions/' . $quote->id . '/source-lines/' . $sourceLine->id, [
            'source_description' => 'Cross tenant edit',
        ], $headers)->assertNotFound();

        $this->deleteJson(
            '/api/v1/quote-submissions/' . $quote->id . '/source-lines/' . $sourceLine->id,
            [],
            $headers,
        )->assertNotFound();
    }
}

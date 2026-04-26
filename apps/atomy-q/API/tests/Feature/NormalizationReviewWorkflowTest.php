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
                'reason' => 'manual_entry_required',
            ],
            $headers,
        );

        $create->assertCreated();
        $create->assertJsonPath('data.origin', 'manual');
        $create->assertJsonPath('data.provenance.origin', 'manual');
        $create->assertJsonPath('data.provenance.user_id', $user->id);
        $create->assertJsonPath('data.provenance.note', 'Typed from vendor email after extraction outage');
        $create->assertJsonPath('data.provenance.reason', 'manual_entry_required');
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
                'reason' => 'price_correction',
                'note' => 'Corrected unit price from page 2',
            ],
            $headers,
        );

        $update->assertOk();
        $update->assertJsonPath('data.source_description', 'Manual Widget quoted line revised');
        $update->assertJsonPath('data.provenance.reason', 'price_correction');
        $update->assertJsonPath('data.provenance.note', 'Corrected unit price from page 2');
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
            'reason' => 'manual_entry_required',
        ], $headers)->assertNotFound();

        $this->putJson('/api/v1/quote-submissions/' . $quote->id . '/source-lines/' . $sourceLine->id, [
            'source_description' => 'Cross tenant edit',
            'reason' => 'manual_entry_required',
        ], $headers)->assertNotFound();

        $this->deleteJson(
            '/api/v1/quote-submissions/' . $quote->id . '/source-lines/' . $sourceLine->id,
            [],
            $headers,
        )->assertNotFound();
    }

    public function test_manual_source_line_create_requires_structured_reason(): void
    {
        $user = $this->createUser();
        [, $lineItem, $quote] = $this->createManualReviewFixture($user);
        $headers = $this->authHeaders((string) $user->tenant_id, (string) $user->id);

        $missingReason = $this->postJson(
            '/api/v1/quote-submissions/' . $quote->id . '/source-lines',
            [
                'source_description' => 'Manual Widget quoted line',
                'rfq_line_item_id' => $lineItem->id,
            ],
            $headers,
        );

        $missingReason->assertStatus(422);
        $missingReason->assertJsonPath('details.reason.0', 'The reason field is required.');
    }

    public function test_manual_source_line_update_requires_note_for_other_reason(): void
    {
        $user = $this->createUser();
        [, $lineItem, $quote] = $this->createManualReviewFixture($user);
        $headers = $this->authHeaders((string) $user->tenant_id, (string) $user->id);

        $create = $this->postJson(
            '/api/v1/quote-submissions/' . $quote->id . '/source-lines',
            [
                'source_description' => 'Manual Widget quoted line valid',
                'rfq_line_item_id' => $lineItem->id,
                'reason' => 'manual_entry_required',
            ],
            $headers,
        );

        $create->assertCreated();
        $lineId = (string) $create->json('data.id');

        $missingOtherNote = $this->putJson(
            '/api/v1/quote-submissions/' . $quote->id . '/source-lines/' . $lineId,
            [
                'source_description' => 'Manual Widget quoted line other reason',
                'rfq_line_item_id' => $lineItem->id,
                'reason' => 'other',
            ],
            $headers,
        );

        $missingOtherNote->assertStatus(422);
        $missingOtherNote->assertJsonPath('details.note.0', 'The note field is required when reason is other.');
    }

    public function test_source_lines_endpoint_serializes_provider_suggested_effective_and_override_state(): void
    {
        $user = $this->createUser();
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-NORM-4',
            'title' => 'Normalization RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'published',
        ]);

        $lineItem = RfqLineItem::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'description' => 'Pump Assembly',
            'quantity' => 4,
            'uom' => 'EA',
            'unit_price' => 11.50,
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
            'confidence' => 91.0,
            'line_items_count' => 1,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        NormalizationSourceLine::query()->create([
            'tenant_id' => $user->tenant_id,
            'quote_submission_id' => $quote->id,
            'rfq_line_item_id' => $lineItem->id,
            'source_description' => 'Pump Assembly quoted line',
            'source_quantity' => 4,
            'source_uom' => 'EA',
            'source_unit_price' => 11.50,
            'raw_data' => [
                'provider_provenance' => [
                    'origin' => 'provider',
                    'provider_name' => 'openrouter',
                    'captured_at' => '2026-04-26T08:00:00+00:00',
                    'suggested_values' => [
                        'rfq_line_item_id' => $lineItem->id,
                        'quantity' => '5.0000',
                        'uom' => 'BOX',
                        'unit_price' => '12.3400',
                    ],
                ],
                'override' => [
                    'quantity' => '4',
                    'uom' => 'EA',
                    'unit_price' => '11.50',
                ],
                'override_audit' => [
                    'actor_user_id' => $user->id,
                    'timestamp' => '2026-04-26T09:30:00+00:00',
                    'reason_code' => 'price_correction',
                    'note' => 'Matched supplier appendix',
                    'provider_confidence' => '91.00',
                    'before' => [
                        'rfq_line_item_id' => $lineItem->id,
                        'quantity' => '5.0000',
                        'uom' => 'BOX',
                        'unit_price' => '12.3400',
                    ],
                    'after' => [
                        'rfq_line_item_id' => $lineItem->id,
                        'quantity' => '4.0000',
                        'uom' => 'EA',
                        'unit_price' => '11.5000',
                    ],
                    'provider_suggested' => [
                        'rfq_line_item_id' => $lineItem->id,
                        'quantity' => '5.0000',
                        'uom' => 'BOX',
                        'unit_price' => '12.3400',
                    ],
                ],
                'override_history' => [[
                    'actor_user_id' => $user->id,
                    'timestamp' => '2026-04-26T09:30:00+00:00',
                    'reason_code' => 'price_correction',
                ]],
            ],
            'sort_order' => 0,
            'ai_confidence' => 91.0,
            'taxonomy_code' => 'PUMP.ASM',
            'mapping_version' => 'provider-v1',
        ]);

        $response = $this->getJson(
            '/api/v1/normalization/' . $rfq->id . '/source-lines',
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('data.0.provider_suggested.rfq_line_item_id', $lineItem->id);
        $response->assertJsonPath('data.0.provider_suggested.quantity', '5.0000');
        $response->assertJsonPath('data.0.provider_suggested.uom', 'BOX');
        $response->assertJsonPath('data.0.provider_suggested.unit_price', '12.3400');
        $response->assertJsonPath('data.0.effective_values.rfq_line_item_id', $lineItem->id);
        $response->assertJsonPath('data.0.effective_values.quantity', '4.0000');
        $response->assertJsonPath('data.0.effective_values.uom', 'EA');
        $response->assertJsonPath('data.0.effective_values.unit_price', '11.5000');
        $response->assertJsonPath('data.0.is_buyer_overridden', true);
        $response->assertJsonPath('data.0.latest_override.actor_user_id', $user->id);
        $response->assertJsonPath('data.0.latest_override.timestamp', '2026-04-26T09:30:00+00:00');
        $response->assertJsonPath('data.0.latest_override.reason_code', 'price_correction');
        $response->assertJsonPath('data.0.latest_override.note', 'Matched supplier appendix');
        $response->assertJsonPath('data.0.latest_override.provider_confidence', '91.00');
        $response->assertJsonPath('data.0.latest_override.before.unit_price', '12.3400');
        $response->assertJsonPath('data.0.latest_override.after.unit_price', '11.5000');
        $response->assertJsonPath('data.0.provider_provenance.suggested_values.unit_price', '12.3400');
    }

    public function test_override_writes_audited_metadata_records_decision_trail_and_recalculates_readiness(): void
    {
        $user = $this->createUser();
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-NORM-5',
            'title' => 'Normalization RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'published',
        ]);

        $lineItem = RfqLineItem::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'description' => 'Control Valve',
            'quantity' => 1,
            'uom' => 'EA',
            'unit_price' => 12.34,
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
            'confidence' => 88.25,
            'line_items_count' => 1,
            'warnings_count' => 0,
            'errors_count' => 1,
        ]);

        $sourceLine = NormalizationSourceLine::query()->create([
            'tenant_id' => $user->tenant_id,
            'quote_submission_id' => $quote->id,
            'rfq_line_item_id' => $lineItem->id,
            'source_description' => 'Control Valve quoted line',
            'source_quantity' => 1,
            'source_uom' => 'EA',
            'source_unit_price' => null,
            'raw_data' => [
                'provider_provenance' => [
                    'origin' => 'provider',
                    'suggested_values' => [
                        'rfq_line_item_id' => $lineItem->id,
                        'quantity' => '1.0000',
                        'uom' => 'EA',
                        'unit_price' => '12.3400',
                    ],
                ],
            ],
            'sort_order' => 0,
            'ai_confidence' => 88.25,
            'taxonomy_code' => 'VALVE.CTRL',
            'mapping_version' => 'provider-v1',
        ]);

        $response = $this->putJson(
            '/api/v1/normalization/source-lines/' . $sourceLine->id . '/override',
            [
                'override_data' => [
                    'unit_price' => '12.34',
                ],
                'reason_code' => 'price_correction',
                'note' => 'Matched signed quotation',
            ],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('data.id', $sourceLine->id);
        $response->assertJsonPath('data.is_buyer_overridden', true);
        $response->assertJsonPath('data.effective_values.unit_price', '12.3400');
        $response->assertJsonPath('data.latest_override.actor_user_id', $user->id);
        $response->assertJsonPath('data.latest_override.reason_code', 'price_correction');
        $response->assertJsonPath('data.latest_override.note', 'Matched signed quotation');
        $response->assertJsonPath('data.latest_override.provider_confidence', '88.25');
        $response->assertJsonPath('data.latest_override.before.unit_price', null);
        $response->assertJsonPath('data.latest_override.after.unit_price', '12.3400');
        $response->assertJsonPath('meta.next_status', 'ready');
        $response->assertJsonPath('meta.has_blocking_issues', false);

        $quote->refresh();
        self::assertSame('ready', $quote->status);

        $sourceLine->refresh();
        self::assertSame('12.3400', $sourceLine->source_unit_price);
        self::assertIsArray($sourceLine->raw_data);
        self::assertSame('12.3400', $sourceLine->raw_data['override']['unit_price']);
        self::assertSame('price_correction', $sourceLine->raw_data['override_audit']['reason_code']);
        self::assertSame($user->id, $sourceLine->raw_data['override_audit']['actor_user_id']);
        self::assertSame('88.25', $sourceLine->raw_data['override_audit']['provider_confidence']);
        self::assertSame('12.3400', $sourceLine->raw_data['provider_provenance']['suggested_values']['unit_price']);
        self::assertCount(1, $sourceLine->raw_data['override_history']);
        self::assertSame('price_correction', $sourceLine->raw_data['override_history'][0]['reason_code']);

        $entry = DecisionTrailEntry::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('comparison_run_id', $quote->id)
            ->where('event_type', 'normalization_source_line_overridden')
            ->first();

        self::assertNotNull($entry);
        self::assertSame($sourceLine->id, $entry->summary_payload['source_line_id']);
        self::assertSame($user->id, $entry->summary_payload['actor_user_id']);
        self::assertSame('price_correction', $entry->summary_payload['reason_code']);
        self::assertSame('88.25', $entry->summary_payload['provider_confidence']);
        self::assertSame(['unit_price' => null], $entry->summary_payload['before']);
        self::assertSame(['unit_price' => '12.3400'], $entry->summary_payload['after']);
    }

    public function test_manual_override_does_not_fabricate_provider_provenance_and_records_actor_name(): void
    {
        $user = $this->createUser();
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-NORM-5A',
            'title' => 'Normalization RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'published',
        ]);

        $lineItem = RfqLineItem::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'description' => 'Manual Valve',
            'quantity' => 1,
            'uom' => 'EA',
            'unit_price' => 9.99,
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
            'errors_count' => 1,
        ]);

        $sourceLine = NormalizationSourceLine::query()->create([
            'tenant_id' => $user->tenant_id,
            'quote_submission_id' => $quote->id,
            'rfq_line_item_id' => $lineItem->id,
            'source_description' => 'Manual valve quoted line',
            'source_quantity' => 1,
            'source_uom' => 'EA',
            'source_unit_price' => 9.99,
            'raw_data' => [
                'provenance' => [
                    'origin' => 'manual',
                    'user_id' => $user->id,
                    'timestamp' => '2026-04-26T08:00:00+00:00',
                ],
            ],
            'sort_order' => 0,
            'ai_confidence' => null,
            'taxonomy_code' => null,
            'mapping_version' => null,
        ]);

        $response = $this->putJson(
            '/api/v1/normalization/source-lines/' . $sourceLine->id . '/override',
            [
                'override_data' => [
                    'unit_price' => '8.99',
                ],
                'reason_code' => 'manual_entry_required',
                'note' => 'Buyer entered the final value directly',
            ],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('data.provider_provenance', null);
        $response->assertJsonPath('data.provider_suggested', null);
        $response->assertJsonPath('data.latest_override.actor_name', $user->name);
        $response->assertJsonPath('data.latest_override.provider_confidence', null);

        $sourceLine->refresh();
        self::assertSame('manual', $sourceLine->raw_data['provenance']['origin']);
        self::assertArrayNotHasKey('provider_provenance', $sourceLine->raw_data);
        self::assertSame($user->name, $sourceLine->raw_data['override_audit']['actor_name']);
        self::assertNull($sourceLine->raw_data['override_audit']['provider_confidence']);

        $entry = DecisionTrailEntry::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('comparison_run_id', $quote->id)
            ->where('event_type', 'normalization_source_line_overridden')
            ->first();

        self::assertNotNull($entry);
        self::assertSame($user->name, $entry->summary_payload['actor_name']);
        self::assertNull($entry->summary_payload['provider_suggested']);
    }

    public function test_override_requires_note_when_reason_is_other(): void
    {
        $user = $this->createUser();
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-NORM-6',
            'title' => 'Normalization RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'published',
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
            'errors_count' => 1,
        ]);

        $sourceLine = NormalizationSourceLine::query()->create([
            'tenant_id' => $user->tenant_id,
            'quote_submission_id' => $quote->id,
            'rfq_line_item_id' => null,
            'source_description' => 'Unmapped line',
            'source_quantity' => 1,
            'source_uom' => 'EA',
            'source_unit_price' => null,
            'sort_order' => 0,
        ]);

        $response = $this->putJson(
            '/api/v1/normalization/source-lines/' . $sourceLine->id . '/override',
            [
                'override_data' => [
                    'unit_price' => '9.99',
                ],
                'reason_code' => 'other',
            ],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertStatus(422);
        $response->assertJsonPath('details.note.0', 'The note field is required when reason code is other.');
    }

    public function test_override_allows_clearing_fields_updates_description_and_keeps_price_precision(): void
    {
        $user = $this->createUser();
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-NORM-7',
            'title' => 'Normalization RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'published',
        ]);

        $lineItem = RfqLineItem::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'description' => 'Precision Valve',
            'quantity' => 1,
            'uom' => 'EA',
            'unit_price' => 12.3456,
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
            'confidence' => 88.25,
            'line_items_count' => 1,
            'warnings_count' => 0,
            'errors_count' => 1,
        ]);

        $sourceLine = NormalizationSourceLine::query()->create([
            'tenant_id' => $user->tenant_id,
            'quote_submission_id' => $quote->id,
            'rfq_line_item_id' => $lineItem->id,
            'source_description' => 'Old description',
            'source_quantity' => 1,
            'source_uom' => 'EA',
            'source_unit_price' => 12.3456,
            'raw_data' => [
                'provider_provenance' => [
                    'origin' => 'provider',
                    'captured_at' => '2026-04-26T08:00:00+00:00',
                    'suggested_values' => [
                        'rfq_line_item_id' => $lineItem->id,
                        'quantity' => '1.0000',
                        'uom' => 'EA',
                        'unit_price' => '12.3456',
                    ],
                ],
            ],
            'sort_order' => 0,
            'ai_confidence' => 88.25,
            'taxonomy_code' => 'VALVE.PRECISION',
            'mapping_version' => 'provider-v1',
        ]);

        $response = $this->putJson(
            '/api/v1/normalization/source-lines/' . $sourceLine->id . '/override',
            [
                'override_data' => [
                    'source_description' => 'Corrected precision valve',
                    'rfq_line_item_id' => null,
                    'quantity' => null,
                    'uom' => null,
                    'unit_price' => '12.34567',
                ],
                'reason_code' => 'price_correction',
                'note' => 'Buyer corrected the extracted line',
            ],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('data.source_description', 'Corrected precision valve');
        $response->assertJsonPath('data.rfq_line_item_id', null);
        $response->assertJsonPath('data.effective_values.source_description', 'Corrected precision valve');
        $response->assertJsonPath('data.effective_values.rfq_line_item_id', null);
        $response->assertJsonPath('data.effective_values.quantity', null);
        $response->assertJsonPath('data.effective_values.uom', null);
        $response->assertJsonPath('data.effective_values.unit_price', '12.3457');
        $response->assertJsonPath('data.latest_override.after.unit_price', '12.3457');
        $response->assertJsonPath('data.latest_override.after.quantity', null);
        $response->assertJsonPath('data.latest_override.after.uom', null);

        $sourceLine->refresh();
        self::assertSame('Corrected precision valve', $sourceLine->source_description);
        self::assertNull($sourceLine->rfq_line_item_id);
        self::assertNull($sourceLine->source_quantity);
        self::assertNull($sourceLine->source_uom);
        self::assertSame('12.3457', $sourceLine->source_unit_price);
        self::assertSame('12.3456', $sourceLine->raw_data['provider_provenance']['suggested_values']['unit_price']);
    }
}

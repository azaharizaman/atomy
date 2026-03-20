<?php

declare(strict_types=1);

namespace Tests\Feature;

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
}

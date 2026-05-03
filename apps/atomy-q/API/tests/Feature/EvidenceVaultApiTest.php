<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Approval;
use App\Models\Award;
use App\Models\ComparisonRun;
use App\Models\DecisionTrailEntry;
use App\Models\EvidenceBundle;
use App\Models\EvidenceBundleItem;
use App\Models\NormalizationConflict;
use App\Models\NormalizationSourceLine;
use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Models\RfqLineItem;
use App\Models\SupportingEvidence;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use Tests\Feature\Api\ApiTestCase;

final class EvidenceVaultApiTest extends ApiTestCase
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

    public function testGenericDocumentsRoutesAreRemoved(): void
    {
        [$user] = $this->seedUserAndRfq();

        $this->getJson('/api/v1/documents', $this->authHeaders((string) $user->tenant_id, (string) $user->id))
            ->assertNotFound();
    }

    public function testGenericEvidenceBundleRoutesAreRemoved(): void
    {
        [$user] = $this->seedUserAndRfq();

        $this->getJson('/api/v1/evidence-bundles', $this->authHeaders((string) $user->tenant_id, (string) $user->id))
            ->assertNotFound();
    }

    public function testEvidenceVaultSummaryEndpointIsRfqScoped(): void
    {
        [$user, $rfq] = $this->seedUserAndRfq();

        $this->getJson(
            '/api/v1/rfqs/' . $rfq->id . '/evidence-vault',
            $this->authHeaders((string) $user->tenant_id, (string) $user->id)
        )
            ->assertOk()
            ->assertJsonPath('data.rfq.id', $rfq->id)
            ->assertJsonPath('data.award_pack.status', 'not_ready');
    }

    public function testEvidenceVaultSummaryEndpointDoesNotLeakAcrossTenants(): void
    {
        [$user] = $this->seedUserAndRfq();
        [, $otherTenantRfq] = $this->seedUserAndRfq();

        $this->getJson(
            '/api/v1/rfqs/' . $otherTenantRfq->id . '/evidence-vault',
            $this->authHeaders((string) $user->tenant_id, (string) $user->id)
        )->assertNotFound();
    }

    public function testEvidenceVaultSummaryReportsCompleteEvidenceSetAsDraftReady(): void
    {
        [$user, $rfq] = $this->seedUserAndRfq();

        QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_name' => 'Complete Supplier',
            'uploaded_by' => $user->id,
            'file_path' => 'quotes/complete-supplier.pdf',
            'file_type' => 'application/pdf',
            'original_filename' => 'complete-supplier.pdf',
            'status' => 'ready',
            'submitted_at' => now(),
            'parsed_at' => now(),
            'line_items_count' => 2,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        $comparisonRun = ComparisonRun::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'name' => 'Final comparison',
            'is_preview' => false,
            'created_by' => $user->id,
            'request_payload' => [],
            'matrix_payload' => ['rows' => 1],
            'scoring_payload' => ['winner' => 'Complete Supplier'],
            'approval_payload' => [],
            'response_payload' => [],
            'readiness_payload' => [],
            'status' => 'final',
            'version' => 1,
        ]);

        $approval = Approval::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'comparison_run_id' => $comparisonRun->id,
            'type' => 'award_approval',
            'status' => 'approved',
            'requested_by' => $user->id,
            'requested_at' => now(),
            'amount' => 12345.67,
            'currency' => 'USD',
            'level' => 1,
            'approved_at' => now(),
            'approved_by' => $user->id,
        ]);

        $vendor = Vendor::query()->create([
            'tenant_id' => $user->tenant_id,
            'legal_name' => 'Complete Supplier LLC',
            'display_name' => 'Complete Supplier',
            'country_of_registration' => 'US',
            'primary_contact_name' => 'Complete Contact',
            'primary_contact_email' => 'complete-supplier@example.com',
            'status' => 'approved',
        ]);

        Award::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'comparison_run_id' => $comparisonRun->id,
            'vendor_id' => $vendor->id,
            'status' => 'signed_off',
            'amount' => 12345.67,
            'currency' => 'USD',
            'split_details' => [],
            'signoff_at' => now(),
            'signed_off_by' => $user->id,
        ]);

        foreach (['quote_sources', 'final_comparison', 'approval_trail', 'award_signoff'] as $index => $eventType) {
            DecisionTrailEntry::query()->create([
                'tenant_id' => $user->tenant_id,
                'comparison_run_id' => $comparisonRun->id,
                'rfq_id' => $rfq->id,
                'sequence' => $index + 1,
                'event_type' => $eventType,
                'summary_payload' => ['code' => $eventType],
                'payload_hash' => hash('sha256', $eventType . '-payload'),
                'previous_hash' => $index === 0 ? str_repeat('0', 64) : hash('sha256', ((string) $index) . '-entry'),
                'entry_hash' => hash('sha256', $eventType . '-entry'),
                'occurred_at' => now()->addSeconds($index),
            ]);
        }

        EvidenceBundle::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'comparison_run_id' => $comparisonRun->id,
            'approval_id' => $approval->id,
            'type' => 'award_justification',
            'status' => 'draft',
            'version' => 2,
            'manifest' => ['rfq_id' => $rfq->id],
            'checksum' => null,
            'created_by' => $user->id,
        ]);

        $this->getJson(
            '/api/v1/rfqs/' . $rfq->id . '/evidence-vault',
            $this->authHeaders((string) $user->tenant_id, (string) $user->id)
        )
            ->assertOk()
            ->assertJsonPath('data.award_pack.status', 'draft_ready')
            ->assertJsonPath('data.readiness.ready', true)
            ->assertJsonPath('data.actions.can_finalize', true)
            ->assertJsonFragment(['code' => 'quote_sources'])
            ->assertJsonFragment(['code' => 'normalization_review'])
            ->assertJsonFragment(['code' => 'final_comparison'])
            ->assertJsonFragment(['code' => 'supporting_evidence'])
            ->assertJsonFragment(['code' => 'approval_trail'])
            ->assertJsonFragment(['code' => 'award_signoff']);
    }

    public function testEvidenceVaultSummaryBlocksReadyQuoteWithoutSourceFile(): void
    {
        [$user, $rfq] = $this->seedUserAndRfq();
        $this->completeEvidenceRfq($user, $rfq, quoteFilePath: null);

        $this->getJson(
            '/api/v1/rfqs/' . $rfq->id . '/evidence-vault',
            $this->authHeaders((string) $user->tenant_id, (string) $user->id)
        )
            ->assertOk()
            ->assertJsonPath('data.readiness.ready', false)
            ->assertJsonFragment(['code' => 'QUOTE_SOURCE_FILE_MISSING']);
    }

    public function testEvidenceVaultSummaryUsesLatestAwardForSignoffReadiness(): void
    {
        [$user, $rfq] = $this->seedUserAndRfq();

        QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_name' => 'Latest Pending Supplier',
            'uploaded_by' => $user->id,
            'file_path' => 'quotes/latest-pending-supplier.pdf',
            'file_type' => 'application/pdf',
            'original_filename' => 'latest-pending-supplier.pdf',
            'status' => 'ready',
            'submitted_at' => now(),
            'parsed_at' => now(),
            'line_items_count' => 2,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        $comparisonRun = ComparisonRun::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'name' => 'Final comparison with superseded award',
            'is_preview' => false,
            'created_by' => $user->id,
            'request_payload' => [],
            'matrix_payload' => ['rows' => 1],
            'scoring_payload' => ['winner' => 'Latest Pending Supplier'],
            'approval_payload' => [],
            'response_payload' => [],
            'readiness_payload' => [],
            'status' => 'frozen',
            'version' => 1,
        ]);

        $approval = Approval::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'comparison_run_id' => $comparisonRun->id,
            'type' => 'award_approval',
            'status' => 'approved',
            'requested_by' => $user->id,
            'requested_at' => now(),
            'amount' => 12345.67,
            'currency' => 'USD',
            'level' => 1,
            'approved_at' => now(),
            'approved_by' => $user->id,
        ]);

        $vendor = Vendor::query()->create([
            'tenant_id' => $user->tenant_id,
            'legal_name' => 'Latest Pending Supplier LLC',
            'display_name' => 'Latest Pending Supplier',
            'country_of_registration' => 'US',
            'primary_contact_name' => 'Latest Pending Contact',
            'primary_contact_email' => 'latest-pending-supplier@example.com',
            'status' => 'approved',
        ]);

        $signedOffAward = Award::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'comparison_run_id' => $comparisonRun->id,
            'vendor_id' => $vendor->id,
            'status' => 'signed_off',
            'amount' => 12345.67,
            'currency' => 'USD',
            'split_details' => [],
            'signoff_at' => now()->subMinutes(10),
            'signed_off_by' => $user->id,
        ]);
        $signedOffAward->forceFill(['created_at' => now()->subMinutes(10)])->save();

        $pendingAward = Award::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'comparison_run_id' => $comparisonRun->id,
            'vendor_id' => $vendor->id,
            'status' => 'pending',
            'amount' => 12345.67,
            'currency' => 'USD',
            'split_details' => [],
            'signoff_at' => null,
            'signed_off_by' => null,
        ]);
        $pendingAward->forceFill(['created_at' => now()->addMinute()])->save();

        foreach (['quote_sources', 'final_comparison', 'approval_trail', 'award_signoff'] as $index => $eventType) {
            DecisionTrailEntry::query()->create([
                'tenant_id' => $user->tenant_id,
                'comparison_run_id' => $comparisonRun->id,
                'rfq_id' => $rfq->id,
                'sequence' => $index + 1,
                'event_type' => $eventType,
                'summary_payload' => ['code' => $eventType],
                'payload_hash' => hash('sha256', $eventType . '-payload'),
                'previous_hash' => $index === 0 ? str_repeat('0', 64) : hash('sha256', ((string) $index) . '-entry'),
                'entry_hash' => hash('sha256', $eventType . '-entry'),
                'occurred_at' => now()->addSeconds($index),
            ]);
        }

        EvidenceBundle::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'comparison_run_id' => $comparisonRun->id,
            'approval_id' => $approval->id,
            'type' => 'award_justification',
            'status' => 'draft',
            'version' => 2,
            'manifest' => ['rfq_id' => $rfq->id],
            'checksum' => null,
            'created_by' => $user->id,
        ]);

        $this->getJson(
            '/api/v1/rfqs/' . $rfq->id . '/evidence-vault',
            $this->authHeaders((string) $user->tenant_id, (string) $user->id)
        )
            ->assertOk()
            ->assertJsonPath('data.readiness.ready', false)
            ->assertJsonFragment(['code' => 'AWARD_SIGNOFF_MISSING']);
    }

    public function testEvidenceVaultSummaryBlocksWhenNormalizationConflictIsUnresolved(): void
    {
        [$user, $rfq] = $this->seedUserAndRfq();

        $quote = QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_name' => 'Conflict Supplier',
            'uploaded_by' => $user->id,
            'file_path' => 'quotes/conflict-supplier.pdf',
            'file_type' => 'application/pdf',
            'original_filename' => 'conflict-supplier.pdf',
            'status' => 'ready',
            'submitted_at' => now(),
            'parsed_at' => now(),
            'line_items_count' => 2,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        $lineItem = RfqLineItem::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'description' => 'Conflict line',
            'quantity' => 1,
            'uom' => 'ea',
            'unit_price' => 10,
            'currency' => 'USD',
            'sort_order' => 0,
        ]);

        $sourceLine = NormalizationSourceLine::query()->create([
            'tenant_id' => $user->tenant_id,
            'quote_submission_id' => $quote->id,
            'rfq_line_item_id' => $lineItem->id,
            'source_description' => 'Conflict line',
            'source_unit_price' => 10,
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

        $comparisonRun = ComparisonRun::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'name' => 'Final comparison with conflict',
            'is_preview' => false,
            'created_by' => $user->id,
            'request_payload' => [],
            'matrix_payload' => ['rows' => 1],
            'scoring_payload' => ['winner' => 'Conflict Supplier'],
            'approval_payload' => [],
            'response_payload' => [],
            'readiness_payload' => [],
            'status' => 'frozen',
            'version' => 1,
        ]);

        $approval = Approval::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'comparison_run_id' => $comparisonRun->id,
            'type' => 'award_approval',
            'status' => 'approved',
            'requested_by' => $user->id,
            'requested_at' => now(),
            'amount' => 12345.67,
            'currency' => 'USD',
            'level' => 1,
            'approved_at' => now(),
            'approved_by' => $user->id,
        ]);

        $vendor = Vendor::query()->create([
            'tenant_id' => $user->tenant_id,
            'legal_name' => 'Conflict Supplier LLC',
            'display_name' => 'Conflict Supplier',
            'country_of_registration' => 'US',
            'primary_contact_name' => 'Conflict Contact',
            'primary_contact_email' => 'conflict-supplier@example.com',
            'status' => 'approved',
        ]);

        Award::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'comparison_run_id' => $comparisonRun->id,
            'vendor_id' => $vendor->id,
            'status' => 'signed_off',
            'amount' => 12345.67,
            'currency' => 'USD',
            'split_details' => [],
            'signoff_at' => now(),
            'signed_off_by' => $user->id,
        ]);

        foreach (['quote_sources', 'final_comparison', 'approval_trail', 'award_signoff'] as $index => $eventType) {
            DecisionTrailEntry::query()->create([
                'tenant_id' => $user->tenant_id,
                'comparison_run_id' => $comparisonRun->id,
                'rfq_id' => $rfq->id,
                'sequence' => $index + 1,
                'event_type' => $eventType,
                'summary_payload' => ['code' => $eventType],
                'payload_hash' => hash('sha256', $eventType . '-payload'),
                'previous_hash' => $index === 0 ? str_repeat('0', 64) : hash('sha256', ((string) $index) . '-entry'),
                'entry_hash' => hash('sha256', $eventType . '-entry'),
                'occurred_at' => now()->addSeconds($index),
            ]);
        }

        EvidenceBundle::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'comparison_run_id' => $comparisonRun->id,
            'approval_id' => $approval->id,
            'type' => 'award_justification',
            'status' => 'draft',
            'version' => 2,
            'manifest' => ['rfq_id' => $rfq->id],
            'checksum' => null,
            'created_by' => $user->id,
        ]);

        $this->getJson(
            '/api/v1/rfqs/' . $rfq->id . '/evidence-vault',
            $this->authHeaders((string) $user->tenant_id, (string) $user->id)
        )
            ->assertOk()
            ->assertJsonPath('data.readiness.ready', false)
            ->assertJsonFragment(['code' => 'NORMALIZATION_CONFLICT_UNRESOLVED']);
    }

    public function testEvidenceVaultSummaryBlocksWhenRequiredDecisionTrailEventIsMissing(): void
    {
        [$user, $rfq] = $this->seedUserAndRfq();

        QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_name' => 'Missing Trail Supplier',
            'uploaded_by' => $user->id,
            'file_path' => 'quotes/missing-trail-supplier.pdf',
            'file_type' => 'application/pdf',
            'original_filename' => 'missing-trail-supplier.pdf',
            'status' => 'ready',
            'submitted_at' => now(),
            'parsed_at' => now(),
            'line_items_count' => 2,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        $comparisonRun = ComparisonRun::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'name' => 'Final comparison with incomplete trail',
            'is_preview' => false,
            'created_by' => $user->id,
            'request_payload' => [],
            'matrix_payload' => ['rows' => 1],
            'scoring_payload' => ['winner' => 'Missing Trail Supplier'],
            'approval_payload' => [],
            'response_payload' => [],
            'readiness_payload' => [],
            'status' => 'frozen',
            'version' => 1,
        ]);

        $approval = Approval::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'comparison_run_id' => $comparisonRun->id,
            'type' => 'award_approval',
            'status' => 'approved',
            'requested_by' => $user->id,
            'requested_at' => now(),
            'amount' => 12345.67,
            'currency' => 'USD',
            'level' => 1,
            'approved_at' => now(),
            'approved_by' => $user->id,
        ]);

        $vendor = Vendor::query()->create([
            'tenant_id' => $user->tenant_id,
            'legal_name' => 'Missing Trail Supplier LLC',
            'display_name' => 'Missing Trail Supplier',
            'country_of_registration' => 'US',
            'primary_contact_name' => 'Missing Trail Contact',
            'primary_contact_email' => 'missing-trail-supplier@example.com',
            'status' => 'approved',
        ]);

        Award::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'comparison_run_id' => $comparisonRun->id,
            'vendor_id' => $vendor->id,
            'status' => 'signed_off',
            'amount' => 12345.67,
            'currency' => 'USD',
            'split_details' => [],
            'signoff_at' => now(),
            'signed_off_by' => $user->id,
        ]);

        foreach (['quote_sources', 'final_comparison', 'award_signoff'] as $index => $eventType) {
            DecisionTrailEntry::query()->create([
                'tenant_id' => $user->tenant_id,
                'comparison_run_id' => $comparisonRun->id,
                'rfq_id' => $rfq->id,
                'sequence' => $index + 1,
                'event_type' => $eventType,
                'summary_payload' => ['code' => $eventType],
                'payload_hash' => hash('sha256', $eventType . '-payload'),
                'previous_hash' => $index === 0 ? str_repeat('0', 64) : hash('sha256', ((string) $index) . '-entry'),
                'entry_hash' => hash('sha256', $eventType . '-entry'),
                'occurred_at' => now()->addSeconds($index),
            ]);
        }

        EvidenceBundle::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'comparison_run_id' => $comparisonRun->id,
            'approval_id' => $approval->id,
            'type' => 'award_justification',
            'status' => 'draft',
            'version' => 2,
            'manifest' => ['rfq_id' => $rfq->id],
            'checksum' => null,
            'created_by' => $user->id,
        ]);

        $this->getJson(
            '/api/v1/rfqs/' . $rfq->id . '/evidence-vault',
            $this->authHeaders((string) $user->tenant_id, (string) $user->id)
        )
            ->assertOk()
            ->assertJsonPath('data.readiness.ready', false)
            ->assertJsonFragment(['code' => 'DECISION_TRAIL_INCOMPLETE']);
    }

    public function testEvidenceVaultSummaryReportsReadinessBlockers(): void
    {
        [$user, $rfq] = $this->seedUserAndRfq();

        QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_name' => 'Blocked Supplier',
            'uploaded_by' => $user->id,
            'file_path' => 'quotes/blocked-supplier.pdf',
            'file_type' => 'application/pdf',
            'original_filename' => 'blocked-supplier.pdf',
            'status' => 'ready',
            'submitted_at' => now(),
            'parsed_at' => now(),
            'line_items_count' => 2,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        $comparisonRun = ComparisonRun::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'name' => 'Preview comparison only',
            'is_preview' => true,
            'created_by' => $user->id,
            'request_payload' => [],
            'matrix_payload' => [],
            'scoring_payload' => [],
            'approval_payload' => [],
            'response_payload' => [],
            'readiness_payload' => [],
            'status' => 'draft',
            'version' => 1,
        ]);

        $vendor = Vendor::query()->create([
            'tenant_id' => $user->tenant_id,
            'legal_name' => 'Blocked Supplier LLC',
            'display_name' => 'Blocked Supplier',
            'country_of_registration' => 'US',
            'primary_contact_name' => 'Blocked Contact',
            'primary_contact_email' => 'blocked-supplier@example.com',
            'status' => 'approved',
        ]);

        Award::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'comparison_run_id' => $comparisonRun->id,
            'vendor_id' => $vendor->id,
            'status' => 'pending',
            'amount' => 12345.67,
            'currency' => 'USD',
            'split_details' => [],
            'signoff_at' => null,
            'signed_off_by' => null,
        ]);

        $this->getJson(
            '/api/v1/rfqs/' . $rfq->id . '/evidence-vault',
            $this->authHeaders((string) $user->tenant_id, (string) $user->id)
        )
            ->assertOk()
            ->assertJsonPath('data.award_pack.status', 'not_ready')
            ->assertJsonPath('data.readiness.ready', false)
            ->assertJsonFragment(['code' => 'FINAL_COMPARISON_MISSING'])
            ->assertJsonFragment(['code' => 'APPROVAL_DECISION_MISSING'])
            ->assertJsonFragment(['code' => 'AWARD_SIGNOFF_MISSING']);
    }

    public function testAwardPackFinalizationRejectsNotReadyRfq(): void
    {
        [$user, $rfq] = $this->seedUserAndRfq();

        $response = $this->postJson(
            '/api/v1/rfqs/' . $rfq->id . '/evidence-vault/award-pack/finalize',
            [],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id)
        );

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Evidence pack is not ready for finalization.');
        $response->assertJsonFragment(['code' => 'FINAL_COMPARISON_MISSING']);
    }

    public function testAwardPackFinalizationCreatesImmutableManifest(): void
    {
        [$user, $rfq] = $this->seedUserAndRfq();
        $this->completeEvidenceRfq($user, $rfq);

        $response = $this->postJson(
            '/api/v1/rfqs/' . $rfq->id . '/evidence-vault/award-pack/finalize',
            [],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id)
        );

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'finalized');
        $response->assertJsonPath('data.type', 'award_justification');
        $response->assertJsonPath('data.version', 1);
        $response->assertJsonPath('data.manifest.rfq.id', (string) $rfq->id);
        $response->assertJsonPath('data.manifest.rfq.title', 'Evidence RFQ');
        $response->assertJsonPath('data.manifest.bundle.status', 'finalized');
        $response->assertJsonPath('data.manifest.bundle.version', 1);
        $response->assertJsonPath('data.manifest.summary.award_pack.status', 'finalized');
        $response->assertJsonPath('data.manifest.summary.award_pack.version', 1);
        $response->assertJsonPath('data.manifest.summary.actions.can_finalize', false);
        $response->assertJsonPath('data.manifest.summary.actions.can_export', true);
        $response->assertJsonPath('data.manifest.summary.sections.1.code', 'normalization_review');
        $response->assertJsonPath('data.manifest.summary.sections.3.code', 'supporting_evidence');

        $bundleId = (string) $response->json('data.id');
        $checksum = (string) $response->json('data.checksum');

        $this->assertDatabaseHas('evidence_bundles', [
            'id' => $bundleId,
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'type' => 'award_justification',
            'status' => 'finalized',
            'version' => 1,
            'checksum' => $checksum,
        ]);
        $this->assertDatabaseHas('decision_trail_entries', [
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'event_type' => 'evidence_pack_finalized',
        ]);

        $bundle = EvidenceBundle::query()->findOrFail($bundleId);
        self::assertNotNull($bundle->finalized_at);
        self::assertSame($checksum, hash('sha256', json_encode($bundle->manifest, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)));
        self::assertSame(6, $bundle->items()->count());
        self::assertSame(
            ['approval_trail', 'award_signoff', 'final_comparison', 'normalization_review', 'quote_sources', 'supporting_evidence'],
            $bundle->items()->orderBy('artifact_kind')->pluck('artifact_kind')->all(),
        );
        self::assertSame($bundleId, $bundle->manifest['summary']['award_pack']['bundle_id'] ?? null);

        $firstManifest = $bundle->manifest;

        $secondResponse = $this->postJson(
            '/api/v1/rfqs/' . $rfq->id . '/evidence-vault/award-pack/finalize',
            [],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id)
        );

        $secondResponse->assertCreated();
        $secondResponse->assertJsonPath('data.version', 2);

        $bundle->refresh();
        self::assertSame('superseded', $bundle->status);
        self::assertSame($firstManifest, $bundle->manifest);
        self::assertSame([1, 2], EvidenceBundle::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('rfq_id', $rfq->id)
            ->where('type', 'award_justification')
            ->orderBy('version')
            ->pluck('version')
            ->all());
        self::assertSame(1, EvidenceBundle::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('rfq_id', $rfq->id)
            ->where('type', 'award_justification')
            ->where('status', 'finalized')
            ->count());
        $this->assertDatabaseHas('evidence_bundles', [
            'id' => (string) $secondResponse->json('data.id'),
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'status' => 'finalized',
            'version' => 2,
        ]);
    }

    public function testAwardPackExportReturnsLatestFinalizedManifest(): void
    {
        [$user, $rfq] = $this->seedUserAndRfq();
        $this->completeEvidenceRfq($user, $rfq);

        $this->postJson(
            '/api/v1/rfqs/' . $rfq->id . '/evidence-vault/award-pack/finalize',
            [],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id)
        )->assertCreated();

        $response = $this->getJson(
            '/api/v1/rfqs/' . $rfq->id . '/evidence-vault/award-pack/export',
            $this->authHeaders((string) $user->tenant_id, (string) $user->id)
        );

        $bundle = EvidenceBundle::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('rfq_id', $rfq->id)
            ->where('status', 'finalized')
            ->firstOrFail();

        $response->assertOk();
        $response->assertJsonPath('data.bundle_id', (string) $bundle->id);
        $response->assertJsonPath('data.checksum', $bundle->checksum);
        $response->assertJsonPath('data.manifest.rfq.id', (string) $rfq->id);
        $response->assertJsonPath('data.manifest.bundle.version', 1);
        $response->assertJsonPath('data.manifest.bundle.id', (string) $bundle->id);
        $response->assertJsonPath('data.manifest.bundle.status', 'finalized');
        $response->assertJsonPath('data.manifest.bundle.checksum_algorithm', 'sha256');
        $response->assertJsonPath('data.manifest.summary.award_pack.bundle_id', (string) $bundle->id);
        $response->assertJsonPath('data.manifest.summary.award_pack.status', 'finalized');
        $response->assertJsonPath('data.manifest.summary.actions.can_finalize', false);
        $response->assertJsonPath('data.manifest.summary.actions.can_export', true);
    }

    public function testSupportingEvidenceUploadStoresFileAndMetadata(): void
    {
        Storage::fake('local');

        [$user, $rfq] = $this->seedUserAndRfq();

        $response = $this->withHeaders($this->authHeaders((string) $user->tenant_id, (string) $user->id))
            ->post('/api/v1/rfqs/' . $rfq->id . '/evidence-vault/supporting-evidence', [
                'reason' => 'Buyer clarification',
                'file' => UploadedFile::fake()->create('clarification.pdf', 12, 'application/pdf'),
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.reason', 'Buyer clarification');
        $response->assertJsonPath('data.original_filename', 'clarification.pdf');

        $storagePath = (string) $response->json('data.storage_path');
        self::assertNotSame('', $storagePath);
        self::assertStringStartsWith('supporting-evidence/' . $user->tenant_id . '/' . $rfq->id . '/', $storagePath);
        Storage::disk('local')->assertExists($storagePath);
        $checksum = hash('sha256', Storage::disk('local')->get($storagePath));
        $response->assertJsonPath('data.checksum', $checksum);

        $this->assertDatabaseHas('supporting_evidence', [
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'reason' => 'Buyer clarification',
            'storage_path' => $storagePath,
            'checksum' => $checksum,
        ]);
    }

    public function testSupportingEvidenceUploadRequiresReasonAndFile(): void
    {
        [$user, $rfq] = $this->seedUserAndRfq();

        $response = $this->withHeaders($this->authHeaders((string) $user->tenant_id, (string) $user->id))
            ->post('/api/v1/rfqs/' . $rfq->id . '/evidence-vault/supporting-evidence', [
                'reason' => '',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Validation failed');
        $response->assertJsonPath('details.reason.0', 'The reason field is required.');
        $response->assertJsonPath('details.file.0', 'The file field is required.');
    }

    public function testSupportingEvidenceUploadRejectsUnsupportedFileType(): void
    {
        Storage::fake('local');

        [$user, $rfq] = $this->seedUserAndRfq();

        $response = $this->withHeaders($this->authHeaders((string) $user->tenant_id, (string) $user->id))
            ->post('/api/v1/rfqs/' . $rfq->id . '/evidence-vault/supporting-evidence', [
                'reason' => 'Buyer clarification',
                'file' => UploadedFile::fake()->create('malicious.exe', 12, 'application/x-msdownload'),
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Validation failed');
        $response->assertJsonPath(
            'details.file.0',
            'The file field must be a file of type: pdf, doc, docx, xls, xlsx, png, jpg, jpeg.',
        );
        $this->assertDatabaseCount('supporting_evidence', 0);
    }

    public function testSupportingEvidenceUploadRejectsSameTenantVendorNotSelectedForRfq(): void
    {
        Storage::fake('local');

        [$user, $rfq] = $this->seedUserAndRfq();
        $vendor = Vendor::query()->create([
            'tenant_id' => $user->tenant_id,
            'legal_name' => 'Unselected Supplier LLC',
            'display_name' => 'Unselected Supplier',
            'country_of_registration' => 'US',
            'primary_contact_name' => 'Unselected Contact',
            'primary_contact_email' => 'unselected-supplier@example.com',
            'status' => 'approved',
        ]);

        $response = $this->withHeaders($this->authHeaders((string) $user->tenant_id, (string) $user->id))
            ->post('/api/v1/rfqs/' . $rfq->id . '/evidence-vault/supporting-evidence', [
                'reason' => 'Buyer clarification',
                'vendor_id' => $vendor->id,
                'file' => UploadedFile::fake()->create('clarification.pdf', 12, 'application/pdf'),
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Validation failed');
        $response->assertJsonPath('details.vendor_id.0', 'The selected vendor is invalid for this RFQ.');
        $this->assertDatabaseMissing('supporting_evidence', [
            'rfq_id' => $rfq->id,
            'vendor_id' => $vendor->id,
        ]);
    }

    public function testSupportingEvidenceUploadAcceptsSelectedVendorForRfq(): void
    {
        Storage::fake('local');

        [$user, $rfq] = $this->seedUserAndRfq();
        $vendor = Vendor::query()->create([
            'tenant_id' => $user->tenant_id,
            'legal_name' => 'Selected Supplier LLC',
            'display_name' => 'Selected Supplier',
            'country_of_registration' => 'US',
            'primary_contact_name' => 'Selected Contact',
            'primary_contact_email' => 'selected-supplier@example.com',
            'status' => 'approved',
        ]);
        DB::table('tenants')->insert([
            'id' => $user->tenant_id,
            'code' => 'selected-' . Str::lower((string) Str::ulid()),
            'name' => 'Selected Tenant ' . Str::lower((string) Str::ulid()),
            'email' => 'selected-tenant@example.com',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'storage_used' => 0,
            'onboarding_progress' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('requisition_selected_vendors')->insert([
            'id' => (string) Str::ulid(),
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_id' => $vendor->id,
            'selected_by_user_id' => $user->id,
            'selected_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders((string) $user->tenant_id, (string) $user->id))
            ->post('/api/v1/rfqs/' . $rfq->id . '/evidence-vault/supporting-evidence', [
                'reason' => 'Buyer clarification',
                'vendor_id' => $vendor->id,
                'file' => UploadedFile::fake()->create('clarification.pdf', 12, 'application/pdf'),
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('supporting_evidence', [
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_id' => $vendor->id,
            'reason' => 'Buyer clarification',
        ]);
    }

    public function testSupportingEvidenceUploadRejectsCrossRfqOrWrongTenantQuoteSubmission(): void
    {
        Storage::fake('local');

        [$user, $rfq] = $this->seedUserAndRfq();
        $sameTenantOtherRfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-EV-OTHER-' . Str::lower((string) Str::ulid()),
            'title' => 'Other Evidence RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'closed',
        ]);
        $crossRfqQuote = QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $sameTenantOtherRfq->id,
            'vendor_name' => 'Cross RFQ Supplier',
            'uploaded_by' => $user->id,
            'file_path' => 'quotes/cross-rfq.pdf',
            'file_type' => 'application/pdf',
            'original_filename' => 'cross-rfq.pdf',
            'status' => 'ready',
            'submitted_at' => now(),
            'parsed_at' => now(),
            'line_items_count' => 1,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);
        [, $otherRfq] = $this->seedUserAndRfq();
        $otherRfqQuote = QuoteSubmission::query()->create([
            'tenant_id' => $otherRfq->tenant_id,
            'rfq_id' => $otherRfq->id,
            'vendor_name' => 'Wrong Tenant Supplier',
            'uploaded_by' => $user->id,
            'file_path' => 'quotes/wrong-tenant.pdf',
            'file_type' => 'application/pdf',
            'original_filename' => 'wrong-tenant.pdf',
            'status' => 'ready',
            'submitted_at' => now(),
            'parsed_at' => now(),
            'line_items_count' => 1,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        foreach ([$crossRfqQuote, $otherRfqQuote] as $quoteSubmission) {
            $response = $this->withHeaders($this->authHeaders((string) $user->tenant_id, (string) $user->id))
                ->post('/api/v1/rfqs/' . $rfq->id . '/evidence-vault/supporting-evidence', [
                    'reason' => 'Buyer clarification',
                    'quote_submission_id' => $quoteSubmission->id,
                    'file' => UploadedFile::fake()->create('clarification.pdf', 12, 'application/pdf'),
                ]);

            $response->assertStatus(422);
            $response->assertJsonPath('error', 'Validation failed');
            $response->assertJsonPath('details.quote_submission_id.0', 'The selected quote submission is invalid for this RFQ.');
            $this->assertDatabaseMissing('supporting_evidence', [
                'rfq_id' => $rfq->id,
                'quote_submission_id' => $quoteSubmission->id,
            ]);
        }
    }

    public function testSupportingEvidenceUploadRejectsWrongRfqAward(): void
    {
        Storage::fake('local');

        [$user, $rfq] = $this->seedUserAndRfq();
        $sameTenantOtherRfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-EV-AWARD-' . Str::lower((string) Str::ulid()),
            'title' => 'Other Award RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'closed',
        ]);
        $vendor = Vendor::query()->create([
            'tenant_id' => $user->tenant_id,
            'legal_name' => 'Award Supplier LLC',
            'display_name' => 'Award Supplier',
            'country_of_registration' => 'US',
            'primary_contact_name' => 'Award Contact',
            'primary_contact_email' => 'award-supplier@example.com',
            'status' => 'approved',
        ]);
        $wrongRfqAward = Award::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $sameTenantOtherRfq->id,
            'vendor_id' => $vendor->id,
            'status' => 'pending',
            'amount' => 10,
            'currency' => 'USD',
            'split_details' => [],
        ]);

        $response = $this->withHeaders($this->authHeaders((string) $user->tenant_id, (string) $user->id))
            ->post('/api/v1/rfqs/' . $rfq->id . '/evidence-vault/supporting-evidence', [
                'reason' => 'Buyer clarification',
                'award_id' => $wrongRfqAward->id,
                'file' => UploadedFile::fake()->create('clarification.pdf', 12, 'application/pdf'),
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Validation failed');
        $response->assertJsonPath('details.award_id.0', 'The selected award is invalid for this RFQ.');
        $this->assertDatabaseMissing('supporting_evidence', [
            'rfq_id' => $rfq->id,
            'award_id' => $wrongRfqAward->id,
        ]);
    }

    public function testSupportingEvidenceUploadReturnsSafeErrorWhenStorageFails(): void
    {
        [$user, $rfq] = $this->seedUserAndRfq();

        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->once()->andReturn($disk);

        $response = $this->withHeaders($this->authHeaders((string) $user->tenant_id, (string) $user->id))
            ->post('/api/v1/rfqs/' . $rfq->id . '/evidence-vault/supporting-evidence', [
                'reason' => 'Buyer clarification',
                'file' => UploadedFile::fake()->create('clarification.pdf', 12, 'application/pdf'),
            ]);

        $response->assertStatus(500);
        $response->assertJsonPath('message', 'Could not store supporting evidence.');
        $this->assertDatabaseMissing('supporting_evidence', [
            'rfq_id' => $rfq->id,
            'reason' => 'Buyer clarification',
        ]);
    }

    public function testSupportingEvidenceStorageDeletesFileWhenChecksumReadFails(): void
    {
        [$user, $rfq] = $this->seedUserAndRfq();

        $storedPath = 'supporting-evidence/' . $user->tenant_id . '/' . $rfq->id . '/stored-clarification.pdf';
        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn($storedPath);
        $disk->shouldReceive('get')->once()->with($storedPath)->andThrow(\RuntimeException::class, 'read failed');
        $disk->shouldReceive('delete')->once()->with($storedPath)->andReturn(true);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);

        $response = $this->withHeaders($this->authHeaders((string) $user->tenant_id, (string) $user->id))
            ->post('/api/v1/rfqs/' . $rfq->id . '/evidence-vault/supporting-evidence', [
                'reason' => 'Buyer clarification',
                'file' => UploadedFile::fake()->create('clarification.pdf', 12, 'application/pdf'),
            ]);

        $response->assertStatus(500);
        $response->assertJsonPath('message', 'Could not store supporting evidence.');
        $this->assertDatabaseMissing('supporting_evidence', [
            'rfq_id' => $rfq->id,
            'storage_path' => $storedPath,
        ]);
    }

    public function testEvidenceBundlePersistsRfqScopedManifestItemsAndSupportingEvidence(): void
    {
        [$user, $rfq] = $this->seedUserAndRfq();

        $bundle = EvidenceBundle::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'type' => 'award_justification',
            'manifest' => ['rfq_id' => $rfq->id],
            'checksum' => null,
            'created_by' => $user->id,
        ]);

        $item = EvidenceBundleItem::query()->create([
            'tenant_id' => $user->tenant_id,
            'evidence_bundle_id' => $bundle->id,
            'source_type' => 'quote_submission',
            'source_id' => (string) Str::ulid(),
            'artifact_kind' => 'quote_source',
            'label' => 'Supplier quote',
            'metadata' => ['status' => 'ready'],
            'included_at' => now(),
        ]);

        $supportingEvidence = SupportingEvidence::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'reason' => 'Clarification email from buyer',
            'original_filename' => 'clarification.pdf',
            'file_type' => 'application/pdf',
            'storage_path' => 'supporting-evidence/clarification.pdf',
            'checksum' => hash('sha256', 'clarification'),
            'uploaded_by' => $user->id,
            'uploaded_at' => now(),
        ]);

        $this->assertDatabaseHas('evidence_bundles', [
            'id' => $bundle->id,
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'type' => 'award_justification',
        ]);
        $this->assertDatabaseHas('evidence_bundle_items', [
            'evidence_bundle_id' => $bundle->id,
            'artifact_kind' => 'quote_source',
        ]);
        $this->assertDatabaseHas('supporting_evidence', [
            'rfq_id' => $rfq->id,
            'reason' => 'Clarification email from buyer',
        ]);

        $bundle->refresh();
        $item->refresh();
        $supportingEvidence->refresh();

        $this->assertSame('draft', $bundle->status);
        $this->assertSame(1, $bundle->version);
        $this->assertIsArray($bundle->manifest);
        $this->assertIsArray($item->metadata);
        $this->assertSame(1, $bundle->items()->count());
        $this->assertTrue($item->bundle->is($bundle));
        $this->assertTrue($supportingEvidence->rfq->is($rfq));
        $this->assertTrue($supportingEvidence->uploader->is($user));
    }

    /**
     * @return array{0: User, 1: Rfq}
     */
    private function seedUserAndRfq(): array
    {
        $tenantId = (string) Str::ulid();

        $user = User::query()->create([
            'tenant_id' => $tenantId,
            'email' => 'evidence-' . Str::lower((string) Str::ulid()) . '@example.com',
            'name' => 'Evidence User',
            'password_hash' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
        ]);

        $rfq = Rfq::query()->create([
            'tenant_id' => $tenantId,
            'rfq_number' => 'RFQ-EV-' . Str::lower((string) Str::ulid()),
            'title' => 'Evidence RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'closed',
        ]);

        return [$user, $rfq];
    }

    /**
     * @return array{comparison_run: ComparisonRun, approval: Approval, award: Award}
     */
    private function completeEvidenceRfq(User $user, Rfq $rfq, ?string $quoteFilePath = 'quotes/complete-supplier.pdf'): array
    {
        $quote = QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_name' => 'Complete Supplier',
            'uploaded_by' => $user->id,
            'file_path' => $quoteFilePath,
            'file_type' => 'application/pdf',
            'original_filename' => 'complete-supplier.pdf',
            'status' => 'ready',
            'submitted_at' => now(),
            'parsed_at' => now(),
            'line_items_count' => 2,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        $comparisonRun = ComparisonRun::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'name' => 'Final comparison',
            'is_preview' => false,
            'created_by' => $user->id,
            'request_payload' => [],
            'matrix_payload' => ['rows' => 1],
            'scoring_payload' => ['winner' => 'Complete Supplier'],
            'approval_payload' => [],
            'response_payload' => [],
            'readiness_payload' => [],
            'status' => 'final',
            'version' => 1,
        ]);

        $approval = Approval::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'comparison_run_id' => $comparisonRun->id,
            'type' => 'award_approval',
            'status' => 'approved',
            'requested_by' => $user->id,
            'requested_at' => now(),
            'amount' => 12345.67,
            'currency' => 'USD',
            'level' => 1,
            'approved_at' => now(),
            'approved_by' => $user->id,
        ]);

        $vendor = Vendor::query()->create([
            'tenant_id' => $user->tenant_id,
            'legal_name' => 'Complete Supplier LLC',
            'display_name' => 'Complete Supplier',
            'country_of_registration' => 'US',
            'primary_contact_name' => 'Complete Contact',
            'primary_contact_email' => 'complete-supplier@example.com',
            'status' => 'approved',
        ]);

        $award = Award::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'comparison_run_id' => $comparisonRun->id,
            'vendor_id' => $vendor->id,
            'status' => 'signed_off',
            'amount' => 12345.67,
            'currency' => 'USD',
            'split_details' => [],
            'signoff_at' => now(),
            'signed_off_by' => $user->id,
        ]);

        SupportingEvidence::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'quote_submission_id' => $quote->id,
            'award_id' => $award->id,
            'reason' => 'Buyer clarification retained for audit',
            'original_filename' => 'buyer-clarification.pdf',
            'file_type' => 'application/pdf',
            'storage_path' => 'supporting-evidence/buyer-clarification.pdf',
            'checksum' => hash('sha256', 'buyer-clarification'),
            'uploaded_by' => $user->id,
            'uploaded_at' => now(),
        ]);

        foreach (['quote_sources', 'final_comparison', 'approval_trail', 'award_signoff'] as $index => $eventType) {
            DecisionTrailEntry::query()->create([
                'tenant_id' => $user->tenant_id,
                'comparison_run_id' => $comparisonRun->id,
                'rfq_id' => $rfq->id,
                'sequence' => $index + 1,
                'event_type' => $eventType,
                'summary_payload' => ['code' => $eventType],
                'payload_hash' => hash('sha256', $eventType . '-payload'),
                'previous_hash' => $index === 0 ? str_repeat('0', 64) : hash('sha256', ((string) $index) . '-entry'),
                'entry_hash' => hash('sha256', $eventType . '-entry'),
                'occurred_at' => now()->addSeconds($index),
            ]);
        }

        return [
            'comparison_run' => $comparisonRun,
            'approval' => $approval,
            'award' => $award,
        ];
    }
}

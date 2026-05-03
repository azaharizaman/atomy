<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Approval;
use App\Models\Award;
use App\Models\ComparisonRun;
use App\Models\DecisionTrailEntry;
use App\Models\EvidenceBundle;
use App\Models\EvidenceBundleItem;
use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Models\SupportingEvidence;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
            ->assertJsonFragment(['code' => 'final_comparison'])
            ->assertJsonFragment(['code' => 'approval_trail'])
            ->assertJsonFragment(['code' => 'award_signoff']);
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
}

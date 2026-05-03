<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\EvidenceBundle;
use App\Models\EvidenceBundleItem;
use App\Models\Rfq;
use App\Models\SupportingEvidence;
use App\Models\User;
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

    public function testEvidenceBundlePersistsRfqScopedManifestItemsAndSupportingEvidence(): void
    {
        [$user, $rfq] = $this->seedUserAndRfq();

        $bundle = EvidenceBundle::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'type' => 'award_justification',
            'status' => 'draft',
            'version' => 1,
            'manifest' => ['rfq_id' => $rfq->id],
            'checksum' => null,
            'created_by' => $user->id,
        ]);

        EvidenceBundleItem::query()->create([
            'tenant_id' => $user->tenant_id,
            'evidence_bundle_id' => $bundle->id,
            'source_type' => 'quote_submission',
            'source_id' => (string) Str::ulid(),
            'artifact_kind' => 'quote_source',
            'label' => 'Supplier quote',
            'metadata' => ['status' => 'ready'],
            'included_at' => now(),
        ]);

        SupportingEvidence::query()->create([
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

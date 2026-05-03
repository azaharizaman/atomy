<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\ComparisonRun;
use App\Models\DecisionTrailEntry;
use App\Models\NormalizationConflict;
use App\Models\NormalizationSourceLine;
use App\Models\QuoteSubmission;
use App\Models\RiskItem;
use App\Models\Rfq;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class PetrochemicalTenantSeederQuotationFixturesTest extends TestCase
{
    private string $testStorageRoot;

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

        $this->testStorageRoot = sys_get_temp_dir() . '/atomy-q-seed-quotation-fixtures-storage';
        File::deleteDirectory($this->testStorageRoot);
        File::ensureDirectoryExists($this->testStorageRoot);
        $app['config']->set('filesystems.disks.local.root', $this->testStorageRoot);

        return $app;
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->testStorageRoot);

        parent::tearDown();
    }

    public function testPetrochemicalSeederCopiesCanonicalQuotePdfsIntoLocalStorage(): void
    {
        $this->artisan('atomy:generate-seed-quotation-fixtures')->assertExitCode(0);
        $this->artisan('migrate:fresh', ['--seed' => true])->assertExitCode(0);

        $submission = QuoteSubmission::query()->firstOrFail();

        self::assertStringStartsWith('quote-submissions/', (string) $submission->file_path);
        Storage::disk('local')->assertExists((string) $submission->file_path);
    }

    public function testPetrochemicalSeederCreatesCoherentProductionSnapshotEvidence(): void
    {
        $this->artisan('atomy:generate-seed-quotation-fixtures')->assertExitCode(0);
        $this->artisan('migrate:fresh', ['--seed' => true])->assertExitCode(0);

        $awardedRfqs = Rfq::query()
            ->where('status', 'awarded')
            ->get();

        self::assertGreaterThan(0, $awardedRfqs->count());

        foreach ($awardedRfqs as $rfq) {
            $finalRun = ComparisonRun::query()
                ->where('tenant_id', $rfq->tenant_id)
                ->where('rfq_id', $rfq->id)
                ->where('is_preview', false)
                ->where('status', 'final')
                ->first();

            self::assertNotNull($finalRun, 'Awarded RFQ must retain its final comparison run: ' . $rfq->rfq_number);
            $snapshot = $finalRun->response_payload['snapshot'] ?? null;
            $matrix = $finalRun->matrix_payload ?? null;
            $readiness = $finalRun->readiness_payload ?? null;

            self::assertIsArray($snapshot, 'Final comparison snapshot must be loadable by the WEB detail view.');
            self::assertIsArray($snapshot['normalized_lines'] ?? null);
            self::assertIsArray($snapshot['resolutions'] ?? null);
            self::assertIsArray($snapshot['currency_meta'] ?? null);
            self::assertIsArray($snapshot['vendors'] ?? null);
            self::assertArrayHasKey('rfq_version', $snapshot);

            self::assertIsArray($matrix, 'Final comparison matrix must be loadable by the WEB detail view.');
            self::assertIsArray($matrix['clusters'] ?? null);

            self::assertIsArray($readiness, 'Final comparison readiness must be loadable by the WEB detail view.');
            self::assertIsArray($readiness['blockers'] ?? null);
            self::assertIsArray($readiness['warnings'] ?? null);
            self::assertArrayHasKey('is_ready', $readiness);
            self::assertArrayHasKey('is_preview_only', $readiness);

            self::assertDatabaseHas('awards', [
                'tenant_id' => $rfq->tenant_id,
                'rfq_id' => $rfq->id,
                'comparison_run_id' => $finalRun->id,
                'status' => 'signed_off',
            ]);

            foreach (['comparison_snapshot_frozen', 'award_created', 'award_signed_off'] as $eventType) {
                self::assertDatabaseHas('decision_trail_entries', [
                    'tenant_id' => $rfq->tenant_id,
                    'rfq_id' => $rfq->id,
                    'comparison_run_id' => $finalRun->id,
                    'event_type' => $eventType,
                ]);
            }
        }

        $reviewableQuotes = QuoteSubmission::query()
            ->whereIn('status', ['ready', 'needs_review'])
            ->get();

        self::assertGreaterThan(0, $reviewableQuotes->count());

        foreach ($reviewableQuotes as $submission) {
            self::assertNotNull($submission->original_filename);
            self::assertStringEndsWith('.pdf', (string) $submission->original_filename);
            Storage::disk('local')->assertExists((string) $submission->file_path);

            self::assertGreaterThan(
                0,
                NormalizationSourceLine::query()
                    ->where('tenant_id', $submission->tenant_id)
                    ->where('quote_submission_id', $submission->id)
                    ->count(),
                'Reviewable quote must retain normalization source lines: ' . $submission->id,
            );
        }

        self::assertGreaterThan(
            0,
            NormalizationConflict::query()->count(),
            'Seed data should include buyer-review normalization conflicts.',
        );

        self::assertGreaterThan(
            0,
            RiskItem::query()->count(),
            'Seed data should include durable RFQ risk items.',
        );

        self::assertGreaterThan(
            0,
            DecisionTrailEntry::query()->count(),
            'Seed data should include durable decision-trail evidence.',
        );
    }
}

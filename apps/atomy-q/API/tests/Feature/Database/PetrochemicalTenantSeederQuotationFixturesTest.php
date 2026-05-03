<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\QuoteSubmission;
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
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class GenerateSeedQuotationFixturesCommandTest extends TestCase
{
    private string $fixtureRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureRoot = base_path('database/seed-data/quotations');

        File::deleteDirectory($this->fixtureRoot);
    }

    public function testFixtureCommandManifestContainsExpectedBusinessKeys(): void
    {
        $this->artisan('atomy:generate-seed-quotation-fixtures')
            ->expectsOutputToContain('Wrote manifest')
            ->assertExitCode(0);

        $manifestPath = $this->fixtureRoot . '/manifest.json';
        $pdfPath = $this->fixtureRoot . '/files/rfq-008-vendor-00.pdf';

        self::assertFileExists($manifestPath);
        self::assertFileExists($pdfPath);
        self::assertGreaterThan(200, filesize($pdfPath));

        /** @var array{entries: array<string, array<string, mixed>>} $manifest */
        $manifest = json_decode(
            (string) file_get_contents($manifestPath),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        self::assertCount(153, $manifest['entries']);
        self::assertArrayHasKey('rfq-008/vendor-00', $manifest['entries']);
    }
}

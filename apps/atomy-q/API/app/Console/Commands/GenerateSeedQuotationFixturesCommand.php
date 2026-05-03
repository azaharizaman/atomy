<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\SeedData\Quotations\PetrochemicalSeedQuotationCatalog;
use App\Support\SeedData\Quotations\SeedQuotationManifest;
use App\Support\SeedData\Quotations\SimplePdfWriter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class GenerateSeedQuotationFixturesCommand extends Command
{
    protected $signature = 'atomy:generate-seed-quotation-fixtures';

    protected $description = 'Generate canonical petrochemical seed quotation PDFs and manifest';

    public function handle(SimplePdfWriter $pdfWriter): int
    {
        $fixtureRoot = base_path('database/seed-data/quotations');
        $manifestPath = $fixtureRoot . '/manifest.json';
        $entries = PetrochemicalSeedQuotationCatalog::entries();

        File::ensureDirectoryExists($fixtureRoot . '/files');

        foreach ($entries as $entry) {
            $pdfWriter->write(
                $fixtureRoot . '/' . $entry['pdf_path'],
                $entry['document_lines'],
            );
        }

        SeedQuotationManifest::write($manifestPath, $entries);

        $this->info('Wrote manifest to ' . $manifestPath);

        return self::SUCCESS;
    }
}

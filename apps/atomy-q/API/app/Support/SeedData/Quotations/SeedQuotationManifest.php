<?php

declare(strict_types=1);

namespace App\Support\SeedData\Quotations;

use Illuminate\Support\Facades\File;

final class SeedQuotationManifest
{
    /**
     * @param array<string, array<string, mixed>> $entries
     */
    public static function write(string $path, array $entries): void
    {
        ksort($entries);

        File::ensureDirectoryExists(dirname($path));

        $payload = [
            'version' => 1,
            'entries' => $entries,
        ];

        File::put(
            $path,
            json_encode(
                $payload,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ) . PHP_EOL,
        );
    }

    /**
     * @return array{version: int, entries: array<string, array<string, mixed>>}
     */
    public static function read(string $path): array
    {
        /** @var array{version: int, entries: array<string, array<string, mixed>>} $manifest */
        $manifest = json_decode(
            File::get($path),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        return $manifest;
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Throwable;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class VerifyStorageDiskCommand extends Command
{
    protected $signature = 'atomy:verify-storage-disk
                            {--disk= : Filesystem disk to verify (defaults to filesystems.default)}
                            {--path-prefix=alpha-storage-smoke : Path prefix for the smoke object}';

    protected $description = 'Verify the configured storage disk can write and read a small object';

    public function handle(): int
    {
        $disk = $this->resolveDisk();
        $pathPrefix = $this->resolvePathPrefix();
        $timestamp = now();
        $path = $this->buildPath($pathPrefix, $timestamp, (string) Str::ulid());
        $contents = $this->buildPayload($disk, $path, $timestamp);
        $probeWritten = false;
        $verificationSucceeded = false;
        $cleanupSucceeded = true;

        try {
            $stored = Storage::disk($disk)->put($path, $contents);
            if ($stored !== true) {
                $this->error('Storage verification failed: unable to write path=' . $path);
                return self::FAILURE;
            }

            $probeWritten = true;
            $readBack = Storage::disk($disk)->get($path);
            if ($readBack !== $contents) {
                $this->error('Storage verification failed: read-back mismatch for path=' . $path);
                return self::FAILURE;
            }

            $verificationSucceeded = true;
        } catch (Throwable $throwable) {
            $this->error('Storage verification write/read failure: path=' . $path . ' ' . $throwable->getMessage());
        } finally {
            if ($probeWritten) {
                try {
                    $deleted = Storage::disk($disk)->delete($path);
                    if ($deleted !== true) {
                        $this->error('Storage verification failed: cleanup failed for path=' . $path);
                        $cleanupSucceeded = false;
                    }
                } catch (Throwable $cleanupThrowable) {
                    $this->error('Storage verification failed: cleanup failed for path=' . $path . ' ' . $cleanupThrowable->getMessage());
                    $cleanupSucceeded = false;
                }
            }
        }

        if ($verificationSucceeded && $cleanupSucceeded) {
            $this->info('Storage verification passed: disk=' . $disk . ' path=' . $path);
            return self::SUCCESS;
        }

        return self::FAILURE;
    }

    private function resolveDisk(): string
    {
        $disk = (string) ($this->option('disk') ?: config('filesystems.default', 'local'));

        return $disk !== '' ? $disk : 'local';
    }

    private function resolvePathPrefix(): string
    {
        $pathPrefix = trim((string) $this->option('path-prefix'));

        return $pathPrefix !== '' ? trim($pathPrefix, '/') : 'alpha-storage-smoke';
    }

    private function buildPath(string $pathPrefix, Carbon $timestamp, string $entropy): string
    {
        return $pathPrefix . '/verify-' . $timestamp->format('YmdHisv') . '-' . $entropy . '.txt';
    }

    private function buildPayload(string $disk, string $path, Carbon $timestamp): string
    {
        return 'storage-verification disk=' . $disk . ' path=' . $path . ' timestamp=' . $timestamp->toISOString();
    }
}

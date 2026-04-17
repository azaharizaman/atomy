<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Closure;
use Mockery;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class VerifyStorageDiskCommandTest extends TestCase
{
    public function testVerifyStorageDiskCommandReportsSuccessForStagingDisk(): void
    {
        config()->set('filesystems.default', 'staging');
        Storage::fake('staging');

        $this->artisan('atomy:verify-storage-disk', [
            '--path-prefix' => 'alpha-storage-smoke',
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('Storage verification passed: disk=staging');

        $this->assertSame([], Storage::disk('staging')->allFiles('alpha-storage-smoke'));
    }

    public function testVerifyStorageDiskCommandFailsWhenCleanupReturnsFalse(): void
    {
        config()->set('filesystems.default', 'cleanup-failure');
        $disk = $this->createMockDisk(static fn (): bool => false);

        Storage::shouldReceive('disk')
            ->with('cleanup-failure')
            ->andReturn($disk);

        $this->artisan('atomy:verify-storage-disk', [
            '--path-prefix' => 'alpha-storage-smoke',
        ])
            ->assertExitCode(1)
            ->expectsOutputToContain('Storage verification failed: cleanup failed for path=');
    }

    public function testVerifyStorageDiskCommandFailsWhenCleanupThrows(): void
    {
        config()->set('filesystems.default', 'cleanup-exception');
        $disk = $this->createMockDisk(static fn (): bool => throw new \RuntimeException('cleanup exploded'));

        Storage::shouldReceive('disk')
            ->with('cleanup-exception')
            ->andReturn($disk);

        $this->artisan('atomy:verify-storage-disk', [
            '--path-prefix' => 'alpha-storage-smoke',
        ])
            ->assertExitCode(1)
            ->expectsOutputToContain('Storage verification failed: cleanup failed for path=');
    }

    private function createMockDisk(Closure $deleteHandler): object
    {
        $contents = '';
        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldIgnoreMissing();
        $disk->shouldReceive('put')
            ->once()
            ->andReturnUsing(static function (string $path, string $writtenContents) use (&$contents): bool {
                $contents = $writtenContents;

                return true;
            });
        $disk->shouldReceive('get')
            ->once()
            ->andReturnUsing(static function (string $path) use (&$contents): string {
                return $contents;
            });
        $disk->shouldReceive('delete')
            ->once()
            ->andReturnUsing(static function (string $path) use ($deleteHandler): bool {
                return $deleteHandler();
            });

        return $disk;
    }
}

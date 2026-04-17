<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Closure;

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
        return new class ($deleteHandler) {
            public string $contents = '';

            public function __construct(private readonly Closure $deleteHandler)
            {
            }

            public function put(string $path, string $contents): bool
            {
                $this->contents = $contents;

                return true;
            }

            public function get(string $path): string
            {
                return $this->contents;
            }

            public function delete(string $path): bool
            {
                return ($this->deleteHandler)();
            }
        };
    }
}

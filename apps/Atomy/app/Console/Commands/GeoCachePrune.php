<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Nexus\Geo\Contracts\GeoRepositoryInterface;

final class GeoCachePrune extends Command
{
    protected $signature = 'geo:cache-prune {--tenant=default-tenant : Tenant ID} {--dry-run : Show what would be deleted without deleting}';
    
    protected $description = 'Remove expired geocoding cache entries';

    public function handle(): int
    {
        // Resolve from container to avoid constructor injection issues during service discovery
        $geoRepository = $this->laravel->make(GeoRepositoryInterface::class);
        
        $tenantId = $this->option('tenant');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN: No entries will be deleted');
        }

        $metrics = $geoRepository->getCacheMetrics($tenantId);
        $expiredCount = $metrics['expired_entries'];

        if ($expiredCount === 0) {
            $this->info('✓ No expired cache entries to prune');
            return Command::SUCCESS;
        }

        $this->warn("Found {$expiredCount} expired cache entries");

        if ($dryRun) {
            $this->info("Would delete {$expiredCount} entries");
            return Command::SUCCESS;
        }

        if ($this->confirm('Proceed with deletion?', true)) {
            $deleted = $geoRepository->pruneCacheEntries($tenantId);
            $this->info("✓ Deleted {$deleted} expired cache entries");
            return Command::SUCCESS;
        }

        $this->comment('Operation cancelled');
        return Command::FAILURE;
    }
}

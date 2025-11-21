<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Nexus\Routing\Contracts\RouteCacheInterface;

final class RoutingCachePrune extends Command
{
    protected $signature = 'routing:cache-prune {--tenant=default-tenant : Tenant ID} {--dry-run : Show what would be deleted without deleting} {--all : Clear all cache entries}';
    
    protected $description = 'Remove expired route cache entries';

    public function handle(): int
    {
        // Resolve from container to avoid constructor injection issues during service discovery
        $routeCache = $this->laravel->make(RouteCacheInterface::class);
        
        $tenantId = $this->option('tenant');
        $dryRun = $this->option('dry-run');
        $clearAll = $this->option('all');

        if ($dryRun) {
            $this->info('DRY RUN: No entries will be deleted');
        }

        $metrics = $routeCache->getCacheMetrics($tenantId);

        if ($clearAll) {
            $totalCount = $metrics['total_entries'];
            
            if ($totalCount === 0) {
                $this->info('✓ Cache is already empty');
                return Command::SUCCESS;
            }

            $this->warn("About to delete ALL {$totalCount} cache entries");

            if ($dryRun) {
                $this->info("Would delete {$totalCount} entries");
                return Command::SUCCESS;
            }

            if ($this->confirm('Are you sure? This will clear the entire route cache.', false)) {
                $deleted = $routeCache->clearAllCache($tenantId);
                $this->info("✓ Deleted {$deleted} cache entries");
                return Command::SUCCESS;
            }

            $this->comment('Operation cancelled');
            return Command::FAILURE;
        }

        // Prune expired only
        $expiredCount = $metrics['total_entries'] - $metrics['active_entries'];

        if ($expiredCount === 0) {
            $this->info('✓ No expired cache entries to prune');
            return Command::SUCCESS;
        }

        $this->warn("Found {$expiredCount} expired cache entries");

        if ($dryRun) {
            $this->info("Would delete {$expiredCount} entries");
            $savedMb = round(($expiredCount / $metrics['total_entries']) * $metrics['compressed_size_mb'], 2);
            $this->info("Would free approximately {$savedMb} MB");
            return Command::SUCCESS;
        }

        if ($this->confirm('Proceed with deletion?', true)) {
            $deleted = $routeCache->pruneCacheEntries($tenantId);
            $this->info("✓ Deleted {$deleted} expired cache entries");
            return Command::SUCCESS;
        }

        $this->comment('Operation cancelled');
        return Command::FAILURE;
    }
}

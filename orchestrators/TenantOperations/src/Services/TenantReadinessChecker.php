<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Services;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service to verify the readiness of the Tenant Domain implementation in L3.
 * 
 * This service is used by canary apps and diagnostic commands to ensure
 * all required interfaces for Multi-Tenancy are properly bound.
 */
final readonly class TenantReadinessChecker
{
    /**
     * @param array<string, object|null> $requiredAdapters Map of interface names to instances
     */
    public function __construct(
        private array $requiredAdapters,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Perform a health check on the tenant domain configuration.
     * 
     * @return array{ready: bool, issues: array<string>}
     */
    public function check(): array
    {
        $issues = [];
        $this->logger->info('Starting Tenant Domain readiness check');

        foreach ($this->requiredAdapters as $interface => $instance) {
            if ($instance === null) {
                $issues[] = "Missing binding for interface: {$interface}";
            }
        }

        $ready = empty($issues);
        
        if ($ready) {
            $this->logger->info('Tenant Domain is READY');
        } else {
            $this->logger->error('Tenant Domain is NOT READY', ['issues' => $issues]);
        }

        return [
            'ready' => $ready,
            'issues' => $issues,
        ];
    }
}

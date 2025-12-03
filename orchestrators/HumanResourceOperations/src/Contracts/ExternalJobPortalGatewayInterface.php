<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Contracts;

/**
 * Gateway interface for external job portal integrations
 */
interface ExternalJobPortalGatewayInterface
{
    /**
     * Post job to external portal
     */
    public function postJob(array $jobData): string;
    
    /**
     * Sync applications from external portal
     */
    public function syncApplications(string $jobId): array;
    
    /**
     * Remove job posting from portal
     */
    public function removeJob(string $externalJobId): void;
}

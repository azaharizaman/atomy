<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\Traits\TenantAwareJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Nexus\Tenant\Contracts\TenantContextInterface;

/**
 * Test job for verifying tenant context propagation.
 */
class TenantContextTestJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use TenantAwareJob;

    public static ?string $processedTenantId = null;

    /**
     * Execute the job.
     */
    public function handle(TenantContextInterface $tenantContext): void
    {
        // Capture the tenant ID that was active when this job processed
        self::$processedTenantId = $tenantContext->getCurrentTenantId();
    }
}

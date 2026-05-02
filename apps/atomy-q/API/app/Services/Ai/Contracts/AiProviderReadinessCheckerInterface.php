<?php

declare(strict_types=1);

namespace App\Services\Ai\Contracts;

use App\Services\Ai\AiProviderReadinessResult;

interface AiProviderReadinessCheckerInterface
{
    /**
     * @param  list<string>  $endpointGroups
     */
    public function check(
        array $endpointGroups,
        bool $deep,
        bool $publishAlerts,
        string $tenantId,
        string $rfqId,
    ): AiProviderReadinessResult;
}

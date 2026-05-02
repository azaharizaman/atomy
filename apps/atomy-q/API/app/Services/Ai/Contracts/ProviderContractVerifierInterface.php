<?php

declare(strict_types=1);

namespace App\Services\Ai\Contracts;

use App\Services\Ai\ProviderContractVerificationResult;

interface ProviderContractVerifierInterface
{
    /**
     * @return list<string>
     */
    public function endpointGroups(): array;

    /**
     * @param list<string> $endpointGroups
     */
    public function assertEndpointGroups(array $endpointGroups): void;

    /**
     * @param list<string> $endpointGroups
     *
     * @return list<ProviderContractVerificationResult>
     */
    public function verify(array $endpointGroups, string $tenantId, string $rfqId): array;
}

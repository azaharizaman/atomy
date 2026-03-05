<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Contracts;

/**
 * Determines whether recommendation output requires human approval.
 */
interface ApprovalGateServiceInterface
{
    /**
     * @param array<int, array{
     *   vendor_id: string,
     *   line_count: int,
     *   risks: array<int, array<string, mixed>>
     * }> $vendors
     * @param array<string, mixed> $scoring
     *
     * @return array{
     *   required: bool,
     *   status: string,
     *   reasons: array<int, string>
     * }
     */
    public function evaluate(array $vendors, array $scoring): array;
}


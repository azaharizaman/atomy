<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\Contracts;

use Nexus\AccountPeriodClose\ValueObjects\ReopenRequest;

/**
 * Contract for validating period reopen requests.
 */
interface ReopenValidatorInterface
{
    /**
     * Validate if a period can be reopened.
     *
     * @param ReopenRequest $request
     * @return bool
     */
    public function canReopen(ReopenRequest $request): bool;

    /**
     * Get reasons why a period cannot be reopened.
     *
     * @param ReopenRequest $request
     * @return array<string>
     */
    public function getBlockers(ReopenRequest $request): array;
}

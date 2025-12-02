<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\Services;

use Nexus\AccountPeriodClose\Contracts\ReopenValidatorInterface;
use Nexus\AccountPeriodClose\Enums\ReopenReason;
use Nexus\AccountPeriodClose\ValueObjects\ReopenRequest;

/**
 * Pure validation logic for period reopen requests.
 */
final readonly class ReopenValidator implements ReopenValidatorInterface
{
    private const int MAX_REOPEN_DAYS = 90;

    public function canReopen(ReopenRequest $request): bool
    {
        return count($this->getBlockers($request)) === 0;
    }

    public function getBlockers(ReopenRequest $request): array
    {
        $blockers = [];

        if (empty($request->getJustification())) {
            $blockers[] = 'Justification is required for reopening a period';
        }

        if (strlen($request->getJustification()) < 20) {
            $blockers[] = 'Justification must be at least 20 characters';
        }

        if ($request->getReason() === ReopenReason::REGULATORY_REQUIREMENT) {
            if ($request->getReopenUntil() === null) {
                $blockers[] = 'Regulatory reopen requests must specify a reopen until date';
            }
        }

        return $blockers;
    }
}

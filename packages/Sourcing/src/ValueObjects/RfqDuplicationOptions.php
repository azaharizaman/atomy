<?php

declare(strict_types=1);

namespace Nexus\Sourcing\ValueObjects;

final readonly class RfqDuplicationOptions
{
    public function __construct(
        public bool $copyLineItems = true,
        public bool $copyVendorInvitations = false,
        public bool $copyQuoteSubmissions = false,
        public bool $copyComparisonRuns = false,
        public bool $copyAwards = false,
        public bool $copyApprovalState = false,
        public bool $copyActivityHistory = false,
    ) {
    }
}

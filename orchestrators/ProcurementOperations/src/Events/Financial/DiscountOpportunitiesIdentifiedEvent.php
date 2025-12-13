<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\Financial;

use Nexus\Common\ValueObjects\Money;

/**
 * Event dispatched when discount opportunities have been identified through analysis.
 */
final readonly class DiscountOpportunitiesIdentifiedEvent
{
    public function __construct(
        public string $tenantId,
        public int $opportunityCount,
        public Money $totalPotentialSavings,
        public float $averageRoi,
        public \DateTimeImmutable $analysisDate,
        public array $options = [],
    ) {}
}

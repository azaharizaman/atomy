<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

use Nexus\Common\ValueObjects\Money;

/**
 * Line item for credit/debit memo.
 */
final readonly class MemoLineItem
{
    /**
     * @param string $description Line description
     * @param Money $amount Line amount
     * @param string|null $glAccountId GL account for posting
     * @param string|null $costCenterId Cost center
     * @param string|null $productId Optional product reference
     * @param float|null $quantity Quantity (for return/adjustment memos)
     * @param string|null $uom Unit of measure
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $description,
        public Money $amount,
        public ?string $glAccountId = null,
        public ?string $costCenterId = null,
        public ?string $productId = null,
        public ?float $quantity = null,
        public ?string $uom = null,
        public array $metadata = [],
    ) {}
}

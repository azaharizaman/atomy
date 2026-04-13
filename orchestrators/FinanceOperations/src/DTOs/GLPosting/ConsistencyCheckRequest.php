<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\GLPosting;

use Nexus\FinanceOperations\Enums\SubledgerType;
use InvalidArgumentException;

/**
 * Request DTO for GL consistency check operations.
 *
 * Used to verify consistency between subledgers and
 * general ledger control accounts.
 *
 * @since 1.0.0
 */
final readonly class ConsistencyCheckRequest
{
    /**
     * @var array<SubledgerType>
     */
    public array $subledgerTypes;

    /**
     * @param string $tenantId Tenant identifier
     * @param string $periodId Accounting period to check
     * @param array<mixed> $subledgerTypes Subledger types to check (must be SubledgerType instances or valid strings)
     */
    public function __construct(
        public string $tenantId,
        public string $periodId,
        array $subledgerTypes = [SubledgerType::RECEIVABLE, SubledgerType::PAYABLE, SubledgerType::ASSET],
    ) {
        $this->subledgerTypes = array_map(function ($type) {
            if ($type instanceof SubledgerType) {
                return $type;
            }
            if (is_string($type)) {
                return SubledgerType::fromString($type);
            }
            throw new InvalidArgumentException('Subledger types must be instances of SubledgerType or valid strings');
        }, $subledgerTypes);
    }
}

<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\GLPosting;

use Nexus\FinanceOperations\Enums\SubledgerType;

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
    public string $tenantId;

    public string $periodId;

    /**
     * @var array<SubledgerType>
     */
    public array $subledgerTypes;

    /**
     * @param array<SubledgerType|string> $subledgerTypes
     */
    public function __construct(
        string $tenantId,
        string $periodId,
        array $subledgerTypes = [SubledgerType::RECEIVABLE, SubledgerType::PAYABLE, SubledgerType::ASSET],
    ) {
        $this->tenantId = $tenantId;
        $this->periodId = $periodId;

        $normalized = [];
        foreach ($subledgerTypes as $subledgerType) {
            if ($subledgerType instanceof SubledgerType) {
                $normalized[] = $subledgerType;
                continue;
            }

            if (!is_string($subledgerType) || trim($subledgerType) === '') {
                continue;
            }

            try {
                $normalized[] = SubledgerType::fromString($subledgerType);
            } catch (\InvalidArgumentException) {
                // Ignore unknown values from loosely-typed callers.
            }
        }

        $this->subledgerTypes = $normalized;
    }
}

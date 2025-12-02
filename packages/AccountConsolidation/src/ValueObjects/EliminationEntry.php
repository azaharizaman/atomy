<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\ValueObjects;

use Nexus\AccountConsolidation\Enums\EliminationType;

/**
 * Represents an elimination entry in consolidation.
 */
final readonly class EliminationEntry
{
    public function __construct(
        private EliminationType $type,
        private string $debitAccountCode,
        private string $creditAccountCode,
        private float $amount,
        private string $description,
        private ?string $relatedEntityId = null,
    ) {}

    public function getType(): EliminationType
    {
        return $this->type;
    }

    public function getDebitAccountCode(): string
    {
        return $this->debitAccountCode;
    }

    public function getCreditAccountCode(): string
    {
        return $this->creditAccountCode;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getRelatedEntityId(): ?string
    {
        return $this->relatedEntityId;
    }
}

<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Contracts;

/**
 * Minimal requisition read-model needed by QuotationIntelligence.
 */
interface OrchestratorRequisitionInterface
{
    /**
     * @return array<OrchestratorRequisitionLineInterface>
     */
    public function getLines(): array;

    /**
     * RFQ closing date after which no new quotes are accepted.
     */
    public function getClosingDate(): ?\DateTimeImmutable;

    /**
     * Whether the RFQ closing date has passed.
     */
    public function isClosedForQuotes(): bool;
}


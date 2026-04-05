<?php

declare(strict_types=1);

namespace App\Adapters\QuotationIntelligence\Concerns;

use DateTimeImmutable;
use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Models\RfqLineItem;
use App\Models\Tenant;
use Nexus\QuotationIntelligence\Contracts\OrchestratorRequisitionInterface;
use Nexus\QuotationIntelligence\Contracts\OrchestratorRequisitionLineInterface;
use Nexus\QuotationIntelligence\Contracts\OrchestratorTenantInterface;
use Nexus\QuotationIntelligence\Contracts\QuotationDocumentInterface;

/**
 * Trait to provide model wrapping capabilities for QuotationIntelligence contracts.
 */
trait WrapsModels
{
    /**
     * Wrap a Tenant model.
     */
    protected function wrapTenant(Tenant $tenant): OrchestratorTenantInterface
    {
        return new class($tenant) implements OrchestratorTenantInterface {
            public function __construct(private Tenant $tenant) {}
            public function getId(): string { return $this->tenant->id; }
            public function getCurrency(): string { return $this->tenant->currency ?? 'USD'; }
        };
    }

    /**
     * Wrap a QuoteSubmission model.
     */
    protected function wrapDocument(QuoteSubmission $submission): QuotationDocumentInterface
    {
        return new class($submission) implements QuotationDocumentInterface {
            public function __construct(private QuoteSubmission $submission) {}
            public function getTenantId(): string { return $this->submission->tenant_id; }
            public function getMetadata(): array { 
                return [
                    'rfq_id' => $this->submission->rfq_id,
                    'vendor_id' => $this->submission->vendor_id,
                ]; 
            }
            public function getStoragePath(): string { return storage_path('app/' . $this->submission->file_path); }
        };
    }

    /**
     * Wrap an Rfq model as a Requisition.
     */
    protected function wrapRequisition(Rfq $rfq): OrchestratorRequisitionInterface
    {
        $lines = $rfq->lineItems
            ->map(fn (RfqLineItem $line): OrchestratorRequisitionLineInterface => $this->wrapLine($line))
            ->values()
            ->all();

        return new class($rfq, $lines) implements OrchestratorRequisitionInterface {
            /**
             * @param array<int, OrchestratorRequisitionLineInterface> $lines
             */
            public function __construct(
                private Rfq $rfq,
                private array $lines,
            ) {
            }

            public function getId(): string
            {
                return $this->rfq->id;
            }

            public function getLines(): array
            {
                return $this->lines;
            }

            public function getClosingDate(): ?DateTimeImmutable
            {
                $closingDate = $this->rfq->closing_date;

                return $closingDate instanceof \DateTimeInterface
                    ? DateTimeImmutable::createFromInterface($closingDate)
                    : null;
            }

            public function isClosedForQuotes(): bool
            {
                $closingDate = $this->rfq->closing_date;

                return $closingDate instanceof \DateTimeInterface
                    && $closingDate < new \DateTimeImmutable();
            }
        };
    }

    protected function wrapLine(RfqLineItem $line): OrchestratorRequisitionLineInterface
    {
        return new class($line) implements OrchestratorRequisitionLineInterface {
            public function __construct(private RfqLineItem $line) {}

            public function getUnit(): ?string
            {
                return $this->line->uom;
            }
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Adapters\QuotationIntelligence\Concerns;

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
        return new class($rfq) implements OrchestratorRequisitionInterface {
            public function __construct(private Rfq $rfq) {}

            public function getId(): string
            {
                return $this->rfq->id;
            }

            public function getLines(): array
            {
                return $this->rfq->lineItems->map(fn($line) => $this->wrapLine($line))->toArray();
            }

            public function getClosingDate(): ?\DateTimeImmutable
            {
                $closingDate = $this->rfq->closing_date;
                return $closingDate ? \DateTimeImmutable::createFromInterface($closingDate) : null;
            }

            public function isClosedForQuotes(): bool
            {
                $closingDate = $this->rfq->closing_date;
                if ($closingDate === null) {
                    return false;
                }
                return $closingDate->isPast();
            }

            private function wrapLine(RfqLineItem $line): OrchestratorRequisitionLineInterface
            {
                return new class($line) implements OrchestratorRequisitionLineInterface {
                    public function __construct(private RfqLineItem $line) {}

                    public function getUnit(): ?string
                    {
                        return $this->line->uom;
                    }
                };
            }
        };
    }
}

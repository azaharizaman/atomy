<?php

declare(strict_types=1);

namespace App\Adapters\QuotationIntelligence;

use App\Adapters\QuotationIntelligence\Concerns\WrapsModels;
use App\Models\QuoteSubmission;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Nexus\QuotationIntelligence\Contracts\OrchestratorDocumentRepositoryInterface;
use Nexus\QuotationIntelligence\Contracts\QuotationDocumentInterface;

final class OrchestratorDocumentRepository implements OrchestratorDocumentRepositoryInterface
{
    use WrapsModels;

    public function __construct(
        private readonly TenantContextInterface $tenantContext,
    ) {
    }

    public function findById(string $id): ?QuotationDocumentInterface
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $query = QuoteSubmission::query()->with('rfq');

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $submission = $query->find($id);

        if ($submission !== null && $submission->rfq !== null && (string) $submission->rfq->tenant_id !== (string) $submission->tenant_id) {
            return null;
        }

        return $submission ? $this->wrapDocument($submission) : null;
    }
}

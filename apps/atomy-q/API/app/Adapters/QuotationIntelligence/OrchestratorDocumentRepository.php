<?php

declare(strict_types=1);

namespace App\Adapters\QuotationIntelligence;

use App\Adapters\QuotationIntelligence\Concerns\WrapsModels;
use App\Models\QuoteSubmission;
use Nexus\QuotationIntelligence\Contracts\OrchestratorDocumentRepositoryInterface;
use Nexus\QuotationIntelligence\Contracts\QuotationDocumentInterface;

final class OrchestratorDocumentRepository implements OrchestratorDocumentRepositoryInterface
{
    use WrapsModels;

    public function findById(string $id): ?QuotationDocumentInterface
    {
        $submission = QuoteSubmission::query()->find($id);

        return $submission ? $this->wrapDocument($submission) : null;
    }
}

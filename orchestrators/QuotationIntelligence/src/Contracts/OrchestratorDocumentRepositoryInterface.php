<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Contracts;

/**
 * Port for document repository operations.
 */
interface OrchestratorDocumentRepositoryInterface
{
    /**
     * Find document by ID.
     *
     * @param string $id
     * @return QuotationDocumentInterface|null
     */
    public function findById(string $id): ?QuotationDocumentInterface;
}

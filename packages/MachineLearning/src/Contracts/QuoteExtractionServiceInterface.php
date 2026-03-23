<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Contracts;

use Nexus\MachineLearning\ValueObjects\QuoteExtractionResult;

interface QuoteExtractionServiceInterface
{
    /**
     * Extract line items from a document (PDF for Alpha)
     *
     * @param string $filePath Absolute path to the document
     * @param string $tenantId Tenant identifier
     * @return QuoteExtractionResult
     */
    public function extract(string $filePath, string $tenantId): QuoteExtractionResult;
}

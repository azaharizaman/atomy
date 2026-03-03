<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Contracts;

/**
 * Port for document content processing.
 */
interface OrchestratorContentProcessorInterface
{
    /**
     * Analyze document content.
     *
     * @param string $storagePath
     * @return object
     */
    public function analyze(string $storagePath): object;
}

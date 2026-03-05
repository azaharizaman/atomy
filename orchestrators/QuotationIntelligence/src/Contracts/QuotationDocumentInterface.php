<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Contracts;

/**
 * Read-model contract for quote documents processed by the orchestrator.
 */
interface QuotationDocumentInterface
{
    public function getTenantId(): string;

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    public function getStoragePath(): string;
}


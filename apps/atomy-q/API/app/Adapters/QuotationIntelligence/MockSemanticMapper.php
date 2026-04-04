<?php

declare(strict_types=1);

namespace App\Adapters\QuotationIntelligence;

use Nexus\QuotationIntelligence\Contracts\SemanticMapperInterface;

final class MockSemanticMapper implements SemanticMapperInterface
{
    public function mapToTaxonomy(string $description, string $tenantId): array
    {
        return [
            'code' => 'UNSPSC-43211500', // Mock code
            'confidence' => 0.95,
            'version' => 'v25.0',
        ];
    }

    public function validateCode(string $code, string $version): bool
    {
        return str_starts_with($code, 'UNSPSC-');
    }
}

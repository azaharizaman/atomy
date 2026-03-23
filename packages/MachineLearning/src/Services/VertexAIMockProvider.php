<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Services;

use Nexus\MachineLearning\Contracts\QuoteExtractionServiceInterface;
use Nexus\MachineLearning\ValueObjects\QuoteExtractionResult;

final class VertexAIMockProvider implements QuoteExtractionServiceInterface
{
    public function extract(string $filePath, string $tenantId): QuoteExtractionResult
    {
        usleep(500000);

        $extractedLines = [
            [
                'source_vendor' => 'Vendor Quote',
                'source_description' => 'Industrial Pump Model X500',
                'source_quantity' => 10.0000,
                'source_uom' => 'units',
                'source_unit_price' => 1250.00,
                'raw_data' => [
                    'line_number' => 1,
                    'original_text' => 'Industrial Pump Model X500 - 10 units @ $1,250.00',
                    'extracted_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                ],
            ],
            [
                'source_vendor' => 'Vendor Quote',
                'source_description' => 'Valve Assembly Kit Type B',
                'source_quantity' => 25.0000,
                'source_uom' => 'kits',
                'source_unit_price' => 85.50,
                'raw_data' => [
                    'line_number' => 2,
                    'original_text' => 'Valve Assembly Kit Type B - 25 kits @ $85.50',
                    'extracted_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                ],
            ],
            [
                'source_vendor' => 'Vendor Quote',
                'source_description' => 'Pressure Gauge 0-100 PSI',
                'source_quantity' => 50.0000,
                'source_uom' => 'pcs',
                'source_unit_price' => 42.00,
                'raw_data' => [
                    'line_number' => 3,
                    'original_text' => 'Pressure Gauge 0-100 PSI - 50 pcs @ $42.00',
                    'extracted_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                ],
            ],
        ];

        return new QuoteExtractionResult(
            extractedLines: $extractedLines,
            confidence: 85.5,
        );
    }
}

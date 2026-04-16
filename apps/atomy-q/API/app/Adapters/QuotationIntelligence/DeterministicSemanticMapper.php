<?php

declare(strict_types=1);

namespace App\Adapters\QuotationIntelligence;

use Nexus\QuotationIntelligence\Contracts\SemanticMapperInterface;

final readonly class DeterministicSemanticMapper implements SemanticMapperInterface
{
    private const UNSPSC_CODES = [
        '20101507' => ['pump', 'valve', 'compressor', 'motor'],
        '30150000' => ['pipe', 'tube', 'fitting', 'flange'],
        '40101600' => ['sensor', 'meter', 'gauge', 'indicator'],
        '40101700' => ['switch', 'relay', 'controller', 'panel'],
        '43201500' => ['computer', 'server', 'laptop', 'monitor'],
        '45121500' => ['filter', 'strainer', 'separator'],
        '31110000' => ['bearing', 'seal', 'gasket', 'o-ring'],
    ];

    private const DEFAULT_CODE = '20101507';

    /**
     * Deterministic alpha taxonomy mapping is intentionally tenant-agnostic.
     */
    public function mapToTaxonomy(string $description, string $tenantId): array
    {
        $lowerDesc = strtolower($description);

        foreach (self::UNSPSC_CODES as $code => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($lowerDesc, $keyword)) {
                    return [
                        'code' => (string) $code,
                        'confidence' => 92.0,
                        'version' => 'v25.0',
                    ];
                }
            }
        }

        return [
            'code' => self::DEFAULT_CODE,
            'confidence' => 90.0,
            'version' => 'v25.0',
        ];
    }

    /**
     * The interface requires a version argument; this deterministic validator checks known codes
     * and allows the DEFAULT_CODE fallback used by mapToTaxonomy when no keyword matches.
     */
    public function validateCode(string $code, string $version): bool
    {
        $validCodes = array_map('strval', array_keys(self::UNSPSC_CODES));
        $validCodes[] = self::DEFAULT_CODE;

        return in_array($code, $validCodes, true);
    }
}

<?php

declare(strict_types=1);

namespace App\Support\SeedData\Quotations;

final class PetrochemicalSeedQuotationCatalog
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function entries(): array
    {
        $entries = [];

        for ($rfqIndex = 0; $rfqIndex < self::rfqCount(); $rfqIndex++) {
            $kind = self::rfqKind($rfqIndex);
            $quoteTarget = self::quoteTarget($kind, $rfqIndex);

            for ($quoteIndex = 0; $quoteIndex < $quoteTarget; $quoteIndex++) {
                $businessKey = self::businessKey($rfqIndex, $quoteIndex);
                $vendorName = self::vendorName($rfqIndex, $quoteIndex);

                $entries[$businessKey] = [
                    'business_key' => $businessKey,
                    'rfq_key' => sprintf('rfq-%03d', $rfqIndex),
                    'vendor_key' => sprintf('vendor-%02d', $quoteIndex),
                    'rfq_index' => $rfqIndex,
                    'quote_index' => $quoteIndex,
                    'rfq_kind' => $kind,
                    'rfq_title' => self::rfqTitle($rfqIndex),
                    'vendor_name' => $vendorName,
                    'quote_status' => self::quoteStatusFor($kind, $quoteIndex),
                    'original_filename' => self::originalFilename($vendorName),
                    'file_type' => 'application/pdf',
                    'pdf_path' => self::pdfRelativePath($businessKey),
                    'document_lines' => self::documentLines($rfqIndex, $quoteIndex, $vendorName),
                    'line_seed_data' => self::lineSeedData($rfqIndex, $quoteIndex),
                ];
            }
        }

        ksort($entries);

        return $entries;
    }

    public static function businessKey(int $rfqIndex, int $quoteIndex): string
    {
        return sprintf('rfq-%03d/vendor-%02d', $rfqIndex, $quoteIndex);
    }

    public static function pdfRelativePath(string $businessKey): string
    {
        return 'files/' . str_replace('/', '-', $businessKey) . '.pdf';
    }

    private static function rfqCount(): int
    {
        return 56;
    }

    private static function rfqKind(int $rfqIndex): string
    {
        if ($rfqIndex < 8) {
            return 'draft';
        }

        if ($rfqIndex < 18) {
            return 'published_intake';
        }

        if ($rfqIndex < 24) {
            return 'published_stuck';
        }

        if ($rfqIndex < 32) {
            return 'closed_pending';
        }

        if ($rfqIndex < 52) {
            return 'awarded';
        }

        return 'cancelled';
    }

    private static function quoteTarget(string $kind, int $rfqIndex): int
    {
        return match ($kind) {
            'published_intake' => 1 + ($rfqIndex % 4),
            'published_stuck' => 3,
            'closed_pending' => 3 + ($rfqIndex % 2),
            'awarded' => 3 + ($rfqIndex % 3),
            'cancelled' => $rfqIndex % 3,
            default => 0,
        };
    }

    private static function quoteStatusFor(string $kind, int $quoteIndex): string
    {
        return match ($kind) {
            'published_intake' => match ($quoteIndex % 4) {
                0 => 'uploaded',
                1 => 'extracting',
                2 => 'extracted',
                default => 'normalizing',
            },
            'published_stuck' => 'needs_review',
            'closed_pending', 'awarded' => 'ready',
            'cancelled' => $quoteIndex === 0 ? 'normalizing' : 'failed',
            default => 'ready',
        };
    }

    private static function vendorName(int $rfqIndex, int $quoteIndex): string
    {
        $vendors = self::approvedVendors();
        $vendorIndex = ($quoteIndex + ($rfqIndex * 7)) % count($vendors);

        return $vendors[$vendorIndex];
    }

    private static function originalFilename(string $vendorName): string
    {
        $sanitized = preg_replace('/\W+/', '_', $vendorName) ?? 'Vendor';

        return 'Quote_' . trim($sanitized, '_') . '.pdf';
    }

    /**
     * @return list<string>
     */
    private static function documentLines(int $rfqIndex, int $quoteIndex, string $vendorName): array
    {
        $status = self::quoteStatusFor(self::rfqKind($rfqIndex), $quoteIndex);
        $lineSeedData = self::lineSeedData($rfqIndex, $quoteIndex);

        return [
            'Atomy-Q seed quotation fixture',
            'Business key: ' . self::businessKey($rfqIndex, $quoteIndex),
            'RFQ: ' . self::rfqTitle($rfqIndex),
            'Vendor: ' . $vendorName,
            'Status profile: ' . $status,
            'Commercial total (USD): ' . number_format(self::quoteTotal($lineSeedData), 2, '.', ''),
            'Payment terms: ' . self::paymentTerms($rfqIndex),
            'Delivery lead: ' . (6 + (($rfqIndex + $quoteIndex) % 9)) . ' weeks',
        ];
    }

    /**
     * @return list<array<string, int|float|string>>
     */
    private static function lineSeedData(int $rfqIndex, int $quoteIndex): array
    {
        $base = $rfqIndex + $quoteIndex + 1;

        return [
            [
                'description' => sprintf('Line 1 - Tag NFC-%03d-A', $rfqIndex + 1),
                'quantity' => 1 + ($base % 4),
                'uom' => 'ea',
                'unit_price' => 850.0 + ($base * 37.5),
                'currency' => 'USD',
            ],
            [
                'description' => sprintf('Line 2 - Tag NFC-%03d-B', $rfqIndex + 1),
                'quantity' => 2 + ($base % 3),
                'uom' => 'set',
                'unit_price' => 425.0 + ($base * 21.25),
                'currency' => 'USD',
            ],
            [
                'description' => sprintf('Line 3 - Service bundle NFC-%03d', $rfqIndex + 1),
                'quantity' => 1,
                'uom' => 'lot',
                'unit_price' => 1200.0 + ($base * 55.0),
                'currency' => 'USD',
            ],
        ];
    }

    /**
     * @param list<array<string, int|float|string>> $lineSeedData
     */
    private static function quoteTotal(array $lineSeedData): float
    {
        $total = 0.0;

        foreach ($lineSeedData as $line) {
            $total += (float) $line['quantity'] * (float) $line['unit_price'];
        }

        return $total;
    }

    private static function paymentTerms(int $rfqIndex): string
    {
        return $rfqIndex % 3 === 0 ? 'Net 45 EOM' : 'Net 30';
    }

    private static function rfqTitle(int $rfqIndex): string
    {
        $titles = [
            'HP control valves Class 900 - ethylene rundown',
            'Mechanical seal upgrade - cracked gas compressor',
            'Sulfuric acid catalyst reload (Claus tail gas)',
            'Demineralized water polisher resin',
            'Thermal oxidizer burner management system',
            'Stainless tank internals - agitator + baffles',
            'Vapor recovery unit compressor package',
            'Corrosion inhibitor injection skid',
            'DCS I/O marshalling cabinets + IS barriers',
            'Loading arm spare hydraulic power unit',
            'Nitrogen generator membrane replacement',
            'Flare pilot / igniter assembly',
            'Heat exchanger bundle - lean amine',
            'Emergency shower & eyewash stations (ATEX)',
            'Chemical metering pumps - cooling tower',
            'HVAC ATEX fan wall - analyzer shelter',
            'Fixed gas detection - LEL / H2S',
            'Firewater pump mechanical seal kit',
            'Insulation & cladding - steam tracing package',
            'Bolt tensioning service - turnaround',
            'Temporary steam boiler rental (12 MW)',
            'Scaffold & containment - sulfur pit',
            'NDT phased-array - welds on HP piping',
            'Relief valve recertification (in-situ)',
            'Laser alignment - train B compressors',
            'Catalyst sampling probes + quills',
            'Sorbent media - mercury guard bed',
            'Oxygen analyzer cells - reformer feed',
            'Submersible sump pumps - containment sump',
            'Gasket kit ANSI 900 - RF joint set',
        ];

        $title = $titles[$rfqIndex % count($titles)];

        if ($rfqIndex + 1 > count($titles)) {
            $title .= ' (lot ' . ($rfqIndex + 1) . ')';
        }

        return $title;
    }

    /**
     * @return list<string>
     */
    private static function approvedVendors(): array
    {
        return [
            'FlowServe Nordics AS',
            'Emerson Fisher Norway',
            'Alfa Laval Aalborg',
            'Sulzer Chemtech',
            'John Zink Hamworthy Combustion',
            'Donaldson Process Filtration',
            'Grundfos Process Nordic',
            'Atlas Copco Compressors',
            'Linde Engineering',
            'Air Liquide Advanced Separations',
            'Technip Energies Norge',
            'Wood PLC Norway',
            'Worley Chemetics',
            'Hayward Tyler (IMO) Pumps',
            'SIHI Liquid Ring',
            'Burkert Fluid Control',
            'Swagelok Bergen',
            'Parker Hannifin Scandinavia',
            'Honeywell UOP',
            'BASF Catalysts',
            'Clariant Catalysts',
            'Johnson Matthey NORAM',
        ];
    }
}

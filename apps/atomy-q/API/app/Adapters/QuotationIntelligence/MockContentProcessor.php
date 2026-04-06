<?php

declare(strict_types=1);

namespace App\Adapters\QuotationIntelligence;

use App\Models\QuoteSubmission;
use Nexus\QuotationIntelligence\Contracts\OrchestratorContentProcessorInterface;

final class MockContentProcessor implements OrchestratorContentProcessorInterface
{
    private const VARIATIONS = [
        ' (Mock Variation)',
        ' - Premium Grade',
        ' (Industrial Grade)',
        ' - Standard Model',
        ' (Professional Series)',
    ];

    public function analyze(string $storagePath): object
    {
        $submission = QuoteSubmission::query()
            ->where('file_path', $this->resolveRelativePath($storagePath))
            ->with(['rfq.lineItems' => fn ($q) => $q->orderBy('sort_order')])
            ->first();

        $lines = [];

        if ($submission && $submission->rfq && $submission->rfq->lineItems->isNotEmpty()) {
            // Hard stop on tenant mismatch to avoid cross-tenant leakage through mocks.
            if ((string) $submission->rfq->tenant_id !== (string) $submission->tenant_id) {
                $submission = null;
            }
        }

        if ($submission && $submission->rfq && $submission->rfq->lineItems->isNotEmpty()) {
            $items = $submission->rfq->lineItems->take(5)->values();
            $baseUnit = (string) ($items->first()->uom ?? 'EA');
            $currency = (string) ($items->first()->currency ?? 'USD');

            foreach ($items as $index => $item) {
                $variation = self::VARIATIONS[$index % count(self::VARIATIONS)];
                $lines[] = [
                    'rfq_line_id' => $item->id,
                    'description' => $item->description . $variation,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => 100.0 + ($index * 10),
                    'unit' => $baseUnit,
                    'currency' => $currency,
                    'terms' => 'Net 30',
                    'bbox' => ['x' => 10, 'y' => 20 + ($index * 30), 'w' => 100, 'h' => 15],
                ];
            }
        }

        return new class($lines) {
            public function __construct(private array $lines) {}
            public function getExtractedField(string $field, mixed $default = null): mixed {
                return $field === 'lines' ? $this->lines : $default;
            }
        };
    }

    private function resolveRelativePath(string $storagePath): string
    {
        $prefix = storage_path('app') . DIRECTORY_SEPARATOR;
        if (str_starts_with($storagePath, $prefix)) {
            return substr($storagePath, strlen($prefix));
        }

        return basename($storagePath);
    }
}

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
            ->whereHas('rfq', fn($q) => $q->whereColumn('rfqs.tenant_id', 'quote_submissions.tenant_id'))
            ->with(['rfq.lineItems' => fn($q) => $q->orderBy('sort_order')])
            ->get()
            ->first(fn($s) => str_contains($storagePath, (string) $s->file_path));

        $lines = [];

        if ($submission && $submission->rfq && $submission->rfq->lineItems->isNotEmpty()) {
            $items = $submission->rfq->lineItems->take(5)->values();
            foreach ($items as $index => $item) {
                $variation = self::VARIATIONS[$index % count(self::VARIATIONS)];
                $lines[] = [
                    'rfq_line_id' => $item->id,
                    'description' => $item->description . $variation,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => 100.0 + ($index * 10),
                    'unit' => $item->uom ?? 'EA',
                    'currency' => $submission->rfq->currency ?? 'USD',
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
}

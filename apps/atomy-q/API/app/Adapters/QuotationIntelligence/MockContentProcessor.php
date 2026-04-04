<?php

declare(strict_types=1);

namespace App\Adapters\QuotationIntelligence;

use App\Models\QuoteSubmission;
use Nexus\QuotationIntelligence\Contracts\OrchestratorContentProcessorInterface;

final class MockContentProcessor implements OrchestratorContentProcessorInterface
{
    /**
     * Analyze document content and return mock extracted lines.
     * We use the storagePath to find the submission and its RFQ context.
     */
    public function analyze(string $storagePath): object
    {
        // Extract original filename or path to identify the submission
        $submission = QuoteSubmission::query()
            ->with(['rfq.lineItems'])
            ->get()
            ->first(fn($s) => str_contains($storagePath, (string) $s->file_path));

        $lines = [];

        if ($submission && $submission->rfq) {
            foreach ($submission->rfq->lineItems as $item) {
                $lines[] = [
                    'rfq_line_id' => $item->id,
                    'description' => $item->description . ' (Mock Variation)',
                    'quantity' => (float) $item->quantity,
                    'unit_price' => 100.0, // Mock price
                    'unit' => $item->uom ?? 'EA',
                    'currency' => 'USD',
                    'terms' => 'Net 30',
                    'bbox' => ['x' => 10, 'y' => 20, 'w' => 100, 'h' => 15],
                ];
            }
        } else {
            // Fallback if no submission found
            $lines[] = [
                'rfq_line_id' => 'dummy-id',
                'description' => 'Mock Item',
                'quantity' => 1.0,
                'unit_price' => 100.0,
                'unit' => 'EA',
                'currency' => 'USD',
                'terms' => 'Net 30',
                'bbox' => ['x' => 10, 'y' => 20, 'w' => 100, 'h' => 15],
            ];
        }

        return new class($lines) {
            public function __construct(private array $lines) {}
            public function getExtractedField(string $field, mixed $default = null): mixed {
                return $field === 'lines' ? $this->lines : $default;
            }
        };
    }
}

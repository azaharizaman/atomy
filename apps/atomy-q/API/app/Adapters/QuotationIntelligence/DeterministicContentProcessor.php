<?php

declare(strict_types=1);

namespace App\Adapters\QuotationIntelligence;

use Nexus\QuotationIntelligence\Contracts\OrchestratorContentProcessorInterface;
use Nexus\Tenant\Contracts\TenantContextInterface;
use App\Models\QuoteSubmission;

final readonly class DeterministicContentProcessor implements OrchestratorContentProcessorInterface
{
    private const VARIATIONS = [
        ' (Alpha Deterministic Match)',
        ' - Premium Grade',
        ' (Industrial Grade)',
        ' - Standard Model',
        ' (Professional Series)',
    ];

    public function __construct(private TenantContextInterface $tenantContext)
    {
    }

    public function analyze(string $storagePath): object
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        if ($tenantId === null || $tenantId === '') {
            return $this->extractedLinesResult([]);
        }

        $submission = QuoteSubmission::query()
            ->where('tenant_id', $tenantId)
            ->where('file_path', $this->resolveRelativePath($storagePath))
            ->whereHas('rfq', fn ($q) => $q->where('tenant_id', $tenantId))
            ->with([
                'rfq.lineItems' => fn ($q) => $q
                    ->where('tenant_id', $tenantId)
                    ->orderBy('sort_order'),
            ])
            ->first();

        $lines = [];

        if ($submission && $submission->rfq && $submission->rfq->lineItems->isNotEmpty()) {
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

        return $this->extractedLinesResult($lines);
    }

    private function resolveRelativePath(string $storagePath): string
    {
        $prefix = storage_path('app') . DIRECTORY_SEPARATOR;
        if (str_starts_with($storagePath, $prefix)) {
            return substr($storagePath, strlen($prefix));
        }

        return basename($storagePath);
    }

    /**
     * @param array<int, array<string, mixed>> $lines
     */
    private function extractedLinesResult(array $lines): object
    {
        return new class($lines) {
            /**
             * @param array<int, array<string, mixed>> $lines
             */
            public function __construct(private array $lines) {}

            public function getExtractedField(string $field, mixed $default = null): mixed
            {
                return $field === 'lines' ? $this->lines : $default;
            }
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Adapters\QuotationIntelligence;

use RuntimeException;
use App\Models\QuoteSubmission;
use App\Models\RfqLineItem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Nexus\Tenant\Contracts\TenantContextInterface;
use App\Adapters\Ai\DTOs\DocumentExtractionRequest;
use App\Adapters\Ai\Support\DocumentExtractionValueCoercer;
use Nexus\QuotationIntelligence\Exceptions\QuotationIntelligenceException;
use Nexus\QuotationIntelligence\Contracts\OrchestratorContentProcessorInterface;
use App\Adapters\Ai\Contracts\ProviderDocumentIntelligenceClientInterface;

final readonly class ProviderQuoteContentProcessor implements OrchestratorContentProcessorInterface
{
    public function __construct(
        private ProviderDocumentIntelligenceClientInterface $documentClient,
        private TenantContextInterface $tenantContext,
    ) {
    }

    public function analyze(string $storagePath): object
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        if ($tenantId === null || $tenantId === '') {
            throw new QuotationIntelligenceException('Quote extraction requires tenant context.');
        }

        $relativePath = $this->resolveRelativePath($storagePath);
        $submission = QuoteSubmission::query()
            ->where('tenant_id', $tenantId)
            ->where('file_path', $relativePath)
            ->whereHas('rfq', fn ($query) => $query->where('tenant_id', $tenantId))
            ->with([
                'rfq.lineItems' => fn ($query) => $query
                    ->where('tenant_id', $tenantId)
                    ->orderBy('sort_order'),
            ])
            ->first();

        if (!$submission instanceof QuoteSubmission || $submission->rfq === null) {
            throw new QuotationIntelligenceException('Quote submission source document was not found.');
        }

        $result = $this->documentClient->extractDocument(new DocumentExtractionRequest(
            tenantId: $tenantId,
            rfqId: (string) $submission->rfq_id,
            quoteSubmissionId: (string) $submission->id,
            filename: (string) $submission->original_filename,
            mimeType: (string) $submission->file_type,
            absolutePath: $storagePath,
        ));

        $lines = $this->lines($submission, $result);

        return new class($lines, $result) {
            /**
             * @param list<array<string, mixed>> $lines
             * @param array<string, mixed> $result
             */
            public function __construct(
                private array $lines,
                private array $result,
            ) {
            }

            public function getExtractedField(string $field, mixed $default = null): mixed
            {
                return match ($field) {
                    'lines' => $this->lines,
                    'provider_result' => $this->result,
                    default => $this->result[$field] ?? $default,
                };
            }
        };
    }

    private function resolveRelativePath(string $storagePath): string
    {
        $prefix = rtrim(Storage::disk('local')->path(''), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (str_starts_with($storagePath, $prefix)) {
            return substr($storagePath, strlen($prefix));
        }

        throw new RuntimeException(sprintf(
            'QuoteSubmission.file_path could not be resolved because [%s] is outside the expected local storage prefix [%s].',
            $storagePath,
            $prefix,
        ));
    }

    /**
     * @param array<string, mixed> $result
     * @return list<array<string, mixed>>
     */
    private function lines(QuoteSubmission $submission, array $result): array
    {
        $rfqLineItems = $submission->rfq?->lineItems?->values();
        if ($rfqLineItems === null || $rfqLineItems->isEmpty()) {
            return [];
        }

        $lines = [];
        $claimedRfqLineIds = [];
        foreach (($result['lines'] ?? []) as $line) {
            if (!is_array($line)) {
                continue;
            }

            $description = isset($line['description']) && is_string($line['description'])
                ? trim($line['description'])
                : '';
            if ($description === '') {
                continue;
            }

            $rfqLineItem = $this->matchRfqLineItem($rfqLineItems->all(), $description, array_keys($claimedRfqLineIds));
            if ($rfqLineItem === null) {
                continue;
            }

            $claimedRfqLineIds[(string) $rfqLineItem->id] = true;

            $quantity = DocumentExtractionValueCoercer::floatOrNull($line['quantity'] ?? null) ?? 1.0;
            $unitPrice = DocumentExtractionValueCoercer::floatOrNull($line['unit_price'] ?? null) ?? 0.0;
            $terms = DocumentExtractionValueCoercer::stringOrNull($line['terms'] ?? null)
                ?? DocumentExtractionValueCoercer::stringOrNull($result['payment_terms'] ?? null)
                ?? '';

            $lines[] = [
                'rfq_line_id' => (string) $rfqLineItem->id,
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'unit' => is_string($line['unit'] ?? null) && trim((string) $line['unit']) !== ''
                    ? (string) $line['unit']
                    : (string) ($rfqLineItem->uom ?? 'EA'),
                'currency' => is_string($line['currency'] ?? null) && trim((string) $line['currency']) !== ''
                    ? (string) $line['currency']
                    : (string) ($rfqLineItem->currency ?? $result['currency'] ?? 'USD'),
                'terms' => $terms,
                'bbox' => ['x' => 0, 'y' => 0, 'w' => 0, 'h' => 0],
            ];
        }

        return $lines;
    }

    /**
     * @param array<int, RfqLineItem> $rfqLineItems
     * @param list<string> $excludedRfqLineIds
     */
    private function matchRfqLineItem(array $rfqLineItems, string $description, array $excludedRfqLineIds = []): ?RfqLineItem
    {
        $normalizedDescription = Str::of($description)->lower()->toString();
        $bestItem = null;
        $bestScore = -1;

        foreach ($rfqLineItems as $candidate) {
            if (in_array((string) $candidate->id, $excludedRfqLineIds, true)) {
                continue;
            }

            $candidateDescription = Str::of((string) ($candidate->description ?? ''))->lower()->toString();
            $score = 0;

            if ($candidateDescription !== '' && str_contains($normalizedDescription, $candidateDescription)) {
                $score += 10;
            }

            foreach (preg_split('/\s+/', preg_replace('/[^a-z0-9 ]/i', ' ', $candidateDescription) ?? '') ?: [] as $token) {
                if ($token !== '' && str_contains($normalizedDescription, $token)) {
                    $score++;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestItem = $candidate;
            }
        }

        if ($bestItem !== null && $bestScore > 0) {
            return $bestItem;
        }

        return null;
    }
}

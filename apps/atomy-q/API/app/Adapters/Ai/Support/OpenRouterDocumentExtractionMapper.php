<?php

declare(strict_types=1);

namespace App\Adapters\Ai\Support;

use App\Adapters\Ai\Exceptions\AiTransportInvalidResponseException;

final readonly class OpenRouterDocumentExtractionMapper
{
    /**
     * @param array<string, string> $currencyMappings
     */
    public function __construct(
        private array $currencyMappings = ['RM' => 'MYR'],
    ) {
    }

    /**
     * @param array<string, mixed> $response
     * @return array<string, mixed>
     */
    public function map(array $response): array
    {
        $content = $response['choices'][0]['message']['content'] ?? null;
        if (is_array($content)) {
            $content = implode("\n", array_values(array_filter($content, 'is_string')));
        }

        if (!is_string($content) || trim($content) === '') {
            throw new AiTransportInvalidResponseException('Document provider response did not include message content.');
        }

        $decoded = json_decode($this->stripMarkdownFence($content), true);
        if (!is_array($decoded)) {
            throw new AiTransportInvalidResponseException('Document provider response content was not valid JSON.');
        }

        $lines = [];
        foreach (($decoded['line_items'] ?? []) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $description = $this->stringOrNull($item['description'] ?? null);
            if ($description === null) {
                continue;
            }

            $lines[] = [
                'description' => $description,
                'quantity' => $this->floatOrNull($item['quantity'] ?? null) ?? 1.0,
                'unit_price' => $this->floatOrNull($item['unit_price'] ?? null),
                'line_total' => $this->floatOrNull($item['total'] ?? null),
                'total' => $this->floatOrNull($item['total'] ?? null),
                'unit' => $this->stringOrNull($item['uom'] ?? $item['unit'] ?? null) ?? 'EA',
                'currency' => $this->normalizeCurrencyCode($item['currency'] ?? $decoded['currency'] ?? null),
                'terms' => $this->stringOrNull($item['terms'] ?? $decoded['payment_terms'] ?? null),
                'sort_order' => $index,
            ];
        }

        return [
            'vendor_name' => $this->stringOrNull($decoded['vendor_name'] ?? null),
            'quote_number' => $this->stringOrNull($decoded['quote_number'] ?? null),
            'currency' => $this->normalizeCurrencyCode($decoded['currency'] ?? null),
            'total_amount' => $this->floatOrNull($decoded['total_amount'] ?? null),
            'payment_terms' => $this->stringOrNull($decoded['payment_terms'] ?? null),
            'delivery_terms' => $this->stringOrNull($decoded['delivery_terms'] ?? null),
            'validity' => $this->stringOrNull($decoded['validity'] ?? null),
            'notes' => $this->stringList($decoded['notes'] ?? []),
            'lines' => $lines,
        ];
    }

    private function stripMarkdownFence(string $content): string
    {
        $trimmed = trim($content);

        if (str_starts_with($trimmed, '```json')) {
            $trimmed = substr($trimmed, 7);
        } elseif (str_starts_with($trimmed, '```')) {
            $trimmed = substr($trimmed, 3);
        }

        if (str_ends_with($trimmed, '```')) {
            $trimmed = substr($trimmed, 0, -3);
        }

        return trim($trimmed);
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function normalizeCurrencyCode(mixed $value): ?string
    {
        $currency = $this->stringOrNull($value);
        if ($currency === null) {
            return null;
        }

        $upper = strtoupper($currency);

        return $this->currencyMappings[$upper] ?? $upper;
    }

    private function floatOrNull(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $normalized = preg_replace('/[^0-9.\-]/', '', $value);

            return is_string($normalized) && $normalized !== '' && is_numeric($normalized)
                ? (float) $normalized
                : null;
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (mixed $item): ?string => $this->stringOrNull($item),
            $value,
        )));
    }
}

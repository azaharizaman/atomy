<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\Tests\Unit;

use DateTimeImmutable;
use Nexus\MachineLearning\Enums\AiEndpointGroup;
use Nexus\ProcurementML\Enums\ProviderResultStatus;
use Nexus\ProcurementML\Enums\SourceLineOrigin;
use Nexus\ProcurementML\ValueObjects\NormalizationSuggestion;
use Nexus\ProcurementML\ValueObjects\ProviderAiProvenance;
use Nexus\ProcurementML\ValueObjects\ProviderQuoteExtractionResult;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class QuoteIntakeAiContractsTest extends TestCase
{
    #[Test]
    public function it_models_provider_quote_extraction_separately_from_persisted_submission_rows(): void
    {
        $processedAt = new DateTimeImmutable('2026-04-24T09:15:00+08:00');
        $provenance = $this->provenance(AiEndpointGroup::DOCUMENT, $processedAt);

        $result = ProviderQuoteExtractionResult::fromProviderPayload([
            'source_lines' => [
                [
                    'source_line_id' => 'line-provider-1',
                    'line_number' => 1,
                    'description' => 'Stainless steel valve',
                    'quantity' => 12,
                    'unit_of_measure' => 'EA',
                    'unit_price' => 42.75,
                    'currency' => 'USD',
                    'raw_text' => '1 Stainless steel valve 12 EA USD 42.75',
                    'confidence' => 0.91,
                    'reliability_hints' => ['ocr_table_confidence' => 0.87],
                ],
            ],
            'commercial_terms' => [
                'payment_terms' => 'Net 30',
                'delivery_terms' => 'DAP',
            ],
        ], $provenance);

        self::assertSame(ProviderResultStatus::AVAILABLE, $result->status);
        self::assertTrue($result->isAvailable());
        self::assertSame(SourceLineOrigin::PROVIDER, $result->sourceLines[0]->origin);
        self::assertSame($provenance, $result->provenance);

        self::assertSame(
            [
                'status' => 'available',
                'source_lines' => [
                    [
                        'source_line_id' => 'line-provider-1',
                        'line_number' => 1,
                        'description' => 'Stainless steel valve',
                        'quantity' => 12.0,
                        'unit_of_measure' => 'EA',
                        'unit_price' => 42.75,
                        'currency' => 'USD',
                        'raw_text' => '1 Stainless steel valve 12 EA USD 42.75',
                        'origin' => 'provider',
                        'confidence' => 0.91,
                        'reliability_hints' => ['ocr_table_confidence' => 0.87],
                    ],
                ],
                'commercial_terms' => [
                    'payment_terms' => 'Net 30',
                    'delivery_terms' => 'DAP',
                ],
                'provenance' => [
                    'provider_name' => 'openrouter',
                    'endpoint_group' => 'document',
                    'model_revision' => 'openai/gpt-4.1-mini:2026-04-01',
                    'prompt_template_version' => 'quote-extraction@2026-04-24',
                    'request_trace_id' => 'trace-quote-123',
                    'input_hash' => 'sha256:input',
                    'output_hash' => 'sha256:output',
                    'latency_ms' => 842,
                    'confidence' => 0.88,
                    'reliability_hints' => ['provider_confidence' => 'high'],
                    'processed_at' => '2026-04-24T09:15:00+08:00',
                ],
                'unavailable_reason' => null,
            ],
            $result->toArray(),
        );
    }

    #[Test]
    public function it_rejects_malformed_provider_payloads_as_manual_action_required(): void
    {
        $result = ProviderQuoteExtractionResult::fromProviderPayload([
            'source_lines' => [
                [
                    'source_line_id' => 'line-provider-1',
                    'description' => '',
                    'quantity' => 3,
                    'unit_of_measure' => 'EA',
                    'unit_price' => 18.5,
                    'currency' => 'USD',
                ],
            ],
        ], $this->provenance(AiEndpointGroup::DOCUMENT));

        self::assertSame(ProviderResultStatus::MANUAL_ACTION_REQUIRED, $result->status);
        self::assertFalse($result->isAvailable());
        self::assertSame([], $result->sourceLines);
        self::assertSame('provider_payload_malformed', $result->unavailableReason);
    }

    #[Test]
    public function it_models_normalization_suggestions_with_origin_and_provider_provenance(): void
    {
        $processedAt = new DateTimeImmutable('2026-04-24T10:05:00+08:00');
        $provenance = $this->provenance(AiEndpointGroup::NORMALIZATION, $processedAt);

        $suggestion = new NormalizationSuggestion(
            sourceLineId: 'line-provider-1',
            rfqLineId: 'rfq-line-9',
            origin: SourceLineOrigin::PROVIDER,
            normalizedDescription: 'Stainless steel valve',
            normalizedQuantity: 12.0,
            normalizedUnitOfMeasure: 'EA',
            taxonomyCode: 'VALVE.STAINLESS',
            confidence: 0.82,
            reliabilityHints: ['mapping_strength' => 'medium'],
            explanation: 'Matched model, material, and unit of measure.',
            provenance: $provenance,
        );

        self::assertSame(
            [
                'source_line_id' => 'line-provider-1',
                'rfq_line_id' => 'rfq-line-9',
                'origin' => 'provider',
                'normalized_description' => 'Stainless steel valve',
                'normalized_quantity' => 12.0,
                'normalized_unit_of_measure' => 'EA',
                'taxonomy_code' => 'VALVE.STAINLESS',
                'confidence' => 0.82,
                'reliability_hints' => ['mapping_strength' => 'medium'],
                'explanation' => 'Matched model, material, and unit of measure.',
                'provenance' => [
                    'provider_name' => 'openrouter',
                    'endpoint_group' => 'normalization',
                    'model_revision' => 'openai/gpt-4.1-mini:2026-04-01',
                    'prompt_template_version' => 'quote-extraction@2026-04-24',
                    'request_trace_id' => 'trace-quote-123',
                    'input_hash' => 'sha256:input',
                    'output_hash' => 'sha256:output',
                    'latency_ms' => 842,
                    'confidence' => 0.88,
                    'reliability_hints' => ['provider_confidence' => 'high'],
                    'processed_at' => '2026-04-24T10:05:00+08:00',
                ],
            ],
            $suggestion->toArray(),
        );
    }

    private function provenance(
        AiEndpointGroup $endpointGroup,
        ?DateTimeImmutable $processedAt = null,
    ): ProviderAiProvenance {
        return new ProviderAiProvenance(
            providerName: 'openrouter',
            endpointGroup: $endpointGroup,
            modelRevision: 'openai/gpt-4.1-mini:2026-04-01',
            promptTemplateVersion: 'quote-extraction@2026-04-24',
            requestTraceId: 'trace-quote-123',
            inputHash: 'sha256:input',
            outputHash: 'sha256:output',
            latencyMs: 842,
            confidence: 0.88,
            reliabilityHints: ['provider_confidence' => 'high'],
            processedAt: $processedAt ?? new DateTimeImmutable('2026-04-24T09:15:00+08:00'),
        );
    }
}

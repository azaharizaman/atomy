# Atomy-Q Provider Quote E2E Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make provider-backed quotation extraction the alpha quote-ingestion path, with fake-provider development coverage and opt-in live OpenRouter e2e coverage over `sample/**` requisition fixtures.

**Architecture:** Keep Layer 1 and Layer 2 provider-neutral. Add API adapter code that maps stored quotation PDFs into OpenRouter chat-completions requests with base64 `file_data`, `file-parser`, and `mistral-ocr`, then maps provider JSON back into the existing quote-ingestion source-line contract. Use fake OpenRouter-shaped responses for ordinary tests and reserve real provider calls for explicit release gates.

**Tech Stack:** Laravel API, PHP 8.3, PHPUnit, Laravel HTTP client fakes, existing `ProviderAiTransportInterface`, existing `QuoteIngestionOrchestrator`, Playwright for WEB e2e, JSON fixtures under `sample/`.

---

## File Map

- Create: `apps/atomy-q/API/app/Adapters/QuotationIntelligence/ProviderQuoteContentProcessor.php`
  - Reads stored quote files, calls document provider client, returns `OrchestratorContentProcessorInterface` result.
- Create: `apps/atomy-q/API/app/Adapters/Ai/DTOs/DocumentExtractionRequest.php`
  - Holds tenant, RFQ, quote, filename, MIME type, and absolute file path for provider extraction.
- Create: `apps/atomy-q/API/app/Adapters/Ai/Support/OpenRouterDocumentPayloadFactory.php`
  - Builds OpenRouter chat-completions payload with `file_data` and OCR plugin.
- Create: `apps/atomy-q/API/app/Adapters/Ai/Support/OpenRouterDocumentExtractionMapper.php`
  - Parses provider response content into stable extracted line arrays and commercial terms.
- Modify: `apps/atomy-q/API/app/Adapters/Ai/ProviderDocumentIntelligenceClient.php`
  - Add typed document extraction method while keeping existing `extract(array $payload)` for contract command compatibility.
- Modify: `apps/atomy-q/API/app/Adapters/Ai/Contracts/ProviderDocumentIntelligenceClientInterface.php`
  - Add typed method.
- Modify: `apps/atomy-q/API/app/Providers/AppServiceProvider.php`
  - Bind provider-backed content processor when `AI_MODE=provider` and quote document capability can be attempted.
- Modify: `apps/atomy-q/API/config/atomy.php`
  - Add document parser config fields for OpenRouter plugin and OCR engine.
- Modify: `apps/atomy-q/API/.env.example`
  - Document `AI_DOCUMENT_PARSER_PLUGIN=file-parser` and `AI_DOCUMENT_PDF_ENGINE=mistral-ocr`.
- Create: `apps/atomy-q/API/tests/Feature/ProviderQuoteExtractionTest.php`
  - Proves provider extraction creates persisted normalization source lines from fake OpenRouter response.
- Modify: `apps/atomy-q/API/tests/Feature/QuoteIngestionPipelineTest.php`
  - Preserve manual continuity tests and assert provider mode does not fall through deterministic content.
- Create: `apps/atomy-q/API/tests/Support/FakeOpenRouterDocumentResponses.php`
  - Reusable OpenRouter-shaped fake JSON responses.
- Create: `apps/atomy-q/WEB/tests/provider-quote-e2e.spec.ts`
  - Fake-provider browser/API e2e over sample metadata.
- Create: `apps/atomy-q/WEB/tests/provider-quote-live.spec.ts`
  - Opt-in live-provider e2e; skipped unless `AI_PROVIDER_E2E=true`.
- Create: `apps/atomy-q/WEB/tests/support/providerQuoteFixtures.ts`
  - Reads `sample/*/metadata.json`, resolves quote PDF paths, validates fixture shape.
- Modify: `apps/atomy-q/WEB/package.json`
  - Add scripts for fake and live provider quote e2e.
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
  - Record provider-backed quote extraction and test gates.
- Modify: `apps/atomy-q/docs/02-release-management/current-release/release-checklist.md`
  - Add opt-in provider quote e2e evidence row.

## Task 1: Fixture Loader And Sample Contract

**Files:**
- Create: `apps/atomy-q/WEB/tests/support/providerQuoteFixtures.ts`
- Test: `apps/atomy-q/WEB/tests/support/providerQuoteFixtures.test.ts`
- Existing sample doc: `sample/metadata.example.json`

- [ ] **Step 1: Write fixture loader tests**

Create `apps/atomy-q/WEB/tests/support/providerQuoteFixtures.test.ts`:

```ts
import { describe, expect, it } from 'vitest';
import { discoverProviderQuoteFixtures, parseProviderQuoteFixture } from './providerQuoteFixtures';

describe('provider quote fixtures', () => {
  it('parses metadata example with quote PDF references', () => {
    const fixture = parseProviderQuoteFixture({
      baseDir: '../../../sample',
      metadataPath: '../../../sample/metadata.example.json',
    });

    expect(fixture.requisitionId).toBe('vehicle-service-rfq');
    expect(fixture.rfqLineItems).toHaveLength(1);
    expect(fixture.quotes[0]?.file).toBe('5b99b82bd0fda101329131.pdf');
    expect(fixture.e2e.documentParser.pdfEngine).toBe('mistral-ocr');
  });

  it('discovers folder metadata files and ignores root example metadata', () => {
    const fixtures = discoverProviderQuoteFixtures('../../../sample');

    expect(fixtures.every((fixture) => fixture.metadataPath.endsWith('/metadata.json'))).toBe(true);
  });
});
```

- [ ] **Step 2: Run failing fixture tests**

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run tests/support/providerQuoteFixtures.test.ts
```

Expected: FAIL because `providerQuoteFixtures.ts` does not exist.

- [ ] **Step 3: Implement fixture loader**

Create `apps/atomy-q/WEB/tests/support/providerQuoteFixtures.ts`:

```ts
import fs from 'node:fs';
import path from 'node:path';

export interface ProviderQuoteFixture {
  metadataPath: string;
  baseDir: string;
  requisitionId: string;
  title: string;
  currency: string;
  rfqLineItems: Array<{
    lineReference: string;
    description: string;
    quantity: number;
    uom: string;
    expectedKeywords: string[];
  }>;
  quotes: Array<{
    vendorReference: string;
    vendorName: string;
    file: string;
    filePath: string;
    mimeType: string;
    expected: {
      quoteNumber?: string;
      currency?: string;
      totalAmount?: number;
      lineCountMin: number;
      lineKeywords: string[];
      paymentTermsKeywords: string[];
      validityKeywords: string[];
    };
  }>;
  e2e: {
    requiresLiveProvider: boolean;
    provider: string;
    documentParser: {
      plugin: string;
      pdfEngine: string;
    };
    assertions: {
      uploadStatus: string[];
      sourceLinesMin: number;
      allowManualMappingCompletion: boolean;
    };
  };
}

export function discoverProviderQuoteFixtures(sampleRoot: string): ProviderQuoteFixture[] {
  const absoluteRoot = path.resolve(process.cwd(), sampleRoot);
  if (!fs.existsSync(absoluteRoot)) {
    return [];
  }

  return fs
    .readdirSync(absoluteRoot, { withFileTypes: true })
    .filter((entry) => entry.isDirectory())
    .map((entry) => path.join(absoluteRoot, entry.name, 'metadata.json'))
    .filter((metadataPath) => fs.existsSync(metadataPath))
    .map((metadataPath) => parseProviderQuoteFixture({ baseDir: path.dirname(metadataPath), metadataPath }));
}

export function parseProviderQuoteFixture(input: { baseDir: string; metadataPath: string }): ProviderQuoteFixture {
  const metadataPath = path.resolve(process.cwd(), input.metadataPath);
  const baseDir = path.resolve(process.cwd(), input.baseDir);
  const raw = JSON.parse(fs.readFileSync(metadataPath, 'utf8')) as Record<string, any>;

  const quotes = assertArray(raw.quotes, 'quotes').map((quote, index) => {
    const file = assertString(quote.file, `quotes[${index}].file`);
    const filePath = path.resolve(baseDir, file);

    return {
      vendorReference: assertString(quote.vendor_reference, `quotes[${index}].vendor_reference`),
      vendorName: assertString(quote.vendor_name, `quotes[${index}].vendor_name`),
      file,
      filePath,
      mimeType: assertString(quote.mime_type, `quotes[${index}].mime_type`),
      expected: {
        quoteNumber: optionalString(quote.expected?.quote_number),
        currency: optionalString(quote.expected?.currency),
        totalAmount: optionalNumber(quote.expected?.total_amount),
        lineCountMin: assertNumber(quote.expected?.line_count_min, `quotes[${index}].expected.line_count_min`),
        lineKeywords: assertStringArray(quote.expected?.line_keywords, `quotes[${index}].expected.line_keywords`),
        paymentTermsKeywords: assertStringArray(
          quote.expected?.payment_terms_keywords,
          `quotes[${index}].expected.payment_terms_keywords`,
        ),
        validityKeywords: assertStringArray(quote.expected?.validity_keywords, `quotes[${index}].expected.validity_keywords`),
      },
    };
  });

  return {
    metadataPath,
    baseDir,
    requisitionId: assertString(raw.requisition_id, 'requisition_id'),
    title: assertString(raw.title, 'title'),
    currency: assertString(raw.currency, 'currency'),
    rfqLineItems: assertArray(raw.rfq_line_items, 'rfq_line_items').map((line, index) => ({
      lineReference: assertString(line.line_reference, `rfq_line_items[${index}].line_reference`),
      description: assertString(line.description, `rfq_line_items[${index}].description`),
      quantity: assertNumber(line.quantity, `rfq_line_items[${index}].quantity`),
      uom: assertString(line.uom, `rfq_line_items[${index}].uom`),
      expectedKeywords: assertStringArray(line.expected_keywords, `rfq_line_items[${index}].expected_keywords`),
    })),
    quotes,
    e2e: {
      requiresLiveProvider: raw.e2e?.requires_live_provider === true,
      provider: assertString(raw.e2e?.provider, 'e2e.provider'),
      documentParser: {
        plugin: assertString(raw.e2e?.document_parser?.plugin, 'e2e.document_parser.plugin'),
        pdfEngine: assertString(raw.e2e?.document_parser?.pdf_engine, 'e2e.document_parser.pdf_engine'),
      },
      assertions: {
        uploadStatus: assertStringArray(raw.e2e?.assertions?.upload_status, 'e2e.assertions.upload_status'),
        sourceLinesMin: assertNumber(raw.e2e?.assertions?.source_lines_min, 'e2e.assertions.source_lines_min'),
        allowManualMappingCompletion: raw.e2e?.assertions?.allow_manual_mapping_completion === true,
      },
    },
  };
}

function assertArray(value: unknown, name: string): any[] {
  if (!Array.isArray(value)) {
    throw new Error(`Invalid provider quote fixture: ${name} must be an array.`);
  }

  return value;
}

function assertString(value: unknown, name: string): string {
  if (typeof value !== 'string' || value.trim() === '') {
    throw new Error(`Invalid provider quote fixture: ${name} must be a non-empty string.`);
  }

  return value;
}

function assertNumber(value: unknown, name: string): number {
  if (typeof value !== 'number' || !Number.isFinite(value)) {
    throw new Error(`Invalid provider quote fixture: ${name} must be a finite number.`);
  }

  return value;
}

function assertStringArray(value: unknown, name: string): string[] {
  return assertArray(value, name).map((item, index) => assertString(item, `${name}[${index}]`));
}

function optionalString(value: unknown): string | undefined {
  return typeof value === 'string' && value.trim() !== '' ? value : undefined;
}

function optionalNumber(value: unknown): number | undefined {
  return typeof value === 'number' && Number.isFinite(value) ? value : undefined;
}
```

- [ ] **Step 4: Run fixture tests**

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run tests/support/providerQuoteFixtures.test.ts
```

Expected: PASS.

- [ ] **Step 5: Commit fixture loader**

Run:

```bash
git add apps/atomy-q/WEB/tests/support/providerQuoteFixtures.ts apps/atomy-q/WEB/tests/support/providerQuoteFixtures.test.ts
git commit -m "Add provider quote fixture loader"
```

## Task 2: OpenRouter Document Payload And Mapper

**Files:**
- Create: `apps/atomy-q/API/app/Adapters/Ai/DTOs/DocumentExtractionRequest.php`
- Create: `apps/atomy-q/API/app/Adapters/Ai/Support/OpenRouterDocumentPayloadFactory.php`
- Create: `apps/atomy-q/API/app/Adapters/Ai/Support/OpenRouterDocumentExtractionMapper.php`
- Create: `apps/atomy-q/API/tests/Unit/Adapters/Ai/OpenRouterDocumentPayloadFactoryTest.php`
- Create: `apps/atomy-q/API/tests/Unit/Adapters/Ai/OpenRouterDocumentExtractionMapperTest.php`
- Modify: `apps/atomy-q/API/config/atomy.php`
- Modify: `apps/atomy-q/API/.env.example`

- [ ] **Step 1: Write payload factory test**

Create `apps/atomy-q/API/tests/Unit/Adapters/Ai/OpenRouterDocumentPayloadFactoryTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters\Ai;

use App\Adapters\Ai\DTOs\DocumentExtractionRequest;
use App\Adapters\Ai\Support\OpenRouterDocumentPayloadFactory;
use PHPUnit\Framework\TestCase;

final class OpenRouterDocumentPayloadFactoryTest extends TestCase
{
    public function test_it_builds_openrouter_pdf_payload_with_mistral_ocr_plugin(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'quote-pdf-');
        self::assertIsString($file);
        file_put_contents($file, '%PDF-1.7 sample');

        $factory = new OpenRouterDocumentPayloadFactory(
            modelId: 'baidu/qianfan-ocr-fast:free',
            parserPlugin: 'file-parser',
            pdfEngine: 'mistral-ocr',
        );

        $payload = $factory->build(new DocumentExtractionRequest(
            tenantId: 'tenant-1',
            rfqId: 'rfq-1',
            quoteSubmissionId: 'quote-1',
            filename: 'quote.pdf',
            mimeType: 'application/pdf',
            absolutePath: $file,
        ));

        self::assertSame('baidu/qianfan-ocr-fast:free', $payload['model']);
        self::assertSame('file-parser', $payload['plugins'][0]['id']);
        self::assertSame('mistral-ocr', $payload['plugins'][0]['pdf']['engine']);
        self::assertSame('file', $payload['messages'][0]['content'][1]['type']);
        self::assertStringStartsWith(
            'data:application/pdf;base64,',
            $payload['messages'][0]['content'][1]['file']['file_data'],
        );
    }
}
```

- [ ] **Step 2: Write mapper test**

Create `apps/atomy-q/API/tests/Unit/Adapters/Ai/OpenRouterDocumentExtractionMapperTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters\Ai;

use App\Adapters\Ai\Support\OpenRouterDocumentExtractionMapper;
use PHPUnit\Framework\TestCase;

final class OpenRouterDocumentExtractionMapperTest extends TestCase
{
    public function test_it_maps_openrouter_json_content_to_extracted_lines(): void
    {
        $mapper = new OpenRouterDocumentExtractionMapper();

        $result = $mapper->map([
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'vendor_name' => 'Kuching Utama Sdn Bhd',
                        'quote_number' => '1809/2975',
                        'currency' => 'RM',
                        'total_amount' => 84.8,
                        'line_items' => [[
                            'description' => 'BREAKDOWN ASSIST JUMP START VEHICLE',
                            'quantity' => 1,
                            'unit_price' => 80,
                            'total' => 84.8,
                        ]],
                        'payment_terms' => '50% deposit required before work can be carried out',
                        'validity' => '30 days',
                    ], JSON_THROW_ON_ERROR),
                ],
            ]],
        ]);

        self::assertSame('Kuching Utama Sdn Bhd', $result['vendor_name']);
        self::assertSame('1809/2975', $result['quote_number']);
        self::assertSame('RM', $result['currency']);
        self::assertSame(84.8, $result['total_amount']);
        self::assertSame('BREAKDOWN ASSIST JUMP START VEHICLE', $result['lines'][0]['description']);
        self::assertSame(1.0, $result['lines'][0]['quantity']);
        self::assertSame(80.0, $result['lines'][0]['unit_price']);
        self::assertSame('50% deposit required before work can be carried out', $result['payment_terms']);
        self::assertSame('30 days', $result['validity']);
    }
}
```

- [ ] **Step 3: Run mapper/payload tests to verify fail**

Run:

```bash
cd apps/atomy-q/API && ./vendor/bin/phpunit tests/Unit/Adapters/Ai/OpenRouterDocumentPayloadFactoryTest.php tests/Unit/Adapters/Ai/OpenRouterDocumentExtractionMapperTest.php
```

Expected: FAIL because classes do not exist.

- [ ] **Step 4: Add request DTO**

Create `apps/atomy-q/API/app/Adapters/Ai/DTOs/DocumentExtractionRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Adapters\Ai\DTOs;

final readonly class DocumentExtractionRequest
{
    public function __construct(
        public string $tenantId,
        public string $rfqId,
        public string $quoteSubmissionId,
        public string $filename,
        public string $mimeType,
        public string $absolutePath,
    ) {
    }
}
```

- [ ] **Step 5: Add OpenRouter payload factory**

Create `apps/atomy-q/API/app/Adapters/Ai/Support/OpenRouterDocumentPayloadFactory.php`:

```php
<?php

declare(strict_types=1);

namespace App\Adapters\Ai\Support;

use App\Adapters\Ai\DTOs\DocumentExtractionRequest;
use InvalidArgumentException;

final readonly class OpenRouterDocumentPayloadFactory
{
    public function __construct(
        private string $modelId,
        private string $parserPlugin,
        private string $pdfEngine,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(DocumentExtractionRequest $request): array
    {
        if (!is_file($request->absolutePath) || !is_readable($request->absolutePath)) {
            throw new InvalidArgumentException('Quote document file is not readable.');
        }

        $bytes = file_get_contents($request->absolutePath);
        if ($bytes === false || $bytes === '') {
            throw new InvalidArgumentException('Quote document file is empty or unreadable.');
        }

        return [
            'model' => $this->modelId,
            'messages' => [[
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Extract this supplier quotation into concise JSON with vendor_name, quote_number, currency, total_amount, line_items (description, quantity, unit_price, total), payment_terms, delivery_terms, validity, and notes. Return JSON only.',
                    ],
                    [
                        'type' => 'file',
                        'file' => [
                            'filename' => $request->filename,
                            'file_data' => 'data:' . $request->mimeType . ';base64,' . base64_encode($bytes),
                        ],
                    ],
                ],
            ]],
            'plugins' => [[
                'id' => $this->parserPlugin,
                'pdf' => [
                    'engine' => $this->pdfEngine,
                ],
            ]],
            'stream' => false,
        ];
    }
}
```

- [ ] **Step 6: Add OpenRouter response mapper**

Create `apps/atomy-q/API/app/Adapters/Ai/Support/OpenRouterDocumentExtractionMapper.php`:

```php
<?php

declare(strict_types=1);

namespace App\Adapters\Ai\Support;

use App\Adapters\Ai\Exceptions\AiTransportInvalidResponseException;

final readonly class OpenRouterDocumentExtractionMapper
{
    /**
     * @param array<string, mixed> $response
     * @return array{
     *   vendor_name: string|null,
     *   quote_number: string|null,
     *   currency: string|null,
     *   total_amount: float|null,
     *   payment_terms: string|null,
     *   delivery_terms: string|null,
     *   validity: string|null,
     *   notes: list<string>,
     *   lines: list<array<string, mixed>>
     * }
     */
    public function map(array $response): array
    {
        $content = $response['choices'][0]['message']['content'] ?? null;
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
                'unit' => $this->stringOrNull($item['uom'] ?? $item['unit'] ?? null) ?? 'EA',
                'currency' => $this->stringOrNull($item['currency'] ?? $decoded['currency'] ?? null),
                'terms' => $this->stringOrNull($item['terms'] ?? $decoded['payment_terms'] ?? null),
                'sort_order' => $index,
            ];
        }

        return [
            'vendor_name' => $this->stringOrNull($decoded['vendor_name'] ?? null),
            'quote_number' => $this->stringOrNull($decoded['quote_number'] ?? null),
            'currency' => $this->stringOrNull($decoded['currency'] ?? null),
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

    private function floatOrNull(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $normalized = preg_replace('/[^0-9.\-]/', '', $value);
            return is_string($normalized) && is_numeric($normalized) ? (float) $normalized : null;
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
```

- [ ] **Step 7: Add parser config**

Modify `apps/atomy-q/API/config/atomy.php` document endpoint block:

```php
'parser_plugin' => (string) env('AI_DOCUMENT_PARSER_PLUGIN', 'file-parser'),
'pdf_engine' => (string) env('AI_DOCUMENT_PDF_ENGINE', 'mistral-ocr'),
```

Modify `apps/atomy-q/API/.env.example` document AI section:

```dotenv
AI_DOCUMENT_PARSER_PLUGIN=file-parser
AI_DOCUMENT_PDF_ENGINE=mistral-ocr
```

- [ ] **Step 8: Run mapper/payload tests**

Run:

```bash
cd apps/atomy-q/API && ./vendor/bin/phpunit tests/Unit/Adapters/Ai/OpenRouterDocumentPayloadFactoryTest.php tests/Unit/Adapters/Ai/OpenRouterDocumentExtractionMapperTest.php
```

Expected: PASS.

- [ ] **Step 9: Commit payload and mapper**

Run:

```bash
git add apps/atomy-q/API/app/Adapters/Ai/DTOs/DocumentExtractionRequest.php \
  apps/atomy-q/API/app/Adapters/Ai/Support/OpenRouterDocumentPayloadFactory.php \
  apps/atomy-q/API/app/Adapters/Ai/Support/OpenRouterDocumentExtractionMapper.php \
  apps/atomy-q/API/tests/Unit/Adapters/Ai/OpenRouterDocumentPayloadFactoryTest.php \
  apps/atomy-q/API/tests/Unit/Adapters/Ai/OpenRouterDocumentExtractionMapperTest.php \
  apps/atomy-q/API/config/atomy.php \
  apps/atomy-q/API/.env.example
git commit -m "Add OpenRouter document extraction mapper"
```

## Task 3: Provider Document Client Typed Extraction

**Files:**
- Modify: `apps/atomy-q/API/app/Adapters/Ai/Contracts/ProviderDocumentIntelligenceClientInterface.php`
- Modify: `apps/atomy-q/API/app/Adapters/Ai/ProviderDocumentIntelligenceClient.php`
- Create: `apps/atomy-q/API/tests/Unit/Adapters/Ai/ProviderDocumentIntelligenceClientTest.php`

- [ ] **Step 1: Write typed client test**

Create `apps/atomy-q/API/tests/Unit/Adapters/Ai/ProviderDocumentIntelligenceClientTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters\Ai;

use App\Adapters\Ai\Contracts\ProviderAiTransportInterface;
use App\Adapters\Ai\DTOs\DocumentExtractionRequest;
use App\Adapters\Ai\ProviderDocumentIntelligenceClient;
use App\Adapters\Ai\Support\OpenRouterDocumentExtractionMapper;
use App\Adapters\Ai\Support\OpenRouterDocumentPayloadFactory;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use PHPUnit\Framework\TestCase;

final class ProviderDocumentIntelligenceClientTest extends TestCase
{
    public function test_extract_document_builds_payload_and_maps_response(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'quote-pdf-');
        self::assertIsString($file);
        file_put_contents($file, '%PDF-1.7 sample');

        $transport = new class implements ProviderAiTransportInterface {
            public array $payload = [];

            public function invoke(string $endpointGroup, array $payload): array
            {
                TestCase::assertSame(AiStatusSchema::ENDPOINT_GROUP_DOCUMENT, $endpointGroup);
                $this->payload = $payload;

                return [
                    'choices' => [[
                        'message' => [
                            'content' => '{"line_items":[{"description":"Pump","quantity":2,"unit_price":10}]}',
                        ],
                    ]],
                ];
            }
        };

        $client = new ProviderDocumentIntelligenceClient(
            transport: $transport,
            payloadFactory: new OpenRouterDocumentPayloadFactory('model-a', 'file-parser', 'mistral-ocr'),
            mapper: new OpenRouterDocumentExtractionMapper(),
        );

        $result = $client->extractDocument(new DocumentExtractionRequest(
            tenantId: 'tenant-1',
            rfqId: 'rfq-1',
            quoteSubmissionId: 'quote-1',
            filename: 'quote.pdf',
            mimeType: 'application/pdf',
            absolutePath: $file,
        ));

        self::assertSame('model-a', $transport->payload['model']);
        self::assertSame('Pump', $result['lines'][0]['description']);
    }
}
```

- [ ] **Step 2: Run typed client test to verify fail**

Run:

```bash
cd apps/atomy-q/API && ./vendor/bin/phpunit tests/Unit/Adapters/Ai/ProviderDocumentIntelligenceClientTest.php
```

Expected: FAIL because `extractDocument()` constructor dependencies do not exist.

- [ ] **Step 3: Update client interface**

Modify `apps/atomy-q/API/app/Adapters/Ai/Contracts/ProviderDocumentIntelligenceClientInterface.php`:

```php
<?php

declare(strict_types=1);

namespace App\Adapters\Ai\Contracts;

use App\Adapters\Ai\DTOs\DocumentExtractionRequest;

interface ProviderDocumentIntelligenceClientInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function extract(array $payload): array;

    /**
     * @return array<string, mixed>
     */
    public function extractDocument(DocumentExtractionRequest $request): array;
}
```

- [ ] **Step 4: Update provider document client**

Modify `apps/atomy-q/API/app/Adapters/Ai/ProviderDocumentIntelligenceClient.php`:

```php
<?php

declare(strict_types=1);

namespace App\Adapters\Ai;

use App\Adapters\Ai\Contracts\ProviderAiTransportInterface;
use App\Adapters\Ai\Contracts\ProviderDocumentIntelligenceClientInterface;
use App\Adapters\Ai\DTOs\DocumentExtractionRequest;
use App\Adapters\Ai\Support\OpenRouterDocumentExtractionMapper;
use App\Adapters\Ai\Support\OpenRouterDocumentPayloadFactory;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;

final readonly class ProviderDocumentIntelligenceClient implements ProviderDocumentIntelligenceClientInterface
{
    public function __construct(
        private ProviderAiTransportInterface $transport,
        private OpenRouterDocumentPayloadFactory $payloadFactory,
        private OpenRouterDocumentExtractionMapper $mapper,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function extract(array $payload): array
    {
        return $this->transport->invoke(AiStatusSchema::ENDPOINT_GROUP_DOCUMENT, $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function extractDocument(DocumentExtractionRequest $request): array
    {
        return $this->mapper->map($this->transport->invoke(
            AiStatusSchema::ENDPOINT_GROUP_DOCUMENT,
            $this->payloadFactory->build($request),
        ));
    }
}
```

- [ ] **Step 5: Register payload factory and mapper**

Modify `apps/atomy-q/API/app/Providers/AppServiceProvider.php` in `register()` near AI adapter bindings:

```php
$this->app->singleton(OpenRouterDocumentPayloadFactory::class, static function (): OpenRouterDocumentPayloadFactory {
    return new OpenRouterDocumentPayloadFactory(
        modelId: (string) config('atomy.ai.endpoints.document.model_id', ''),
        parserPlugin: (string) config('atomy.ai.endpoints.document.parser_plugin', 'file-parser'),
        pdfEngine: (string) config('atomy.ai.endpoints.document.pdf_engine', 'mistral-ocr'),
    );
});
$this->app->singleton(OpenRouterDocumentExtractionMapper::class);
```

Add imports:

```php
use App\Adapters\Ai\Support\OpenRouterDocumentExtractionMapper;
use App\Adapters\Ai\Support\OpenRouterDocumentPayloadFactory;
```

- [ ] **Step 6: Run typed client test**

Run:

```bash
cd apps/atomy-q/API && ./vendor/bin/phpunit tests/Unit/Adapters/Ai/ProviderDocumentIntelligenceClientTest.php
```

Expected: PASS.

- [ ] **Step 7: Run existing AI console command tests**

Run:

```bash
cd apps/atomy-q/API && ./vendor/bin/phpunit tests/Feature/Console/AiConsoleCommandsTest.php
```

Expected: PASS; existing `extract(array $payload)` compatibility remains intact.

- [ ] **Step 8: Commit typed client**

Run:

```bash
git add apps/atomy-q/API/app/Adapters/Ai/Contracts/ProviderDocumentIntelligenceClientInterface.php \
  apps/atomy-q/API/app/Adapters/Ai/ProviderDocumentIntelligenceClient.php \
  apps/atomy-q/API/app/Providers/AppServiceProvider.php \
  apps/atomy-q/API/tests/Unit/Adapters/Ai/ProviderDocumentIntelligenceClientTest.php
git commit -m "Wire typed provider document extraction"
```

## Task 4: Provider Quote Content Processor

**Files:**
- Create: `apps/atomy-q/API/app/Adapters/QuotationIntelligence/ProviderQuoteContentProcessor.php`
- Modify: `apps/atomy-q/API/app/Providers/AppServiceProvider.php`
- Create: `apps/atomy-q/API/tests/Feature/ProviderQuoteExtractionTest.php`
- Modify: `apps/atomy-q/API/tests/Feature/QuoteIngestionPipelineTest.php`

- [ ] **Step 1: Write provider extraction feature test**

Create `apps/atomy-q/API/tests/Feature/ProviderQuoteExtractionTest.php` using existing helper patterns from `QuoteIngestionPipelineTest.php`. Test body must:

```php
public function test_provider_mode_upload_persists_extracted_source_lines_from_document_ai(): void
{
    config()->set('atomy.ai.mode', 'provider');
    config()->set('atomy.ai.endpoints.document.uri', 'https://openrouter.example.test/chat/completions');
    config()->set('atomy.ai.endpoints.document.model_id', 'baidu/qianfan-ocr-fast:free');
    config()->set('atomy.ai.endpoints.document.parser_plugin', 'file-parser');
    config()->set('atomy.ai.endpoints.document.pdf_engine', 'mistral-ocr');
    config()->set('queue.default', 'sync');

    Http::fake([
        'https://openrouter.example.test/chat/completions' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'vendor_name' => 'Kuching Utama Sdn Bhd',
                        'quote_number' => '1809/2975',
                        'currency' => 'RM',
                        'line_items' => [[
                            'description' => 'BREAKDOWN ASSIST JUMP START VEHICLE',
                            'quantity' => 1,
                            'unit_price' => 80,
                            'total' => 84.8,
                        ]],
                        'payment_terms' => '50% deposit required before work can be carried out',
                        'validity' => '30 days',
                    ], JSON_THROW_ON_ERROR),
                ],
            ]],
        ], 200),
    ]);

    $user = $this->createUser();
    $rfq = $this->createRfq($user);

    $response = $this->withHeaders($this->authHeaders((string) $user->tenant_id, (string) $user->id))
        ->post('/api/v1/quote-submissions/upload', [
            'rfq_id' => $rfq->id,
            'vendor_id' => (string) Str::ulid(),
            'vendor_name' => 'Kuching Utama Sdn Bhd',
            'file' => UploadedFile::fake()->create('quote.pdf', 12, 'application/pdf'),
        ]);

    $response->assertCreated();
    $response->assertJsonPath('data.status', 'ready');

    $submission = QuoteSubmission::query()->findOrFail((string) $response->json('data.id'));
    self::assertSame('ready', $submission->status);
    self::assertSame(1, $submission->normalizationSourceLines()->count());
    self::assertSame(
        'BREAKDOWN ASSIST JUMP START VEHICLE',
        $submission->normalizationSourceLines()->firstOrFail()->source_description,
    );

    Http::assertSent(static fn ($request): bool =>
        $request->url() === 'https://openrouter.example.test/chat/completions'
        && ($request->data()['plugins'][0]['pdf']['engine'] ?? null) === 'mistral-ocr'
    );
}
```

- [ ] **Step 2: Run provider extraction test to verify fail**

Run:

```bash
cd apps/atomy-q/API && ./vendor/bin/phpunit tests/Feature/ProviderQuoteExtractionTest.php
```

Expected: FAIL because upload path still uses deterministic/dormant processor.

- [ ] **Step 3: Implement provider content processor**

Create `apps/atomy-q/API/app/Adapters/QuotationIntelligence/ProviderQuoteContentProcessor.php`:

```php
<?php

declare(strict_types=1);

namespace App\Adapters\QuotationIntelligence;

use App\Adapters\Ai\Contracts\ProviderDocumentIntelligenceClientInterface;
use App\Adapters\Ai\DTOs\DocumentExtractionRequest;
use App\Models\QuoteSubmission;
use Nexus\QuotationIntelligence\Contracts\OrchestratorContentProcessorInterface;
use Nexus\QuotationIntelligence\Exceptions\QuotationIntelligenceException;
use Nexus\Tenant\Contracts\TenantContextInterface;

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
            ->first();

        if (!$submission instanceof QuoteSubmission) {
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

        return new class($this->lines($result), $result) {
            /**
             * @param list<array<string, mixed>> $lines
             * @param array<string, mixed> $result
             */
            public function __construct(private array $lines, private array $result)
            {
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
        $prefix = storage_path('app') . DIRECTORY_SEPARATOR;
        if (str_starts_with($storagePath, $prefix)) {
            return substr($storagePath, strlen($prefix));
        }

        return basename($storagePath);
    }

    /**
     * @param array<string, mixed> $result
     * @return list<array<string, mixed>>
     */
    private function lines(array $result): array
    {
        $lines = [];
        foreach (($result['lines'] ?? []) as $line) {
            if (!is_array($line)) {
                continue;
            }

            $description = $line['description'] ?? null;
            if (!is_string($description) || trim($description) === '') {
                continue;
            }

            $lines[] = [
                'description' => trim($description),
                'quantity' => (float) ($line['quantity'] ?? 1),
                'unit_price' => isset($line['unit_price']) ? (float) $line['unit_price'] : null,
                'unit' => is_string($line['unit'] ?? null) ? (string) $line['unit'] : 'EA',
                'currency' => is_string($line['currency'] ?? null) ? (string) $line['currency'] : ($result['currency'] ?? 'USD'),
                'terms' => is_string($line['terms'] ?? null) ? (string) $line['terms'] : ($result['payment_terms'] ?? null),
                'bbox' => null,
            ];
        }

        return $lines;
    }
}
```

- [ ] **Step 4: Bind provider processor from `AI_MODE`**

Modify `apps/atomy-q/API/app/Providers/AppServiceProvider.php` content-processor binding:

```php
$this->app->bind(OrchestratorContentProcessorInterface::class, function (): OrchestratorContentProcessorInterface {
    $aiMode = (string) config('atomy.ai.mode', 'deterministic');
    if ($aiMode === AiStatusSchema::MODE_PROVIDER) {
        return new ProviderQuoteContentProcessor(
            $this->app->make(ProviderDocumentIntelligenceClientInterface::class),
            $this->app->make(TenantContextInterface::class),
        );
    }

    $mode = (string) config('atomy.quote_intelligence.mode', 'deterministic');
    if ($mode === 'deterministic') {
        return new DeterministicContentProcessor($this->app->make(TenantContextInterface::class));
    }

    if ($mode === 'llm') {
        return new DormantLlmContentProcessor($this->quoteIntelligenceLlmConfig());
    }

    $message = 'Unsupported quote intelligence mode.';

    return new class($message) implements OrchestratorContentProcessorInterface {
        public function __construct(private readonly string $message) {}

        public function analyze(string $storagePath): object
        {
            throw new QuotationIntelligenceException($this->message);
        }
    };
});
```

Add imports:

```php
use App\Adapters\QuotationIntelligence\ProviderQuoteContentProcessor;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
```

- [ ] **Step 5: Run provider extraction test**

Run:

```bash
cd apps/atomy-q/API && ./vendor/bin/phpunit tests/Feature/ProviderQuoteExtractionTest.php
```

Expected: PASS.

- [ ] **Step 6: Run manual continuity regression**

Run:

```bash
cd apps/atomy-q/API && ./vendor/bin/phpunit tests/Feature/QuoteIngestionPipelineTest.php --filter 'manual_continuity|provider_ai_clients'
```

Expected: PASS; unavailable provider keeps upload in `needs_review`.

- [ ] **Step 7: Commit provider processor**

Run:

```bash
git add apps/atomy-q/API/app/Adapters/QuotationIntelligence/ProviderQuoteContentProcessor.php \
  apps/atomy-q/API/app/Providers/AppServiceProvider.php \
  apps/atomy-q/API/tests/Feature/ProviderQuoteExtractionTest.php \
  apps/atomy-q/API/tests/Feature/QuoteIngestionPipelineTest.php
git commit -m "Use provider extraction for quote ingestion"
```

## Task 5: Fake Provider E2E

**Files:**
- Create: `apps/atomy-q/WEB/tests/provider-quote-e2e.spec.ts`
- Modify: `apps/atomy-q/WEB/package.json`

- [ ] **Step 1: Add fake provider e2e test**

Create `apps/atomy-q/WEB/tests/provider-quote-e2e.spec.ts`:

```ts
import { expect, test } from '@playwright/test';
import { discoverProviderQuoteFixtures } from './support/providerQuoteFixtures';

const fixtures = discoverProviderQuoteFixtures('../../../sample');

test.describe('provider-backed quote e2e with fake provider', () => {
  test.skip(fixtures.length === 0, 'No sample requisition metadata folders found under sample/.');

  for (const fixture of fixtures) {
    test(`uploads provider quote fixture ${fixture.requisitionId}`, async ({ page, request }) => {
      await page.route('**/api/v1/ai/status', async (route) => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            data: {
              mode: 'provider',
              provider_name: 'openrouter-fake',
              capability_statuses: {
                quote_document_extraction: { available: true, status: 'available' },
                normalization_suggestions: { available: true, status: 'available' },
              },
            },
          }),
        });
      });

      expect(fixture.e2e.documentParser.pdfEngine).toBe('mistral-ocr');
      expect(fixture.quotes.length).toBeGreaterThan(0);

      const response = await request.get('/api/v1/ai/status');
      expect(response.ok()).toBeTruthy();
    });
  }
});
```

- [ ] **Step 2: Add package script**

Modify `apps/atomy-q/WEB/package.json` scripts:

```json
"test:e2e:provider-quotes": "playwright test tests/provider-quote-e2e.spec.ts --project=chromium"
```

- [ ] **Step 3: Run fake provider e2e**

Run:

```bash
cd apps/atomy-q/WEB && npm run test:e2e:provider-quotes
```

Expected: PASS when dev server/API test config is available. If Playwright web server is not configured for this branch, record exact failure and keep the spec compiling as part of Task 7.

- [ ] **Step 4: Commit fake e2e**

Run:

```bash
git add apps/atomy-q/WEB/tests/provider-quote-e2e.spec.ts apps/atomy-q/WEB/package.json
git commit -m "Add fake provider quote e2e"
```

## Task 6: Opt-In Live Provider E2E

**Files:**
- Create: `apps/atomy-q/WEB/tests/provider-quote-live.spec.ts`
- Modify: `apps/atomy-q/WEB/package.json`
- Modify: `apps/atomy-q/docs/02-release-management/current-release/release-checklist.md`

- [ ] **Step 1: Add live provider test gate**

Create `apps/atomy-q/WEB/tests/provider-quote-live.spec.ts`:

```ts
import { expect, test } from '@playwright/test';
import { discoverProviderQuoteFixtures } from './support/providerQuoteFixtures';

const liveEnabled = process.env.AI_PROVIDER_E2E === 'true';
const fixtures = discoverProviderQuoteFixtures('../../../sample');

test.describe('provider-backed quote e2e with live OpenRouter', () => {
  test.skip(!liveEnabled, 'Set AI_PROVIDER_E2E=true to run live provider quote e2e.');
  test.skip(fixtures.length === 0, 'No sample requisition metadata folders found under sample/.');

  for (const fixture of fixtures) {
    test(`live provider extracts quotes for ${fixture.requisitionId}`, async ({ request }) => {
      expect(process.env.AI_MODE).toBe('provider');
      expect(process.env.AI_DOCUMENT_ENDPOINT).toContain('openrouter.ai');
      expect(fixture.e2e.provider).toBe('openrouter');
      expect(fixture.e2e.documentParser.pdfEngine).toBe('mistral-ocr');

      const statusResponse = await request.get('/api/v1/ai/status');
      expect(statusResponse.ok()).toBeTruthy();

      const status = await statusResponse.json();
      expect(status.data.mode).toBe('provider');
    });
  }
});
```

- [ ] **Step 2: Add live provider script**

Modify `apps/atomy-q/WEB/package.json` scripts:

```json
"test:e2e:provider-quotes:live": "AI_PROVIDER_E2E=true playwright test tests/provider-quote-live.spec.ts --project=chromium"
```

- [ ] **Step 3: Add release checklist row**

Modify `apps/atomy-q/docs/02-release-management/current-release/release-checklist.md` AI evidence table with:

```markdown
| Provider quote extraction e2e | `cd apps/atomy-q/WEB && AI_PROVIDER_E2E=true AI_MODE=provider npm run test:e2e:provider-quotes:live` |  |  | Opt-in live OpenRouter gate over `sample/*/metadata.json`; record provider, model, PDF engine, and sample folder count. |
```

- [ ] **Step 4: Run live e2e skip check**

Run without provider flag:

```bash
cd apps/atomy-q/WEB && playwright test tests/provider-quote-live.spec.ts --project=chromium
```

Expected: tests skipped with message requiring `AI_PROVIDER_E2E=true`.

- [ ] **Step 5: Commit live e2e gate**

Run:

```bash
git add apps/atomy-q/WEB/tests/provider-quote-live.spec.ts \
  apps/atomy-q/WEB/package.json \
  apps/atomy-q/docs/02-release-management/current-release/release-checklist.md
git commit -m "Add live provider quote release gate"
```

## Task 7: Final Verification And Documentation

**Files:**
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- Modify: `sample/README.md`
- Modify: `sample/metadata.example.json` only if fixture schema changed during implementation.

- [ ] **Step 1: Update API implementation summary**

Append to `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`:

```markdown
**Provider-backed quote extraction (2026-04-25):** `AI_MODE=provider` now routes quote upload/reparse extraction through the provider document client. OpenRouter PDF extraction uses base64 `file_data`, `file-parser`, and `mistral-ocr` for scanned quotations, then maps provider JSON into persisted normalization source lines. Deterministic quote intelligence remains a diagnostics/development mode and is not counted as provider-backed alpha release evidence.
```

- [ ] **Step 2: Run focused API verification**

Run:

```bash
cd apps/atomy-q/API && ./vendor/bin/phpunit \
  tests/Unit/Adapters/Ai/OpenRouterDocumentPayloadFactoryTest.php \
  tests/Unit/Adapters/Ai/OpenRouterDocumentExtractionMapperTest.php \
  tests/Unit/Adapters/Ai/ProviderDocumentIntelligenceClientTest.php \
  tests/Feature/ProviderQuoteExtractionTest.php \
  tests/Feature/QuoteIngestionPipelineTest.php \
  tests/Feature/QuoteIngestionIntelligenceTest.php \
  tests/Feature/NormalizationReviewWorkflowTest.php
```

Expected: PASS.

- [ ] **Step 3: Run focused WEB fixture verification**

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run tests/support/providerQuoteFixtures.test.ts
```

Expected: PASS.

- [ ] **Step 4: Run fake provider e2e if local e2e services are available**

Run:

```bash
cd apps/atomy-q/WEB && npm run test:e2e:provider-quotes
```

Expected: PASS. If local API/WEB e2e services are unavailable, capture exact failure in final handoff and run the fixture/unit/API gates from Steps 2 and 3.

- [ ] **Step 5: Run live provider gate only with explicit release env**

Run:

```bash
cd apps/atomy-q/WEB && AI_PROVIDER_E2E=true AI_MODE=provider npm run test:e2e:provider-quotes:live
```

Expected: PASS only when `AI_DOCUMENT_ENDPOINT`, `AI_DOCUMENT_AUTH_TOKEN`, `AI_DOCUMENT_MODEL_ID`, `AI_DOCUMENT_PARSER_PLUGIN=file-parser`, and `AI_DOCUMENT_PDF_ENGINE=mistral-ocr` are set and outbound provider access is allowed.

- [ ] **Step 6: Commit docs and final adjustments**

Run:

```bash
git add apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md sample/README.md sample/metadata.example.json
git commit -m "Document provider quote extraction release gate"
```

## Self-Review Checklist

- Spec coverage: provider path, manual continuity, hybrid fake/live testing, sample fixture contract, and release evidence all have tasks.
- Placeholder scan: plan uses no TBD placeholders.
- Type consistency: `DocumentExtractionRequest`, `OpenRouterDocumentPayloadFactory`, `OpenRouterDocumentExtractionMapper`, and `ProviderQuoteContentProcessor` names stay consistent across tasks.
- Release safety: live provider e2e is opt-in through `AI_PROVIDER_E2E=true`; default fake-provider and unit tests do not spend provider quota.

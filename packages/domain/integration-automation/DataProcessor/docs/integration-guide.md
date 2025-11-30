# Integration Guide: DataProcessor

**Package:** `Nexus\DataProcessor`  
**Audience:** Application developers implementing vendor OCR adapters

---

## Overview

This guide demonstrates how to integrate the Nexus\DataProcessor package into your application layer with concrete vendor implementations.

**Key Concept:** This package provides **only interfaces**. You must create vendor-specific adapters in your application layer (e.g., `apps/Atomy`).

---

## Table of Contents

1. [Laravel Integration](#laravel-integration)
2. [Symfony Integration](#symfony-integration)
3. [Vendor Adapters](#vendor-adapters)
   - [Azure Form Recognizer](#azure-form-recognizer-adapter)
   - [AWS Textract](#aws-textract-adapter)
   - [Google Vision API](#google-vision-api-adapter)
4. [Advanced Patterns](#advanced-patterns)
5. [Testing Strategies](#testing-strategies)

---

## Laravel Integration

### Step 1: Install Vendor SDK

**For Azure:**
```bash
composer require azure/azure-ai-formrecognizer
```

**For AWS:**
```bash
composer require aws/aws-sdk-php
```

**For Google:**
```bash
composer require google/cloud-vision
```

### Step 2: Create Service Provider

**File:** `apps/Atomy/app/Providers/DataProcessorServiceProvider.php`

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\DataProcessor\Contracts\DocumentRecognizerInterface;
use App\Services\DataProcessor\AzureFormRecognizerAdapter;
use Azure\AI\FormRecognizer\DocumentAnalysisClient;
use Azure\Core\Credential\AzureKeyCredential;

final class DataProcessorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind Azure Client
        $this->app->singleton(DocumentAnalysisClient::class, function () {
            return new DocumentAnalysisClient(
                endpoint: config('services.azure.form_recognizer.endpoint'),
                credential: new AzureKeyCredential(
                    config('services.azure.form_recognizer.key')
                )
            );
        });

        // Bind DocumentRecognizerInterface to Azure Adapter
        $this->app->singleton(
            DocumentRecognizerInterface::class,
            AzureFormRecognizerAdapter::class
        );
    }

    public function boot(): void
    {
        // Optional: Publish configuration
        $this->publishes([
            __DIR__ . '/../../config/dataprocessor.php' => config_path('dataprocessor.php'),
        ], 'config');
    }
}
```

### Step 3: Register Service Provider

**File:** `apps/Atomy/bootstrap/providers.php` (Laravel 11+)

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\DataProcessorServiceProvider::class, // Add this
];
```

**Or in `config/app.php` (Laravel 10):**
```php
'providers' => ServiceProvider::defaultProviders()->merge([
    // ...
    App\Providers\DataProcessorServiceProvider::class,
])->toArray(),
```

### Step 4: Configure Services

**File:** `apps/Atomy/config/services.php`

```php
return [
    // ... other services

    'azure' => [
        'form_recognizer' => [
            'endpoint' => env('AZURE_FORM_RECOGNIZER_ENDPOINT'),
            'key' => env('AZURE_FORM_RECOGNIZER_KEY'),
        ],
    ],
];
```

**File:** `apps/Atomy/.env`

```env
AZURE_FORM_RECOGNIZER_ENDPOINT=https://your-resource.cognitiveservices.azure.com/
AZURE_FORM_RECOGNIZER_KEY=your-32-character-azure-key
```

### Step 5: Use in Controllers/Services

**File:** `apps/Atomy/app/Http/Controllers/VendorBillController.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Nexus\DataProcessor\Contracts\DocumentRecognizerInterface;
use Nexus\Payable\Contracts\VendorBillManagerInterface;

final readonly class VendorBillController
{
    public function __construct(
        private DocumentRecognizerInterface $ocr,
        private VendorBillManagerInterface $billManager
    ) {}

    public function uploadInvoice(Request $request)
    {
        $request->validate([
            'invoice_file' => 'required|file|mimes:pdf,png,jpg|max:10240',
        ]);

        $filePath = $request->file('invoice_file')->store('invoices');

        try {
            $result = $this->ocr->recognizeDocument(
                storage_path('app/' . $filePath),
                'invoice'
            );

            if ($result->getConfidence() < 80) {
                return response()->json([
                    'status' => 'needs_review',
                    'data' => $result->getExtractedData(),
                    'confidence' => $result->getConfidence(),
                    'message' => 'Low confidence - manual review required',
                ]);
            }

            $billId = $this->billManager->createFromOcrResult($result);

            return response()->json([
                'status' => 'success',
                'bill_id' => $billId,
                'confidence' => $result->getConfidence(),
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
```

---

## Symfony Integration

### Step 1: Create Adapter Service

**File:** `apps/Atomy/src/Service/DataProcessor/AzureFormRecognizerAdapter.php`

*(Same as Laravel example - adapter is framework-agnostic)*

### Step 2: Configure Service Container

**File:** `apps/Atomy/config/services.yaml`

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    # Azure Client
    Azure\AI\FormRecognizer\DocumentAnalysisClient:
        factory: ['App\Factory\AzureClientFactory', 'create']
        arguments:
            $endpoint: '%env(AZURE_FORM_RECOGNIZER_ENDPOINT)%'
            $key: '%env(AZURE_FORM_RECOGNIZER_KEY)%'

    # DocumentRecognizerInterface → Azure Adapter
    Nexus\DataProcessor\Contracts\DocumentRecognizerInterface:
        class: App\Service\DataProcessor\AzureFormRecognizerAdapter
        arguments:
            $client: '@Azure\AI\FormRecognizer\DocumentAnalysisClient'
```

### Step 3: Create Azure Client Factory

**File:** `apps/Atomy/src/Factory/AzureClientFactory.php`

```php
<?php

declare(strict_types=1);

namespace App\Factory;

use Azure\AI\FormRecognizer\DocumentAnalysisClient;
use Azure\Core\Credential\AzureKeyCredential;

final class AzureClientFactory
{
    public static function create(string $endpoint, string $key): DocumentAnalysisClient
    {
        return new DocumentAnalysisClient(
            $endpoint,
            new AzureKeyCredential($key)
        );
    }
}
```

### Step 4: Use in Controllers

**File:** `apps/Atomy/src/Controller/VendorBillController.php`

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Nexus\DataProcessor\Contracts\DocumentRecognizerInterface;

final class VendorBillController extends AbstractController
{
    public function __construct(
        private readonly DocumentRecognizerInterface $ocr
    ) {}

    #[Route('/api/bills/upload', methods: ['POST'])]
    public function uploadInvoice(Request $request): JsonResponse
    {
        $file = $request->files->get('invoice_file');
        $filePath = $file->getRealPath();

        $result = $this->ocr->recognizeDocument($filePath, 'invoice');

        return $this->json([
            'data' => $result->getExtractedData(),
            'confidence' => $result->getConfidence(),
        ]);
    }
}
```

---

## Vendor Adapters

### Azure Form Recognizer Adapter

**File:** `apps/Atomy/app/Services/DataProcessor/AzureFormRecognizerAdapter.php`

```php
<?php

declare(strict_types=1);

namespace App\Services\DataProcessor;

use Azure\AI\FormRecognizer\DocumentAnalysisClient;
use Nexus\DataProcessor\Contracts\DocumentRecognizerInterface;
use Nexus\DataProcessor\ValueObjects\ProcessingResult;
use Nexus\DataProcessor\Exceptions\ProcessingFailedException;
use Nexus\DataProcessor\Exceptions\UnsupportedDocumentTypeException;

final readonly class AzureFormRecognizerAdapter implements DocumentRecognizerInterface
{
    private const DOCUMENT_TYPE_MODELS = [
        'invoice' => 'prebuilt-invoice',
        'receipt' => 'prebuilt-receipt',
        'id' => 'prebuilt-idDocument',
        'business_card' => 'prebuilt-businessCard',
        'tax_form_w2' => 'prebuilt-tax.us.w2',
    ];

    public function __construct(
        private DocumentAnalysisClient $client
    ) {}

    public function recognizeDocument(
        string $filePath,
        string $documentType,
        array $options = []
    ): ProcessingResult {
        if (!$this->supportsDocumentType($documentType)) {
            throw new UnsupportedDocumentTypeException(
                "Document type '{$documentType}' is not supported by Azure Form Recognizer"
            );
        }

        $modelId = self::DOCUMENT_TYPE_MODELS[$documentType];

        try {
            // Start document analysis
            $poller = $this->client->beginAnalyzeDocument(
                $modelId,
                fopen($filePath, 'r'),
                $options
            );

            // Wait for completion
            $result = $poller->pollUntilComplete();

            return new ProcessingResult(
                extractedData: $this->transformToStandardFormat($result),
                confidence: $this->calculateOverallConfidence($result),
                fieldConfidences: $this->extractFieldConfidences($result),
                warnings: $this->extractWarnings($result)
            );

        } catch (\Azure\Core\Exception\HttpException $e) {
            throw new ProcessingFailedException(
                "Azure API error: {$e->getMessage()}",
                previous: $e
            );
        } catch (\Throwable $e) {
            throw new ProcessingFailedException(
                "Failed to process document: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    public function getSupportedDocumentTypes(): array
    {
        return array_keys(self::DOCUMENT_TYPE_MODELS);
    }

    public function supportsDocumentType(string $documentType): bool
    {
        return isset(self::DOCUMENT_TYPE_MODELS[$documentType]);
    }

    private function transformToStandardFormat($result): array
    {
        $data = [];

        foreach ($result->getDocuments() as $document) {
            foreach ($document->getFields() as $fieldName => $field) {
                $data[$fieldName] = match ($field->getType()) {
                    'string' => $field->getValueString(),
                    'number' => $field->getValueNumber(),
                    'date' => $field->getValueDate()?->format('Y-m-d'),
                    'array' => $this->transformArrayField($field),
                    default => $field->getContent(),
                };
            }
        }

        return $data;
    }

    private function transformArrayField($field): array
    {
        $items = [];
        foreach ($field->getValueArray() as $item) {
            $items[] = $this->transformToStandardFormat($item);
        }
        return $items;
    }

    private function calculateOverallConfidence($result): float
    {
        $confidences = [];

        foreach ($result->getDocuments() as $document) {
            foreach ($document->getFields() as $field) {
                $confidences[] = $field->getConfidence();
            }
        }

        return empty($confidences) ? 0.0 : (array_sum($confidences) / count($confidences)) * 100;
    }

    private function extractFieldConfidences($result): array
    {
        $fieldConfidences = [];

        foreach ($result->getDocuments() as $document) {
            foreach ($document->getFields() as $fieldName => $field) {
                $fieldConfidences[$fieldName] = $field->getConfidence() * 100;
            }
        }

        return $fieldConfidences;
    }

    private function extractWarnings($result): array
    {
        // Azure doesn't provide warnings in current API
        // Add custom logic based on confidence thresholds
        $warnings = [];

        foreach ($result->getDocuments() as $document) {
            foreach ($document->getFields() as $fieldName => $field) {
                if ($field->getConfidence() < 0.7) {
                    $warnings[] = "Low confidence on field '{$fieldName}': " . 
                                 round($field->getConfidence() * 100, 2) . "%";
                }
            }
        }

        return $warnings;
    }
}
```

---

### AWS Textract Adapter

**File:** `apps/Atomy/app/Services/DataProcessor/AwsTextractAdapter.php`

```php
<?php

declare(strict_types=1);

namespace App\Services\DataProcessor;

use Aws\Textract\TextractClient;
use Nexus\DataProcessor\Contracts\DocumentRecognizerInterface;
use Nexus\DataProcessor\ValueObjects\ProcessingResult;
use Nexus\DataProcessor\Exceptions\ProcessingFailedException;
use Nexus\DataProcessor\Exceptions\UnsupportedDocumentTypeException;

final readonly class AwsTextractAdapter implements DocumentRecognizerInterface
{
    private const SUPPORTED_TYPES = [
        'invoice' => 'INVOICES',
        'receipt' => 'RECEIPTS',
        'id' => 'IDENTITY_DOCUMENTS',
    ];

    public function __construct(
        private TextractClient $client
    ) {}

    public function recognizeDocument(
        string $filePath,
        string $documentType,
        array $options = []
    ): ProcessingResult {
        if (!$this->supportsDocumentType($documentType)) {
            throw new UnsupportedDocumentTypeException(
                "Document type '{$documentType}' not supported by AWS Textract"
            );
        }

        try {
            $result = $this->client->analyzeDocument([
                'Document' => [
                    'Bytes' => file_get_contents($filePath),
                ],
                'FeatureTypes' => [
                    'FORMS',
                    'TABLES',
                    self::SUPPORTED_TYPES[$documentType],
                ],
            ]);

            return new ProcessingResult(
                extractedData: $this->transformAwsResult($result),
                confidence: $this->calculateConfidence($result),
                fieldConfidences: $this->extractFieldConfidences($result),
                warnings: []
            );

        } catch (\Aws\Exception\AwsException $e) {
            throw new ProcessingFailedException(
                "AWS Textract error: {$e->getAwsErrorMessage()}",
                previous: $e
            );
        }
    }

    public function getSupportedDocumentTypes(): array
    {
        return array_keys(self::SUPPORTED_TYPES);
    }

    public function supportsDocumentType(string $documentType): bool
    {
        return isset(self::SUPPORTED_TYPES[$documentType]);
    }

    private function transformAwsResult($result): array
    {
        $data = [];

        foreach ($result['Blocks'] ?? [] as $block) {
            if ($block['BlockType'] === 'KEY_VALUE_SET' && isset($block['EntityTypes'])) {
                if (in_array('KEY', $block['EntityTypes'])) {
                    $key = $this->extractText($block, $result['Blocks']);
                    $value = $this->extractValueForKey($block, $result['Blocks']);
                    $data[$key] = $value;
                }
            }
        }

        return $data;
    }

    private function extractText($block, $allBlocks): string
    {
        $text = '';
        foreach ($block['Relationships'] ?? [] as $relationship) {
            if ($relationship['Type'] === 'CHILD') {
                foreach ($relationship['Ids'] as $id) {
                    $childBlock = $this->findBlockById($id, $allBlocks);
                    if ($childBlock && $childBlock['BlockType'] === 'WORD') {
                        $text .= $childBlock['Text'] . ' ';
                    }
                }
            }
        }
        return trim($text);
    }

    private function extractValueForKey($keyBlock, $allBlocks): string
    {
        foreach ($keyBlock['Relationships'] ?? [] as $relationship) {
            if ($relationship['Type'] === 'VALUE') {
                foreach ($relationship['Ids'] as $id) {
                    $valueBlock = $this->findBlockById($id, $allBlocks);
                    if ($valueBlock) {
                        return $this->extractText($valueBlock, $allBlocks);
                    }
                }
            }
        }
        return '';
    }

    private function findBlockById(string $id, array $blocks): ?array
    {
        foreach ($blocks as $block) {
            if ($block['Id'] === $id) {
                return $block;
            }
        }
        return null;
    }

    private function calculateConfidence($result): float
    {
        $confidences = [];
        foreach ($result['Blocks'] ?? [] as $block) {
            if (isset($block['Confidence'])) {
                $confidences[] = $block['Confidence'];
            }
        }
        return empty($confidences) ? 0.0 : array_sum($confidences) / count($confidences);
    }

    private function extractFieldConfidences($result): array
    {
        $fieldConfidences = [];
        foreach ($result['Blocks'] ?? [] as $block) {
            if ($block['BlockType'] === 'KEY_VALUE_SET' && isset($block['Confidence'])) {
                $key = $this->extractText($block, $result['Blocks']);
                $fieldConfidences[$key] = $block['Confidence'];
            }
        }
        return $fieldConfidences;
    }
}
```

---

### Google Vision API Adapter

**File:** `apps/Atomy/app/Services/DataProcessor/GoogleVisionAdapter.php`

```php
<?php

declare(strict_types=1);

namespace App\Services\DataProcessor;

use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\Image;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Feature\Type;
use Nexus\DataProcessor\Contracts\DocumentRecognizerInterface;
use Nexus\DataProcessor\ValueObjects\ProcessingResult;
use Nexus\DataProcessor\Exceptions\ProcessingFailedException;
use Nexus\DataProcessor\Exceptions\UnsupportedDocumentTypeException;

final readonly class GoogleVisionAdapter implements DocumentRecognizerInterface
{
    private const SUPPORTED_TYPES = ['invoice', 'receipt', 'id', 'business_card'];

    public function __construct(
        private ImageAnnotatorClient $client
    ) {}

    public function recognizeDocument(
        string $filePath,
        string $documentType,
        array $options = []
    ): ProcessingResult {
        if (!$this->supportsDocumentType($documentType)) {
            throw new UnsupportedDocumentTypeException(
                "Document type '{$documentType}' not supported"
            );
        }

        try {
            $image = (new Image())->setContent(file_get_contents($filePath));

            $response = $this->client->documentTextDetection($image);

            if ($error = $response->getError()) {
                throw new ProcessingFailedException(
                    "Google Vision API error: {$error->getMessage()}"
                );
            }

            $annotation = $response->getFullTextAnnotation();

            return new ProcessingResult(
                extractedData: $this->extractStructuredData($annotation),
                confidence: $this->calculateConfidence($annotation),
                fieldConfidences: [],
                warnings: []
            );

        } catch (\Google\ApiCore\ApiException $e) {
            throw new ProcessingFailedException(
                "Google Vision API error: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    public function getSupportedDocumentTypes(): array
    {
        return self::SUPPORTED_TYPES;
    }

    public function supportsDocumentType(string $documentType): bool
    {
        return in_array($documentType, self::SUPPORTED_TYPES);
    }

    private function extractStructuredData($annotation): array
    {
        // Basic text extraction - enhance with NLP for structured data
        return [
            'raw_text' => $annotation->getText(),
            // Add custom parsing logic based on document type
        ];
    }

    private function calculateConfidence($annotation): float
    {
        $confidences = [];
        foreach ($annotation->getPages() as $page) {
            foreach ($page->getBlocks() as $block) {
                $confidences[] = $block->getConfidence();
            }
        }
        return empty($confidences) ? 0.0 : (array_sum($confidences) / count($confidences)) * 100;
    }
}
```

---

## Advanced Patterns

### Multi-Vendor Fallback Strategy

```php
<?php

declare(strict_types=1);

namespace App\Services\DataProcessor;

use Nexus\DataProcessor\Contracts\DocumentRecognizerInterface;
use Nexus\DataProcessor\ValueObjects\ProcessingResult;
use Nexus\DataProcessor\Exceptions\ProcessingFailedException;

final readonly class MultiVendorFallbackAdapter implements DocumentRecognizerInterface
{
    public function __construct(
        private DocumentRecognizerInterface $primaryOcr,
        private DocumentRecognizerInterface $fallbackOcr,
        private float $confidenceThreshold = 85.0
    ) {}

    public function recognizeDocument(
        string $filePath,
        string $documentType,
        array $options = []
    ): ProcessingResult {
        try {
            $result = $this->primaryOcr->recognizeDocument($filePath, $documentType, $options);

            if ($result->getConfidence() >= $this->confidenceThreshold) {
                return $result;
            }

            // Primary confidence too low - try fallback
            $fallbackResult = $this->fallbackOcr->recognizeDocument($filePath, $documentType, $options);

            return $fallbackResult->getConfidence() > $result->getConfidence()
                ? $fallbackResult
                : $result;

        } catch (ProcessingFailedException $e) {
            // Primary failed - try fallback
            return $this->fallbackOcr->recognizeDocument($filePath, $documentType, $options);
        }
    }

    public function getSupportedDocumentTypes(): array
    {
        return array_unique(array_merge(
            $this->primaryOcr->getSupportedDocumentTypes(),
            $this->fallbackOcr->getSupportedDocumentTypes()
        ));
    }

    public function supportsDocumentType(string $documentType): bool
    {
        return $this->primaryOcr->supportsDocumentType($documentType) ||
               $this->fallbackOcr->supportsDocumentType($documentType);
    }
}
```

**Bind in Service Provider:**
```php
$this->app->singleton(DocumentRecognizerInterface::class, function ($app) {
    return new MultiVendorFallbackAdapter(
        primaryOcr: $app->make(AzureFormRecognizerAdapter::class),
        fallbackOcr: $app->make(AwsTextractAdapter::class),
        confidenceThreshold: 85.0
    );
});
```

---

## Testing Strategies

### Mock Adapter for Unit Tests

```php
<?php

declare(strict_types=1);

namespace Tests\Mocks;

use Nexus\DataProcessor\Contracts\DocumentRecognizerInterface;
use Nexus\DataProcessor\ValueObjects\ProcessingResult;

final class MockDocumentRecognizer implements DocumentRecognizerInterface
{
    public function __construct(
        private array $mockData = [],
        private float $mockConfidence = 95.0
    ) {}

    public function recognizeDocument(
        string $filePath,
        string $documentType,
        array $options = []
    ): ProcessingResult {
        return new ProcessingResult(
            extractedData: $this->mockData,
            confidence: $this->mockConfidence,
            fieldConfidences: array_fill_keys(array_keys($this->mockData), $this->mockConfidence),
            warnings: []
        );
    }

    public function getSupportedDocumentTypes(): array
    {
        return ['invoice', 'receipt', 'id'];
    }

    public function supportsDocumentType(string $documentType): bool
    {
        return in_array($documentType, $this->getSupportedDocumentTypes());
    }
}
```

**Usage in Tests:**
```php
public function test_creates_bill_from_ocr(): void
{
    $mockOcr = new MockDocumentRecognizer(
        mockData: [
            'vendor_name' => 'Acme Corp',
            'invoice_number' => 'INV-001',
            'total_amount' => 1500.00,
        ],
        mockConfidence: 92.5
    );

    $manager = new VendorBillManager($mockOcr, $repository);

    $billId = $manager->createFromScannedDocument('/tmp/test.pdf');

    $this->assertNotEmpty($billId);
}
```

---

## See Also

- **Getting Started Guide:** [`docs/getting-started.md`](getting-started.md)
- **API Reference:** [`docs/api-reference.md`](api-reference.md)
- **Code Examples:** [`docs/examples/`](examples/)

---

**Last Updated:** 2025-11-24  
**Package Version:** 1.0.0

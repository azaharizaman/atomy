# Getting Started with Nexus DataProcessor

**Package:** `Nexus\DataProcessor`  
**Type:** Pure Contract Package (Interface-Only)  
**Minimum PHP:** 8.3+

---

## Overview

The **Nexus\DataProcessor** package provides **interface contracts** for document processing and OCR (Optical Character Recognition) services. This is a **pure contract package** - it contains no concrete implementations.

**Key Concept:** This package defines **what** OCR services should do, not **how** they do it. Concrete implementations (Azure Form Recognizer, AWS Textract, Google Vision API adapters) belong in your application layer.

---

## Prerequisites

### Required
- PHP 8.3 or higher
- Composer

### For Concrete Implementations (Application Layer)
- Vendor SDK (Azure/AWS/Google) based on your choice
- API credentials for chosen OCR vendor

---

## Installation

### 1. Install the Package

```bash
composer require nexus/data-processor:"*@dev"
```

### 2. Understand the Architecture

```
Your Application (apps/Atomy)
├── Binds interfaces → concrete implementations
├── Vendor SDK dependencies (Azure/AWS/Google)
└── Tests for concrete adapters

Nexus\DataProcessor Package
├── Defines interfaces (DocumentRecognizerInterface)
├── Defines value objects (ProcessingResult)
└── Defines exceptions (ProcessingFailedException)

Consuming Packages (Nexus\Payable, Nexus\Receivable, etc.)
└── Type-hint DocumentRecognizerInterface (dependency injection)
```

---

## Core Concepts

### 1. Pure Contract Package

This package contains **ONLY**:
- ✅ Interfaces (contracts)
- ✅ Value Objects (immutable data containers)
- ✅ Exceptions

This package does **NOT** contain:
- ❌ Vendor SDK integrations
- ❌ HTTP clients or API calls
- ❌ File storage logic
- ❌ Queue management

**Why?** Vendor SDKs have heavy dependencies and version conflicts. By keeping this package as pure contracts, you can choose your preferred vendor without forcing dependencies on all package consumers.

### 2. Vendor-Agnostic Strategy

The interface allows you to:
- Use **Azure Form Recognizer** for invoices (best accuracy)
- Use **Google Vision API** for ID cards (best for Asian languages)
- Use **AWS Textract** for receipts (best pricing)
- Switch vendors anytime **without refactoring consuming packages**

### 3. Confidence-Based Validation

All OCR results include confidence scores (0-100):
- **95-100:** High confidence → Auto-accept
- **80-94:** Medium confidence → Optional review
- **0-79:** Low confidence → Mandatory manual review

---

## Your First Integration

### Step 1: Create a Vendor Adapter (Application Layer)

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
    public function __construct(
        private DocumentAnalysisClient $client,
        private array $documentTypeModels = [
            'invoice' => 'prebuilt-invoice',
            'receipt' => 'prebuilt-receipt',
            'id' => 'prebuilt-idDocument',
        ]
    ) {}

    public function recognizeDocument(
        string $filePath,
        string $documentType,
        array $options = []
    ): ProcessingResult {
        if (!$this->supportsDocumentType($documentType)) {
            throw new UnsupportedDocumentTypeException(
                "Document type '{$documentType}' is not supported"
            );
        }

        $modelId = $this->documentTypeModels[$documentType];

        try {
            $poller = $this->client->beginAnalyzeDocument($modelId, fopen($filePath, 'r'));
            $result = $poller->pollUntilComplete();

            return new ProcessingResult(
                extractedData: $this->transformAzureResult($result),
                confidence: $result->getConfidence() * 100,
                fieldConfidences: $this->extractFieldConfidences($result),
                warnings: $this->extractWarnings($result)
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
        return array_keys($this->documentTypeModels);
    }

    public function supportsDocumentType(string $documentType): bool
    {
        return isset($this->documentTypeModels[$documentType]);
    }

    private function transformAzureResult($result): array
    {
        // Transform Azure-specific result to standard array
        $data = [];
        foreach ($result->getDocuments()[0]->getFields() as $key => $field) {
            $data[$key] = $field->getValueString();
        }
        return $data;
    }

    private function extractFieldConfidences($result): array
    {
        $confidences = [];
        foreach ($result->getDocuments()[0]->getFields() as $key => $field) {
            $confidences[$key] = $field->getConfidence() * 100;
        }
        return $confidences;
    }

    private function extractWarnings($result): array
    {
        return []; // Azure doesn't provide warnings in current API
    }
}
```

### Step 2: Register in Service Container

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
        $this->app->singleton(DocumentRecognizerInterface::class, function () {
            $client = new DocumentAnalysisClient(
                config('services.azure.form_recognizer.endpoint'),
                new AzureKeyCredential(config('services.azure.form_recognizer.key'))
            );

            return new AzureFormRecognizerAdapter($client);
        });
    }
}
```

### Step 3: Configure Credentials

**File:** `apps/Atomy/config/services.php`

```php
return [
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
AZURE_FORM_RECOGNIZER_KEY=your-32-character-key
```

### Step 4: Use in Consuming Packages

**Example:** Extract data from vendor invoice in `Nexus\Payable`

**File:** `packages/Payable/src/Services/VendorBillManager.php`

```php
<?php

declare(strict_types=1);

namespace Nexus\Payable\Services;

use Nexus\DataProcessor\Contracts\DocumentRecognizerInterface;
use Nexus\Payable\Contracts\VendorBillRepositoryInterface;
use Nexus\Payable\ValueObjects\VendorBillData;

final readonly class VendorBillManager
{
    public function __construct(
        private DocumentRecognizerInterface $ocr,
        private VendorBillRepositoryInterface $repository
    ) {}

    public function createFromScannedDocument(string $filePath): string
    {
        // Process document with OCR
        $result = $this->ocr->recognizeDocument($filePath, 'invoice');

        // Check confidence before auto-creating
        if ($result->getConfidence() < 80) {
            throw new \RuntimeException(
                "OCR confidence too low ({$result->getConfidence()}%), manual review required"
            );
        }

        // Extract data
        $data = $result->getExtractedData();

        // Create vendor bill
        $billData = new VendorBillData(
            vendorName: $data['vendor_name'] ?? throw new \InvalidArgumentException('Missing vendor name'),
            invoiceNumber: $data['invoice_number'] ?? throw new \InvalidArgumentException('Missing invoice number'),
            invoiceDate: new \DateTimeImmutable($data['invoice_date']),
            totalAmount: (float) $data['total_amount'],
            lineItems: $this->extractLineItems($data)
        );

        return $this->repository->create($billData);
    }

    private function extractLineItems(array $data): array
    {
        // Transform OCR line items to VendorBillLineItem VOs
        // Implementation details...
        return [];
    }
}
```

---

## Common Patterns

### Pattern 1: Confidence-Based Routing

```php
public function processInvoice(string $filePath): void
{
    $result = $this->ocr->recognizeDocument($filePath, 'invoice');

    match (true) {
        $result->getConfidence() >= 95 => $this->autoAccept($result),
        $result->getConfidence() >= 80 => $this->flagForOptionalReview($result),
        default => $this->requireManualReview($result),
    };
}
```

### Pattern 2: Field-Level Validation

```php
public function validateCriticalFields(ProcessingResult $result): void
{
    $criticalFields = ['invoice_number', 'total_amount', 'vendor_name'];

    foreach ($criticalFields as $field) {
        if (!$result->hasField($field)) {
            throw new \InvalidArgumentException("Missing critical field: {$field}");
        }

        if ($result->getFieldConfidence($field) < 90) {
            throw new \RuntimeException(
                "Low confidence on critical field '{$field}': " . 
                $result->getFieldConfidence($field) . "%"
            );
        }
    }
}
```

### Pattern 3: Multi-Vendor Fallback

```php
final readonly class MultiVendorOcrAdapter implements DocumentRecognizerInterface
{
    public function __construct(
        private DocumentRecognizerInterface $primaryOcr,
        private DocumentRecognizerInterface $fallbackOcr,
        private float $minimumConfidence = 85.0
    ) {}

    public function recognizeDocument(
        string $filePath,
        string $documentType,
        array $options = []
    ): ProcessingResult {
        // Try primary vendor
        $result = $this->primaryOcr->recognizeDocument($filePath, $documentType, $options);

        // If confidence too low, try fallback vendor
        if ($result->getConfidence() < $this->minimumConfidence) {
            $fallbackResult = $this->fallbackOcr->recognizeDocument($filePath, $documentType, $options);

            // Use whichever has higher confidence
            return $fallbackResult->getConfidence() > $result->getConfidence()
                ? $fallbackResult
                : $result;
        }

        return $result;
    }

    // ... implement other interface methods
}
```

---

## Next Steps

1. **Read API Reference:** [`docs/api-reference.md`](api-reference.md) - Detailed interface documentation
2. **Study Integration Guide:** [`docs/integration-guide.md`](integration-guide.md) - Laravel/Symfony examples
3. **Review Examples:** [`docs/examples/`](examples/) - Working code samples
4. **Implement Your Adapter:** Choose Azure, AWS, or Google based on your needs
5. **Write Tests:** Test your adapter implementation thoroughly

---

## Troubleshooting

### Issue: "Class DocumentRecognizerInterface not found"

**Cause:** Interface not registered in service container  
**Solution:** Create and register service provider (see Step 2 above)

### Issue: "UnsupportedDocumentTypeException"

**Cause:** Document type not in adapter's supported types  
**Solution:** Check `getSupportedDocumentTypes()` or add new document type to adapter

### Issue: "Low confidence scores"

**Causes:**
- Poor image quality (blurry, low resolution)
- Handwritten text (OCR works best with printed text)
- Non-standard document format
- Language not supported by vendor

**Solutions:**
- Improve image quality (300+ DPI recommended)
- Use vendor with better support for your document type
- Implement manual review workflow for low confidence results

### Issue: "ProcessingFailedException"

**Causes:**
- Invalid API credentials
- Network timeout
- Unsupported file format
- Corrupted file

**Solutions:**
- Verify API credentials in `.env`
- Check vendor service status
- Ensure file format is supported (PDF, PNG, JPG)
- Validate file integrity before processing

---

## Support

- **Documentation:** [`docs/`](../docs/)
- **Examples:** [`docs/examples/`](examples/)
- **Package Issues:** GitHub Issues (internal monorepo)
- **Architecture Questions:** Nexus Architecture Team

---

**Ready to integrate?** Continue to [API Reference](api-reference.md) →

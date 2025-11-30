# API Reference: DataProcessor

**Package:** `Nexus\DataProcessor`  
**Namespace:** `Nexus\DataProcessor`  
**Type:** Pure Contract Package

---

## Overview

This document provides complete API documentation for all public interfaces, value objects, and exceptions in the Nexus\DataProcessor package.

---

## Interfaces

### DocumentRecognizerInterface

**Namespace:** `Nexus\DataProcessor\Contracts\DocumentRecognizerInterface`

**Purpose:** Contract for OCR (Optical Character Recognition) services that extract structured data from documents.

#### Methods

##### `recognizeDocument()`

Processes a document and extracts structured data with confidence scores.

```php
public function recognizeDocument(
    string $filePath,
    string $documentType,
    array $options = []
): ProcessingResult
```

**Parameters:**
- `$filePath` (string) - Absolute path to the document file (PDF, PNG, JPG)
- `$documentType` (string) - Type of document to process (e.g., 'invoice', 'receipt', 'id')
- `$options` (array) - Optional vendor-specific configuration
  - `'locale'` - Document language (e.g., 'en-US', 'ms-MY')
  - `'pages'` - Specific pages to process (e.g., [1, 2, 5])
  - `'mode'` - Processing mode (e.g., 'quick', 'detailed')

**Returns:** `ProcessingResult` - Immutable value object containing extracted data and confidence scores

**Throws:**
- `ProcessingFailedException` - When OCR processing fails (network error, invalid file, API error)
- `UnsupportedDocumentTypeException` - When document type is not supported by the implementation

**Example:**
```php
use Nexus\DataProcessor\Contracts\DocumentRecognizerInterface;

public function __construct(
    private readonly DocumentRecognizerInterface $ocr
) {}

public function extractInvoiceData(string $filePath): array
{
    try {
        $result = $this->ocr->recognizeDocument(
            filePath: $filePath,
            documentType: 'invoice',
            options: [
                'locale' => 'en-US',
                'mode' => 'detailed',
            ]
        );

        if ($result->getConfidence() < 80) {
            throw new \RuntimeException('OCR confidence too low for auto-processing');
        }

        return $result->getExtractedData();

    } catch (ProcessingFailedException $e) {
        // Handle OCR failure (retry, log error, notify admin)
        throw new \RuntimeException("OCR failed: {$e->getMessage()}", previous: $e);
    }
}
```

---

##### `getSupportedDocumentTypes()`

Returns list of document types supported by this implementation.

```php
public function getSupportedDocumentTypes(): array
```

**Returns:** `array<string>` - Array of supported document type identifiers

**Example:**
```php
$supportedTypes = $this->ocr->getSupportedDocumentTypes();
// ['invoice', 'receipt', 'id', 'passport', 'business_card']
```

---

##### `supportsDocumentType()`

Checks if a specific document type is supported.

```php
public function supportsDocumentType(string $documentType): bool
```

**Parameters:**
- `$documentType` (string) - Document type identifier to check

**Returns:** `bool` - True if document type is supported, false otherwise

**Example:**
```php
if ($this->ocr->supportsDocumentType('invoice')) {
    $result = $this->ocr->recognizeDocument($filePath, 'invoice');
} else {
    throw new \InvalidArgumentException('Invoice OCR not supported');
}
```

---

## Value Objects

### ProcessingResult

**Namespace:** `Nexus\DataProcessor\ValueObjects\ProcessingResult`

**Purpose:** Immutable container for OCR processing results with confidence scores.

**Properties:**

```php
final readonly class ProcessingResult
{
    public function __construct(
        public array $extractedData,
        public float $confidence,
        public array $fieldConfidences,
        public array $warnings
    ) {
        // Validates confidence is between 0-100
    }
}
```

#### Properties

##### `$extractedData`

**Type:** `array<string, mixed>`  
**Description:** Key-value pairs of extracted fields from the document

**Structure:**
```php
[
    'vendor_name' => 'Acme Corporation',
    'invoice_number' => 'INV-2024-001',
    'invoice_date' => '2024-11-15',
    'total_amount' => 1500.00,
    'line_items' => [
        ['description' => 'Widget A', 'quantity' => 10, 'unit_price' => 100.00],
        ['description' => 'Widget B', 'quantity' => 5, 'unit_price' => 100.00],
    ],
]
```

---

##### `$confidence`

**Type:** `float`  
**Range:** 0.0 - 100.0  
**Description:** Overall confidence score for the entire document processing

**Confidence Levels:**
- **95-100:** Very high confidence (auto-accept)
- **85-94:** High confidence (minimal review)
- **70-84:** Medium confidence (review recommended)
- **50-69:** Low confidence (manual review required)
- **0-49:** Very low confidence (likely processing error)

---

##### `$fieldConfidences`

**Type:** `array<string, float>`  
**Description:** Per-field confidence scores matching keys in `$extractedData`

**Structure:**
```php
[
    'vendor_name' => 98.5,
    'invoice_number' => 100.0,
    'invoice_date' => 95.2,
    'total_amount' => 92.8,
]
```

**Use Case:** Identify which fields need manual verification

---

##### `$warnings`

**Type:** `array<string>`  
**Description:** Non-critical warnings encountered during processing

**Example:**
```php
[
    'Low resolution image detected',
    'Handwritten notes ignored',
    'Table borders unclear - line items may be incomplete',
]
```

---

#### Methods

##### `getExtractedData()`

Returns all extracted data as an array.

```php
public function getExtractedData(): array
```

**Returns:** `array<string, mixed>` - All extracted fields

**Example:**
```php
$data = $result->getExtractedData();
$vendorName = $data['vendor_name'] ?? null;
```

---

##### `getField()`

Retrieves a specific field value from extracted data.

```php
public function getField(string $key, mixed $default = null): mixed
```

**Parameters:**
- `$key` (string) - Field key to retrieve
- `$default` (mixed) - Default value if field doesn't exist

**Returns:** `mixed` - Field value or default

**Example:**
```php
$invoiceNumber = $result->getField('invoice_number');
$poNumber = $result->getField('po_number', 'N/A'); // Default to 'N/A'
```

---

##### `hasField()`

Checks if a specific field exists in extracted data.

```php
public function hasField(string $key): bool
```

**Parameters:**
- `$key` (string) - Field key to check

**Returns:** `bool` - True if field exists, false otherwise

**Example:**
```php
if ($result->hasField('tax_id')) {
    $taxId = $result->getField('tax_id');
}
```

---

##### `getConfidence()`

Returns overall confidence score.

```php
public function getConfidence(): float
```

**Returns:** `float` - Confidence score (0-100)

**Example:**
```php
if ($result->getConfidence() >= 95) {
    $this->autoAccept($result);
} else {
    $this->queueForReview($result);
}
```

---

##### `getFieldConfidence()`

Returns confidence score for a specific field.

```php
public function getFieldConfidence(string $key): float
```

**Parameters:**
- `$key` (string) - Field key

**Returns:** `float` - Confidence score for field (0-100)

**Throws:** `\InvalidArgumentException` - If field doesn't exist in `$fieldConfidences`

**Example:**
```php
$amountConfidence = $result->getFieldConfidence('total_amount');

if ($amountConfidence < 90) {
    $this->flagForManualVerification('total_amount', $result);
}
```

---

##### `getFieldConfidences()`

Returns all field confidence scores.

```php
public function getFieldConfidences(): array
```

**Returns:** `array<string, float>` - All field confidence scores

**Example:**
```php
$confidences = $result->getFieldConfidences();

foreach ($confidences as $field => $confidence) {
    if ($confidence < 85) {
        echo "Low confidence on field '{$field}': {$confidence}%\n";
    }
}
```

---

##### `hasWarnings()`

Checks if any warnings were generated during processing.

```php
public function hasWarnings(): bool
```

**Returns:** `bool` - True if warnings exist, false otherwise

**Example:**
```php
if ($result->hasWarnings()) {
    $this->logWarnings($result->warnings);
}
```

---

## Exceptions

### DataProcessorException

**Namespace:** `Nexus\DataProcessor\Exceptions\DataProcessorException`

**Type:** Abstract base exception

**Purpose:** Base exception class for all DataProcessor package exceptions

**Usage:**
```php
try {
    $result = $this->ocr->recognizeDocument($filePath, 'invoice');
} catch (DataProcessorException $e) {
    // Catch all DataProcessor-related exceptions
    $this->logger->error('DataProcessor error: ' . $e->getMessage());
}
```

---

### ProcessingFailedException

**Namespace:** `Nexus\DataProcessor\Exceptions\ProcessingFailedException`

**Extends:** `DataProcessorException`

**Purpose:** Thrown when document processing fails

**When Thrown:**
- Network error communicating with OCR vendor API
- Invalid file format (not PDF, PNG, JPG)
- Corrupted file
- Vendor API error (rate limit, authentication failure)
- File size exceeds vendor limits

**Example:**
```php
use Nexus\DataProcessor\Exceptions\ProcessingFailedException;

try {
    $result = $this->ocr->recognizeDocument($filePath, 'invoice');
} catch (ProcessingFailedException $e) {
    $this->logger->error('OCR processing failed', [
        'file' => $filePath,
        'error' => $e->getMessage(),
        'vendor' => get_class($this->ocr),
    ]);

    // Retry with fallback vendor or queue for manual processing
    $this->retryWithFallback($filePath);
}
```

---

### UnsupportedDocumentTypeException

**Namespace:** `Nexus\DataProcessor\Exceptions\UnsupportedDocumentTypeException`

**Extends:** `DataProcessorException`

**Purpose:** Thrown when requested document type is not supported by the implementation

**When Thrown:**
- Document type not in `getSupportedDocumentTypes()` array
- Vendor doesn't support the document type
- Custom document type not configured

**Example:**
```php
use Nexus\DataProcessor\Exceptions\UnsupportedDocumentTypeException;

try {
    $result = $this->ocr->recognizeDocument($filePath, 'passport');
} catch (UnsupportedDocumentTypeException $e) {
    $supportedTypes = $this->ocr->getSupportedDocumentTypes();

    throw new \InvalidArgumentException(
        "Document type 'passport' not supported. " .
        "Supported types: " . implode(', ', $supportedTypes)
    );
}
```

---

## Document Types (Common Standards)

While document types are implementation-specific, these are commonly supported:

| Document Type | Identifier | Typical Fields |
|---------------|------------|----------------|
| Invoice | `invoice` | vendor_name, invoice_number, invoice_date, total_amount, line_items |
| Receipt | `receipt` | merchant_name, transaction_date, total_amount, line_items |
| ID Card | `id` | name, id_number, date_of_birth, address |
| Passport | `passport` | name, passport_number, nationality, date_of_birth, expiry_date |
| Business Card | `business_card` | name, company, title, email, phone |
| Tax Form | `tax_form` | tax_id, name, year, form_type, fields (varies by form) |
| Bank Statement | `bank_statement` | account_number, statement_date, transactions |

**Note:** Actual supported types depend on your vendor adapter implementation. Always check `getSupportedDocumentTypes()`.

---

## Usage Patterns

### Pattern 1: Validate Critical Fields

```php
public function validateInvoiceData(ProcessingResult $result): void
{
    $requiredFields = [
        'vendor_name' => 90.0,      // Minimum 90% confidence
        'invoice_number' => 95.0,   // Minimum 95% confidence
        'total_amount' => 92.0,     // Minimum 92% confidence
    ];

    foreach ($requiredFields as $field => $minConfidence) {
        if (!$result->hasField($field)) {
            throw new \InvalidArgumentException("Missing required field: {$field}");
        }

        if ($result->getFieldConfidence($field) < $minConfidence) {
            throw new \RuntimeException(
                "Confidence too low for field '{$field}': " .
                "{$result->getFieldConfidence($field)}% (required: {$minConfidence}%)"
            );
        }
    }
}
```

### Pattern 2: Conditional Processing Based on Confidence

```php
public function processDocument(string $filePath): void
{
    $result = $this->ocr->recognizeDocument($filePath, 'invoice');

    if ($result->getConfidence() >= 95) {
        $this->autoProcessInvoice($result);
    } elseif ($result->getConfidence() >= 80) {
        $this->queueForQuickReview($result);
    } else {
        $this->queueForFullManualReview($result, reason: 'Low OCR confidence');
    }
}
```

### Pattern 3: Fallback to Manual Entry

```php
public function extractOrManual(string $filePath): array
{
    try {
        $result = $this->ocr->recognizeDocument($filePath, 'invoice');

        if ($result->getConfidence() >= 85) {
            return $result->getExtractedData();
        }

        // Low confidence - return partial data with flag
        return [
            'extracted_data' => $result->getExtractedData(),
            'needs_review' => true,
            'confidence' => $result->getConfidence(),
            'warnings' => $result->warnings,
        ];

    } catch (ProcessingFailedException $e) {
        // OCR failed completely - return empty data for manual entry
        return [
            'extracted_data' => [],
            'needs_manual_entry' => true,
            'error' => $e->getMessage(),
        ];
    }
}
```

---

## See Also

- **Getting Started Guide:** [`docs/getting-started.md`](getting-started.md)
- **Integration Examples:** [`docs/integration-guide.md`](integration-guide.md)
- **Code Examples:** [`docs/examples/`](examples/)
- **Requirements:** [`REQUIREMENTS.md`](../REQUIREMENTS.md)

---

**Last Updated:** 2025-11-24  
**Package Version:** 1.0.0

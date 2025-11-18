# Nexus\DataProcessor

Framework-agnostic contracts for OCR, ETL, and document processing capabilities.

## Purpose

The DataProcessor package provides **interface-only** contracts for specialized data processing tasks. This is a pure interface package - all concrete implementations must be provided in the application layer (apps/Atomy) due to vendor SDK dependencies.

## Key Features

- **OCR/Document Recognition**: Extract structured data from images and PDFs
- **Document Classification**: Identify document types (invoice, receipt, contract, ID)
- **Data Transformation**: Format conversion and normalization
- **Batch Processing**: High-volume document processing queues
- **Multi-Language Support**: Process documents in various languages
- **Confidence Scoring**: Validation thresholds for extracted data

## Architecture

### Contracts (Interfaces)
- `DocumentRecognizerInterface` - OCR service contract
- `DocumentParserInterface` - Structured data extraction
- `DocumentClassifierInterface` - Document type identification
- `DataTransformerInterface` - Data format conversions
- `DataValidatorInterface` - Extracted data validation
- `BatchProcessorInterface` - Bulk document processing

### Value Objects
- `ProcessingResult` - OCR output with confidence scores
- `DocumentMetadata` - Document properties (type, size, MIME)
- `ExtractionConfidence` - Confidence score (0-100%)

### No Concrete Implementations

This package provides ONLY contracts. Vendor-specific implementations (Azure Cognitive Services, AWS Textract, Google Vision API) must be created in the application layer.

## Supported Vendors (Application Layer)

Recommended OCR vendors for implementation in `apps/Atomy`:
- **Azure Cognitive Services** - Form Recognizer, OCR
- **AWS Textract** - Document analysis, forms, tables
- **Google Cloud Vision API** - OCR, label detection
- **Tesseract OCR** - Open-source (lower accuracy)

## Usage Example

```php
// In application layer (Atomy), inject DocumentRecognizerInterface
use Nexus\DataProcessor\Contracts\DocumentRecognizerInterface;

public function __construct(
    private readonly DocumentRecognizerInterface $ocr
) {}

public function processInvoice(string $filePath): array
{
    $result = $this->ocr->recognizeDocument($filePath, 'invoice');
    
    if ($result->getConfidence() < 80) {
        // Queue for manual review
        $this->queueForReview($result);
    }
    
    return $result->getExtractedData();
}
```

## Integration

This package is consumed by:
- `Nexus\Payable` - for vendor bill OCR processing
- `Nexus\Receivable` - for customer document processing
- `Nexus\Hrm` - for employee document verification
- `Nexus\Procurement` - for PO/GR document scanning

This package integrates with:
- `Nexus\Storage` - for document archiving (REQUIRED)
- `Nexus\AuditLogger` - for processing audit trails (REQUIRED)
- `Nexus\Notifier` - for processing completion notifications (REQUIRED)

## Performance Requirements

- OCR processing: < 10s per document (single page) via async queue
- Batch processing: 100 documents per hour minimum
- Image preprocessing: < 2s per document
- Document classification: < 1s per document

## Small to Enterprise Scale

- **Small business**: Basic OCR for common document types (< 100 docs/month)
- **Medium business**: Advanced OCR with field mapping and validation (100-1000 docs/month)
- **Large enterprise**: ML-powered OCR with continuous learning (1000+ docs/day)

## Vendor Implementation Example

```php
// In apps/Atomy/app/Services/AzureOcrAdapter.php
namespace App\Services;

use Nexus\DataProcessor\Contracts\DocumentRecognizerInterface;
use Azure\AI\FormRecognizer\FormRecognizerClient;

final class AzureOcrAdapter implements DocumentRecognizerInterface
{
    public function __construct(
        private readonly FormRecognizerClient $client
    ) {}

    public function recognizeDocument(string $filePath, string $documentType): ProcessingResult
    {
        // Azure-specific implementation
        $response = $this->client->beginRecognizeCustomFormsFromUrl($filePath);
        
        // Transform Azure response to ProcessingResult
        return new ProcessingResult(
            extractedData: $this->transformAzureData($response),
            confidence: $this->calculateConfidence($response)
        );
    }
}
```

## Security Considerations

- Encrypt documents at rest and in transit
- Sanitize extracted data to prevent injection attacks
- Support GDPR compliance with document retention and deletion policies
- Log all document access and processing events
- Implement rate limiting for OCR API usage

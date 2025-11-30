<?php

declare(strict_types=1);

/**
 * Basic Usage Example: Nexus\DataProcessor
 * 
 * This example demonstrates simple OCR document processing
 * with confidence-based validation.
 */

use Nexus\DataProcessor\Contracts\DocumentRecognizerInterface;
use Nexus\DataProcessor\Exceptions\ProcessingFailedException;
use Nexus\DataProcessor\Exceptions\UnsupportedDocumentTypeException;

// Assume DocumentRecognizerInterface is injected via DI
// (See integration-guide.md for service provider setup)

final readonly class InvoiceProcessor
{
    public function __construct(
        private DocumentRecognizerInterface $ocr
    ) {}

    /**
     * Extract data from invoice with basic validation
     */
    public function processInvoice(string $filePath): array
    {
        try {
            // Check if invoice processing is supported
            if (!$this->ocr->supportsDocumentType('invoice')) {
                throw new \RuntimeException('Invoice OCR not supported by current implementation');
            }

            // Process the document
            $result = $this->ocr->recognizeDocument(
                filePath: $filePath,
                documentType: 'invoice'
            );

            // Validate overall confidence
            if ($result->getConfidence() < 80) {
                return [
                    'status' => 'manual_review_required',
                    'reason' => 'Low OCR confidence',
                    'confidence' => $result->getConfidence(),
                    'data' => $result->getExtractedData(),
                ];
            }

            // Extract required fields
            $invoiceData = [
                'vendor_name' => $result->getField('vendor_name'),
                'invoice_number' => $result->getField('invoice_number'),
                'invoice_date' => $result->getField('invoice_date'),
                'total_amount' => $result->getField('total_amount'),
                'line_items' => $result->getField('line_items', []),
            ];

            // Validate required fields exist
            foreach (['vendor_name', 'invoice_number', 'total_amount'] as $field) {
                if (empty($invoiceData[$field])) {
                    return [
                        'status' => 'missing_data',
                        'missing_field' => $field,
                        'confidence' => $result->getConfidence(),
                        'data' => $result->getExtractedData(),
                    ];
                }
            }

            return [
                'status' => 'success',
                'confidence' => $result->getConfidence(),
                'data' => $invoiceData,
                'warnings' => $result->warnings,
            ];

        } catch (ProcessingFailedException $e) {
            return [
                'status' => 'processing_failed',
                'error' => $e->getMessage(),
            ];
        } catch (UnsupportedDocumentTypeException $e) {
            return [
                'status' => 'unsupported_type',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check field-level confidence scores
     */
    public function validateFieldConfidence(string $filePath): array
    {
        $result = $this->ocr->recognizeDocument($filePath, 'invoice');

        $criticalFields = ['invoice_number', 'total_amount'];
        $lowConfidenceFields = [];

        foreach ($criticalFields as $field) {
            if (!$result->hasField($field)) {
                $lowConfidenceFields[$field] = 'missing';
                continue;
            }

            $confidence = $result->getFieldConfidence($field);

            if ($confidence < 90) {
                $lowConfidenceFields[$field] = $confidence;
            }
        }

        if (!empty($lowConfidenceFields)) {
            return [
                'status' => 'validation_failed',
                'low_confidence_fields' => $lowConfidenceFields,
                'overall_confidence' => $result->getConfidence(),
            ];
        }

        return [
            'status' => 'validated',
            'overall_confidence' => $result->getConfidence(),
            'data' => $result->getExtractedData(),
        ];
    }

    /**
     * Get list of supported document types from current implementation
     */
    public function getSupportedTypes(): array
    {
        return $this->ocr->getSupportedDocumentTypes();
    }
}

// ============================================================================
// USAGE EXAMPLES
// ============================================================================

/**
 * Example 1: Simple invoice processing
 */
function example1_simple_processing(DocumentRecognizerInterface $ocr): void
{
    $processor = new InvoiceProcessor($ocr);

    $result = $processor->processInvoice('/path/to/invoice.pdf');

    if ($result['status'] === 'success') {
        echo "Invoice processed successfully!\n";
        echo "Vendor: {$result['data']['vendor_name']}\n";
        echo "Invoice Number: {$result['data']['invoice_number']}\n";
        echo "Amount: {$result['data']['total_amount']}\n";
        echo "Confidence: {$result['confidence']}%\n";
    } else {
        echo "Processing failed: {$result['status']}\n";
    }
}

/**
 * Example 2: Field-level confidence validation
 */
function example2_field_validation(DocumentRecognizerInterface $ocr): void
{
    $processor = new InvoiceProcessor($ocr);

    $result = $processor->validateFieldConfidence('/path/to/invoice.pdf');

    if ($result['status'] === 'validation_failed') {
        echo "Low confidence on critical fields:\n";
        foreach ($result['low_confidence_fields'] as $field => $confidence) {
            if ($confidence === 'missing') {
                echo "  - {$field}: MISSING\n";
            } else {
                echo "  - {$field}: {$confidence}%\n";
            }
        }
    } else {
        echo "All fields validated successfully!\n";
    }
}

/**
 * Example 3: Check supported document types
 */
function example3_check_support(DocumentRecognizerInterface $ocr): void
{
    $processor = new InvoiceProcessor($ocr);

    $supportedTypes = $processor->getSupportedTypes();

    echo "Supported document types:\n";
    foreach ($supportedTypes as $type) {
        echo "  - {$type}\n";
    }

    // Check specific type
    if ($ocr->supportsDocumentType('receipt')) {
        echo "\nReceipt processing is available!\n";
    }
}

/**
 * Example 4: Direct interface usage (without wrapper class)
 */
function example4_direct_usage(DocumentRecognizerInterface $ocr): void
{
    // Process document
    $result = $ocr->recognizeDocument(
        filePath: '/path/to/receipt.pdf',
        documentType: 'receipt',
        options: [
            'locale' => 'en-US',
            'mode' => 'detailed',
        ]
    );

    // Check overall confidence
    if ($result->getConfidence() >= 95) {
        echo "High confidence - auto-accept\n";
    } elseif ($result->getConfidence() >= 80) {
        echo "Medium confidence - quick review recommended\n";
    } else {
        echo "Low confidence - manual review required\n";
    }

    // Extract specific fields
    $merchantName = $result->getField('merchant_name', 'Unknown');
    $totalAmount = $result->getField('total_amount', 0.0);

    echo "Merchant: {$merchantName}\n";
    echo "Total: {$totalAmount}\n";

    // Check for warnings
    if ($result->hasWarnings()) {
        echo "\nWarnings:\n";
        foreach ($result->warnings as $warning) {
            echo "  - {$warning}\n";
        }
    }

    // Display all field confidences
    echo "\nField Confidence Scores:\n";
    foreach ($result->getFieldConfidences() as $field => $confidence) {
        echo "  - {$field}: {$confidence}%\n";
    }
}

/**
 * Example 5: Conditional processing based on confidence
 */
function example5_confidence_routing(DocumentRecognizerInterface $ocr): void
{
    $result = $ocr->recognizeDocument('/path/to/invoice.pdf', 'invoice');

    match (true) {
        $result->getConfidence() >= 95 => autoAcceptInvoice($result),
        $result->getConfidence() >= 80 => flagForQuickReview($result),
        default => requireManualEntry($result),
    };
}

function autoAcceptInvoice($result): void
{
    echo "Auto-accepting invoice (confidence: {$result->getConfidence()}%)\n";
    // Create invoice record automatically
}

function flagForQuickReview($result): void
{
    echo "Flagging for quick review (confidence: {$result->getConfidence()}%)\n";
    // Queue for human verification
}

function requireManualEntry($result): void
{
    echo "Requires manual entry (confidence: {$result->getConfidence()}%)\n";
    // Show form with pre-filled data from OCR
}

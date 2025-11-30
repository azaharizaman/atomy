<?php

declare(strict_types=1);

/**
 * Advanced Usage Example: Nexus\DataProcessor
 * 
 * This example demonstrates advanced patterns:
 * - Multi-vendor fallback strategy
 * - Batch processing
 * - Custom validation rules
 * - Confidence-based workflow routing
 */

use Nexus\DataProcessor\Contracts\DocumentRecognizerInterface;
use Nexus\DataProcessor\ValueObjects\ProcessingResult;
use Nexus\DataProcessor\Exceptions\ProcessingFailedException;

// ============================================================================
// PATTERN 1: Multi-Vendor Fallback Adapter
// ============================================================================

/**
 * Implements fallback strategy: Try primary vendor, fallback to secondary if needed
 */
final readonly class MultiVendorOcrAdapter implements DocumentRecognizerInterface
{
    public function __construct(
        private DocumentRecognizerInterface $primaryOcr,
        private DocumentRecognizerInterface $fallbackOcr,
        private float $minimumConfidence = 85.0,
        private ?\Psr\Log\LoggerInterface $logger = null
    ) {}

    public function recognizeDocument(
        string $filePath,
        string $documentType,
        array $options = []
    ): ProcessingResult {
        // Try primary vendor first
        try {
            $result = $this->primaryOcr->recognizeDocument($filePath, $documentType, $options);

            // If confidence meets threshold, return result
            if ($result->getConfidence() >= $this->minimumConfidence) {
                $this->logger?->info('Primary OCR succeeded', [
                    'confidence' => $result->getConfidence(),
                    'vendor' => get_class($this->primaryOcr),
                ]);

                return $result;
            }

            // Confidence too low - try fallback
            $this->logger?->warning('Primary OCR confidence too low, trying fallback', [
                'primary_confidence' => $result->getConfidence(),
                'threshold' => $this->minimumConfidence,
            ]);

            return $this->tryFallback($filePath, $documentType, $options, $result);

        } catch (ProcessingFailedException $e) {
            // Primary vendor failed completely - use fallback
            $this->logger?->error('Primary OCR failed, using fallback', [
                'error' => $e->getMessage(),
                'vendor' => get_class($this->primaryOcr),
            ]);

            return $this->fallbackOcr->recognizeDocument($filePath, $documentType, $options);
        }
    }

    private function tryFallback(
        string $filePath,
        string $documentType,
        array $options,
        ProcessingResult $primaryResult
    ): ProcessingResult {
        try {
            $fallbackResult = $this->fallbackOcr->recognizeDocument(
                $filePath,
                $documentType,
                $options
            );

            // Use whichever result has higher confidence
            if ($fallbackResult->getConfidence() > $primaryResult->getConfidence()) {
                $this->logger?->info('Fallback OCR produced better result', [
                    'fallback_confidence' => $fallbackResult->getConfidence(),
                    'primary_confidence' => $primaryResult->getConfidence(),
                ]);

                return $fallbackResult;
            }

            return $primaryResult;

        } catch (ProcessingFailedException $e) {
            // Fallback also failed - return primary result even if low confidence
            $this->logger?->warning('Fallback OCR also failed, returning primary result', [
                'fallback_error' => $e->getMessage(),
            ]);

            return $primaryResult;
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

// ============================================================================
// PATTERN 2: Batch Document Processor
// ============================================================================

/**
 * Process multiple documents in batch with progress tracking
 */
final readonly class BatchDocumentProcessor
{
    public function __construct(
        private DocumentRecognizerInterface $ocr
    ) {}

    /**
     * Process multiple documents with confidence-based categorization
     * 
     * @param array<string> $filePaths
     * @return array{auto_accept: array, needs_review: array, failed: array}
     */
    public function processBatch(array $filePaths, string $documentType): array
    {
        $results = [
            'auto_accept' => [],
            'needs_review' => [],
            'failed' => [],
        ];

        foreach ($filePaths as $index => $filePath) {
            try {
                $result = $this->ocr->recognizeDocument($filePath, $documentType);

                $processedItem = [
                    'file' => $filePath,
                    'confidence' => $result->getConfidence(),
                    'data' => $result->getExtractedData(),
                    'warnings' => $result->warnings,
                ];

                // Categorize by confidence
                if ($result->getConfidence() >= 95) {
                    $results['auto_accept'][] = $processedItem;
                } elseif ($result->getConfidence() >= 80) {
                    $results['needs_review'][] = $processedItem;
                } else {
                    $results['failed'][] = $processedItem;
                }

            } catch (ProcessingFailedException $e) {
                $results['failed'][] = [
                    'file' => $filePath,
                    'error' => $e->getMessage(),
                ];
            }

            // Optional: Report progress
            $progress = round((($index + 1) / count($filePaths)) * 100, 2);
            echo "Progress: {$progress}% ({$index + 1}/" . count($filePaths) . ")\n";
        }

        return $results;
    }

    /**
     * Process documents in parallel (requires async support)
     * 
     * Note: This is a conceptual example - actual implementation
     * would require proper async/await or queue workers
     */
    public function processBatchAsync(array $filePaths, string $documentType): array
    {
        // Chunk files into batches for parallel processing
        $batches = array_chunk($filePaths, 5);
        $allResults = [];

        foreach ($batches as $batch) {
            // In production: dispatch to queue workers
            // For demo: process sequentially
            foreach ($batch as $filePath) {
                $result = $this->ocr->recognizeDocument($filePath, $documentType);
                $allResults[] = [
                    'file' => $filePath,
                    'result' => $result,
                ];
            }
        }

        return $allResults;
    }
}

// ============================================================================
// PATTERN 3: Custom Field Validation Rules
// ============================================================================

/**
 * Apply custom validation rules to OCR results
 */
final readonly class OcrValidator
{
    /**
     * Validate invoice data with custom rules
     */
    public function validateInvoice(ProcessingResult $result): array
    {
        $errors = [];

        // Rule 1: Critical fields must exist
        $criticalFields = ['vendor_name', 'invoice_number', 'total_amount', 'invoice_date'];
        
        foreach ($criticalFields as $field) {
            if (!$result->hasField($field)) {
                $errors[] = "Missing critical field: {$field}";
            }
        }

        // Rule 2: Critical fields must have high confidence
        foreach ($criticalFields as $field) {
            if ($result->hasField($field)) {
                $confidence = $result->getFieldConfidence($field);
                
                if ($confidence < 90) {
                    $errors[] = "Low confidence on {$field}: {$confidence}%";
                }
            }
        }

        // Rule 3: Total amount must be numeric and positive
        if ($result->hasField('total_amount')) {
            $amount = $result->getField('total_amount');
            
            if (!is_numeric($amount) || (float) $amount <= 0) {
                $errors[] = "Invalid total amount: {$amount}";
            }
        }

        // Rule 4: Invoice date must be valid and not in future
        if ($result->hasField('invoice_date')) {
            $dateStr = $result->getField('invoice_date');
            
            try {
                $date = new \DateTimeImmutable($dateStr);
                
                if ($date > new \DateTimeImmutable()) {
                    $errors[] = "Invoice date is in the future: {$dateStr}";
                }
            } catch (\Exception $e) {
                $errors[] = "Invalid invoice date format: {$dateStr}";
            }
        }

        // Rule 5: Line items total should match total_amount (if both exist)
        if ($result->hasField('line_items') && $result->hasField('total_amount')) {
            $lineItems = $result->getField('line_items', []);
            $calculatedTotal = 0.0;

            foreach ($lineItems as $item) {
                $calculatedTotal += (float) ($item['total'] ?? 0);
            }

            $declaredTotal = (float) $result->getField('total_amount');
            $difference = abs($calculatedTotal - $declaredTotal);

            if ($difference > 0.01) { // Allow 1 cent tolerance for rounding
                $errors[] = "Line items total ({$calculatedTotal}) doesn't match declared total ({$declaredTotal})";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'overall_confidence' => $result->getConfidence(),
        ];
    }

    /**
     * Validate ID document
     */
    public function validateIdDocument(ProcessingResult $result): array
    {
        $errors = [];

        // Required fields for ID
        $requiredFields = ['name', 'id_number', 'date_of_birth'];

        foreach ($requiredFields as $field) {
            if (!$result->hasField($field)) {
                $errors[] = "Missing required field: {$field}";
            }
        }

        // Date of birth must be in the past
        if ($result->hasField('date_of_birth')) {
            try {
                $dob = new \DateTimeImmutable($result->getField('date_of_birth'));
                
                if ($dob >= new \DateTimeImmutable()) {
                    $errors[] = "Date of birth cannot be in the future";
                }

                // Must be at least 18 years old (example rule)
                $age = (new \DateTimeImmutable())->diff($dob)->y;
                if ($age < 18) {
                    $errors[] = "Person must be at least 18 years old (age: {$age})";
                }
            } catch (\Exception $e) {
                $errors[] = "Invalid date of birth format";
            }
        }

        // ID number format validation (example: must be alphanumeric, 12 chars)
        if ($result->hasField('id_number')) {
            $idNumber = $result->getField('id_number');
            
            if (!preg_match('/^[A-Z0-9]{12}$/i', $idNumber)) {
                $errors[] = "ID number format invalid (expected 12 alphanumeric characters)";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'overall_confidence' => $result->getConfidence(),
        ];
    }
}

// ============================================================================
// PATTERN 4: Workflow Routing Based on Confidence
// ============================================================================

/**
 * Route OCR results to different workflows based on confidence and validation
 */
final readonly class OcrWorkflowRouter
{
    public function __construct(
        private DocumentRecognizerInterface $ocr,
        private OcrValidator $validator
    ) {}

    /**
     * Process invoice with intelligent routing
     */
    public function processInvoice(string $filePath): array
    {
        // Step 1: OCR Processing
        $result = $this->ocr->recognizeDocument($filePath, 'invoice');

        // Step 2: Validate
        $validation = $this->validator->validateInvoice($result);

        // Step 3: Route based on confidence and validation
        return match (true) {
            // High confidence + valid = Auto-accept
            $result->getConfidence() >= 95 && $validation['valid'] => [
                'workflow' => 'auto_accept',
                'action' => 'Create invoice automatically',
                'data' => $result->getExtractedData(),
            ],

            // Medium confidence + valid = Quick review
            $result->getConfidence() >= 80 && $validation['valid'] => [
                'workflow' => 'quick_review',
                'action' => 'Queue for quick human verification',
                'data' => $result->getExtractedData(),
            ],

            // Medium confidence + invalid = Manual review
            $result->getConfidence() >= 80 && !$validation['valid'] => [
                'workflow' => 'manual_review',
                'action' => 'Queue for detailed review',
                'data' => $result->getExtractedData(),
                'validation_errors' => $validation['errors'],
            ],

            // Low confidence = Manual entry with OCR hints
            default => [
                'workflow' => 'manual_entry',
                'action' => 'Present form with OCR data as hints',
                'data' => $result->getExtractedData(),
                'confidence' => $result->getConfidence(),
                'validation_errors' => $validation['errors'] ?? [],
            ],
        };
    }
}

// ============================================================================
// PATTERN 5: Vendor-Specific Optimization
// ============================================================================

/**
 * Choose best vendor for specific document types
 */
final readonly class VendorOptimizedOcr implements DocumentRecognizerInterface
{
    public function __construct(
        private DocumentRecognizerInterface $azureOcr,
        private DocumentRecognizerInterface $googleOcr,
        private DocumentRecognizerInterface $awsOcr,
    ) {}

    public function recognizeDocument(
        string $filePath,
        string $documentType,
        array $options = []
    ): ProcessingResult {
        // Route to best vendor for document type
        $vendor = match ($documentType) {
            'invoice' => $this->azureOcr,        // Azure best for invoices
            'receipt' => $this->awsOcr,          // AWS best for receipts
            'id', 'passport' => $this->googleOcr, // Google best for IDs (Asian languages)
            default => $this->azureOcr,          // Default to Azure
        };

        return $vendor->recognizeDocument($filePath, $documentType, $options);
    }

    public function getSupportedDocumentTypes(): array
    {
        return array_unique(array_merge(
            $this->azureOcr->getSupportedDocumentTypes(),
            $this->googleOcr->getSupportedDocumentTypes(),
            $this->awsOcr->getSupportedDocumentTypes()
        ));
    }

    public function supportsDocumentType(string $documentType): bool
    {
        return $this->azureOcr->supportsDocumentType($documentType) ||
               $this->googleOcr->supportsDocumentType($documentType) ||
               $this->awsOcr->supportsDocumentType($documentType);
    }
}

// ============================================================================
// USAGE EXAMPLES
// ============================================================================

/**
 * Example 1: Multi-vendor fallback
 */
function example1_multi_vendor(
    DocumentRecognizerInterface $azureOcr,
    DocumentRecognizerInterface $awsOcr
): void {
    $multiVendorOcr = new MultiVendorOcrAdapter(
        primaryOcr: $azureOcr,
        fallbackOcr: $awsOcr,
        minimumConfidence: 85.0
    );

    $result = $multiVendorOcr->recognizeDocument('/path/to/invoice.pdf', 'invoice');

    echo "Confidence: {$result->getConfidence()}%\n";
    echo "Data: " . json_encode($result->getExtractedData(), JSON_PRETTY_PRINT) . "\n";
}

/**
 * Example 2: Batch processing
 */
function example2_batch_processing(DocumentRecognizerInterface $ocr): void
{
    $batchProcessor = new BatchDocumentProcessor($ocr);

    $files = [
        '/path/to/invoice1.pdf',
        '/path/to/invoice2.pdf',
        '/path/to/invoice3.pdf',
    ];

    $results = $batchProcessor->processBatch($files, 'invoice');

    echo "Auto-accept: " . count($results['auto_accept']) . "\n";
    echo "Needs review: " . count($results['needs_review']) . "\n";
    echo "Failed: " . count($results['failed']) . "\n";
}

/**
 * Example 3: Custom validation
 */
function example3_custom_validation(DocumentRecognizerInterface $ocr): void
{
    $validator = new OcrValidator();
    
    $result = $ocr->recognizeDocument('/path/to/invoice.pdf', 'invoice');
    $validation = $validator->validateInvoice($result);

    if ($validation['valid']) {
        echo "Invoice is valid!\n";
    } else {
        echo "Validation errors:\n";
        foreach ($validation['errors'] as $error) {
            echo "  - {$error}\n";
        }
    }
}

/**
 * Example 4: Workflow routing
 */
function example4_workflow_routing(
    DocumentRecognizerInterface $ocr,
    OcrValidator $validator
): void {
    $router = new OcrWorkflowRouter($ocr, $validator);
    
    $workflow = $router->processInvoice('/path/to/invoice.pdf');

    echo "Workflow: {$workflow['workflow']}\n";
    echo "Action: {$workflow['action']}\n";

    if (isset($workflow['validation_errors'])) {
        echo "Validation errors found:\n";
        foreach ($workflow['validation_errors'] as $error) {
            echo "  - {$error}\n";
        }
    }
}

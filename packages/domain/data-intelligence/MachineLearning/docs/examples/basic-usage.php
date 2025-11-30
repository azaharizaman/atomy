<?php

declare(strict_types=1);

/**
 * Basic Usage Example: Nexus MachineLearning Package
 * 
 * This example demonstrates the fundamental usage of the MachineLearning package
 * for anomaly detection in a receivables (invoice) context.
 * 
 * Prerequisites:
 * - PHP 8.3+
 * - Package installed via Composer
 * - OpenAI API key configured (or use RuleBased provider as fallback)
 * 
 * @package Nexus\MachineLearning\Examples
 */

namespace Examples\MachineLearning;

use Nexus\MachineLearning\Contracts\{
    AnomalyDetectionServiceInterface,
    FeatureExtractorInterface
};

// ============================================================================
// Example 1: Feature Extraction
// ============================================================================

/**
 * Simple feature extractor for invoice anomaly detection
 */
final class InvoiceAnomalyExtractor implements FeatureExtractorInterface
{
    public function extract(mixed $entity): array
    {
        // Assume $entity is an Invoice object
        return [
            'invoice_number' => $entity->number,
            'customer_id' => $entity->customer_id,
            'invoice_date' => $entity->invoice_date->format('Y-m-d'),
            'due_date' => $entity->due_date->format('Y-m-d'),
            'total_amount' => (float) $entity->total_amount,
            'currency' => $entity->currency,
            'line_items_count' => count($entity->line_items),
            'payment_terms' => $entity->payment_terms,
            
            // Historical context
            'customer_history' => [
                'total_invoices' => $entity->customer->invoices_count,
                'average_amount' => (float) $entity->customer->average_invoice_amount,
                'overdue_count' => $entity->customer->overdue_invoices_count,
                'last_payment_date' => $entity->customer->last_payment_date?->format('Y-m-d'),
            ],
        ];
    }
    
    public function getFeatureSchema(): array
    {
        return [
            'version' => 1,
            'fields' => [
                'invoice_number' => ['type' => 'string', 'required' => true],
                'customer_id' => ['type' => 'string', 'required' => true],
                'invoice_date' => ['type' => 'date', 'required' => true],
                'due_date' => ['type' => 'date', 'required' => true],
                'total_amount' => ['type' => 'float', 'required' => true],
                'currency' => ['type' => 'string', 'required' => true],
                'line_items_count' => ['type' => 'integer', 'required' => true],
                'payment_terms' => ['type' => 'string', 'required' => false],
                'customer_history' => [
                    'type' => 'object',
                    'required' => true,
                    'fields' => [
                        'total_invoices' => ['type' => 'integer', 'required' => true],
                        'average_amount' => ['type' => 'float', 'required' => true],
                        'overdue_count' => ['type' => 'integer', 'required' => true],
                        'last_payment_date' => ['type' => 'date', 'required' => false],
                    ],
                ],
            ],
        ];
    }
}

// ============================================================================
// Example 2: Basic Anomaly Detection
// ============================================================================

/**
 * Simple invoice validator using anomaly detection
 */
final readonly class InvoiceValidator
{
    public function __construct(
        private AnomalyDetectionServiceInterface $mlService,
        private InvoiceAnomalyExtractor $extractor
    ) {}
    
    /**
     * Validate invoice for anomalies
     * 
     * @param object $invoice Invoice entity
     * 
     * @return void
     * 
     * @throws InvoiceAnomalyDetectedException If anomaly detected with high confidence
     */
    public function validate(object $invoice): void
    {
        // Extract features
        $features = $this->extractor->extract($invoice);
        
        // Detect anomalies
        $result = $this->mlService->detectAnomalies('receivable', $features);
        
        // Check result
        if ($result->isAnomaly() && $result->getConfidence() >= 0.85) {
            throw new InvoiceAnomalyDetectedException(
                "Invoice {$invoice->number} flagged as anomaly: {$result->getReason()}"
            );
        }
    }
    
    /**
     * Soft validation - returns result without throwing
     * 
     * @param object $invoice Invoice entity
     * 
     * @return array Validation result
     */
    public function softValidate(object $invoice): array
    {
        $features = $this->extractor->extract($invoice);
        $result = $this->mlService->detectAnomalies('receivable', $features);
        
        return [
            'is_anomaly' => $result->isAnomaly(),
            'confidence' => $result->getConfidence(),
            'reason' => $result->getReason(),
            'provider' => $result->getProvider(),
            'requires_review' => $result->isAnomaly() && $result->getConfidence() >= 0.75,
        ];
    }
}

// ============================================================================
// Example 3: Using in Controller (Laravel)
// ============================================================================

/**
 * Invoice controller with ML-powered validation
 */
final class InvoiceController
{
    public function __construct(
        private readonly InvoiceValidator $validator
    ) {}
    
    /**
     * Create new invoice with anomaly detection
     */
    public function store(array $data): array
    {
        // Create invoice (pseudo-code)
        $invoice = Invoice::create($data);
        
        // Validate with ML
        try {
            $this->validator->validate($invoice);
            
            // No anomaly - approve automatically
            $invoice->status = 'approved';
            $invoice->save();
            
            return [
                'success' => true,
                'invoice' => $invoice,
                'message' => 'Invoice created and approved',
            ];
            
        } catch (InvoiceAnomalyDetectedException $e) {
            // Anomaly detected - flag for review
            $invoice->status = 'pending_review';
            $invoice->review_reason = $e->getMessage();
            $invoice->save();
            
            return [
                'success' => true,
                'invoice' => $invoice,
                'message' => 'Invoice created but requires manual review',
                'review_reason' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Bulk validation endpoint
     */
    public function validateBatch(array $invoiceIds): array
    {
        $results = [];
        
        foreach ($invoiceIds as $id) {
            $invoice = Invoice::find($id);
            
            if (!$invoice) {
                $results[$id] = ['error' => 'Invoice not found'];
                continue;
            }
            
            $results[$id] = $this->validator->softValidate($invoice);
        }
        
        return $results;
    }
}

// ============================================================================
// Example 4: Manual Feature Construction (without Extractor)
// ============================================================================

/**
 * Simple anomaly check without creating a dedicated extractor
 */
function detectInvoiceAnomaly(
    AnomalyDetectionServiceInterface $mlService,
    object $invoice
): bool {
    // Manually construct features
    $features = [
        'invoice_number' => $invoice->number,
        'total_amount' => (float) $invoice->total_amount,
        'customer_id' => $invoice->customer_id,
        'line_items_count' => count($invoice->line_items),
    ];
    
    // Detect anomaly
    $result = $mlService->detectAnomalies('receivable', $features);
    
    return $result->isAnomaly();
}

// ============================================================================
// Example 5: Logging Anomaly Results
// ============================================================================

/**
 * Validator with comprehensive logging
 */
final readonly class LoggingInvoiceValidator
{
    public function __construct(
        private AnomalyDetectionServiceInterface $mlService,
        private InvoiceAnomalyExtractor $extractor,
        private \Psr\Log\LoggerInterface $logger
    ) {}
    
    public function validate(object $invoice): void
    {
        $features = $this->extractor->extract($invoice);
        
        $this->logger->info('Running ML anomaly detection', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->number,
        ]);
        
        $result = $this->mlService->detectAnomalies('receivable', $features);
        
        $this->logger->info('ML anomaly detection completed', [
            'invoice_id' => $invoice->id,
            'is_anomaly' => $result->isAnomaly(),
            'confidence' => $result->getConfidence(),
            'provider' => $result->getProvider(),
            'reason' => $result->getReason(),
        ]);
        
        if ($result->isAnomaly() && $result->getConfidence() >= 0.85) {
            $this->logger->warning('Invoice anomaly detected', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->number,
                'confidence' => $result->getConfidence(),
                'reason' => $result->getReason(),
            ]);
            
            throw new InvoiceAnomalyDetectedException($result->getReason());
        }
    }
}

// ============================================================================
// Example 6: Custom Exception
// ============================================================================

/**
 * Custom exception for invoice anomalies
 */
final class InvoiceAnomalyDetectedException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?float $confidence = null,
        private readonly ?string $provider = null
    ) {
        parent::__construct($message);
    }
    
    public function getConfidence(): ?float
    {
        return $this->confidence;
    }
    
    public function getProvider(): ?string
    {
        return $this->provider;
    }
}

// ============================================================================
// Usage Example in Application
// ============================================================================

/*

// Bootstrap (Laravel Service Provider)

use Nexus\MachineLearning\Contracts\AnomalyDetectionServiceInterface;
use Nexus\MachineLearning\Services\AnomalyDetectionService;

$this->app->singleton(AnomalyDetectionServiceInterface::class, function ($app) {
    return $app->make(AnomalyDetectionService::class);
});

// Usage in controller or service

public function __construct(
    private readonly AnomalyDetectionServiceInterface $mlService
) {}

public function createInvoice(array $data): void
{
    $invoice = Invoice::create($data);
    
    // Quick anomaly check
    $features = [
        'invoice_number' => $invoice->number,
        'total_amount' => (float) $invoice->total_amount,
        'customer_id' => $invoice->customer_id,
    ];
    
    $result = $this->mlService->detectAnomalies('receivable', $features);
    
    if ($result->isAnomaly()) {
        $invoice->flagForReview($result->getReason());
    } else {
        $invoice->approve();
    }
}

*/

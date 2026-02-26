<?php

declare(strict_types=1);

namespace Nexus\Telemetry\Services;

use DateTimeImmutable;
use Nexus\Telemetry\Contracts\AlertDispatcherInterface;
use Nexus\Telemetry\Contracts\AlertGatewayInterface;
use Nexus\Telemetry\ValueObjects\AlertContext;
use Nexus\Telemetry\ValueObjects\AlertSeverity;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Evaluates and dispatches alerts with severity mapping and deduplication.
 * 
 * Features:
 * - Exception-to-severity mapping (RuntimeException → CRITICAL, LogicException → ERROR, etc.)
 * - Alert deduplication using fingerprinting (configurable time window)
 * - Automatic metadata enrichment (stack trace, timestamp, exception details)
 * - Graceful handling of dispatcher failures
 * - Comprehensive logging for observability
 * 
 * @see AlertGatewayInterface
 */
final class AlertEvaluator implements AlertGatewayInterface
{
    /** @var array<string, int> Fingerprint → timestamp mapping for deduplication */
    private array $deduplicationCache = [];
    
    public function __construct(
        private readonly AlertDispatcherInterface $dispatcher,
        private readonly LoggerInterface $logger,
        private readonly bool $enableDeduplication = true,
        private readonly int $deduplicationWindowSeconds = 300
    ) {}
    
    public function logException(Throwable $exception, AlertSeverity $severity, array $context = []): void
    {
        $this->evaluateException($exception, $severity, $context);
    }
    
    public function logAuditAlert(AlertSeverity $severity, string $message, array $context = []): void
    {
        $alertContext = new AlertContext(
            severity: $severity,
            message: $message,
            context: array_merge($context, [
                'alert_type' => 'audit',
                'occurred_at' => (new DateTimeImmutable())->format(DATE_RFC3339),
            ]),
            throwable: null,
            triggeredAt: new DateTimeImmutable()
        );
        
        $this->dispatchAlert($alertContext);
    }
    
    public function notifyCritical(string $message, array $metadata = []): void
    {
        $alertContext = new AlertContext(
            severity: AlertSeverity::CRITICAL,
            message: $message,
            context: array_merge($metadata, [
                'alert_type' => 'critical_notification',
                'occurred_at' => (new DateTimeImmutable())->format(DATE_RFC3339),
            ]),
            throwable: null,
            triggeredAt: new DateTimeImmutable()
        );
        
        $this->dispatchAlert($alertContext);
    }
    
    /**
     * Evaluate an exception and dispatch an alert if not deduplicated.
     *
     * @param array<string, mixed> $additionalMetadata
     */
    public function evaluateException(Throwable $exception, ?AlertSeverity $severity = null, array $additionalMetadata = []): void
    {
        // Use provided severity or map from exception type
        $severity = $severity ?? $this->mapExceptionToSeverity($exception);
        
        $alertContext = new AlertContext(
            severity: $severity,
            message: $exception->getMessage(),
            context: array_merge([
                'exception_class' => get_class($exception),
                'exception_code' => $exception->getCode(),
                'exception_file' => $exception->getFile(),
                'exception_line' => $exception->getLine(),
                'stack_trace' => $exception->getTraceAsString(),
                'occurred_at' => (new DateTimeImmutable())->format(DATE_RFC3339),
            ], $additionalMetadata),
            throwable: $exception,
            triggeredAt: new DateTimeImmutable()
        );
        
        $this->dispatchAlert($alertContext);
    }
    
    /**
     * Clear the deduplication cache.
     */
    public function clearDeduplicationCache(): void
    {
        $this->deduplicationCache = [];
        $this->logger->debug('Alert deduplication cache cleared');
    }
    
    /**
     * Dispatch an alert with deduplication and error handling.
     */
    private function dispatchAlert(AlertContext $context): void
    {
        $fingerprint = $context->getFingerprint();
        
        // Check for deduplication
        if ($this->enableDeduplication && $this->isDuplicate($fingerprint)) {
            $this->logger->debug('Alert deduplicated (duplicate within window)', [
                'fingerprint' => $fingerprint,
                'message' => $context->message,
            ]);
            return;
        }
        
        // Record fingerprint for deduplication
        if ($this->enableDeduplication) {
            $this->recordFingerprint($fingerprint);
        }
        
        // Attempt to dispatch
        try {
            $this->dispatcher->dispatch($context);
            
            $this->logger->info('Alert evaluated and dispatched', [
                'severity' => $context->severity->value,
                'message' => $context->message,
                'fingerprint' => $fingerprint,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to dispatch alert', [
                'alert_message' => $context->message,
                'alert_severity' => $context->severity->value,
                'dispatcher_error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Map exception type to alert severity.
     */
    private function mapExceptionToSeverity(Throwable $exception): AlertSeverity
    {
        return match (true) {
            $exception instanceof \RuntimeException => AlertSeverity::CRITICAL,
            $exception instanceof \LogicException => AlertSeverity::WARNING,
            $exception instanceof \InvalidArgumentException => AlertSeverity::WARNING,
            default => AlertSeverity::INFO,
        };
    }
    
    /**
     * Generate a consistent fingerprint for an exception.
     */
    private function generateExceptionFingerprint(Throwable $exception): string
    {
        return $this->generateFingerprint(
            get_class($exception),
            $exception->getMessage()
        );
    }
    
    /**
     * Generate a fingerprint for deduplication.
     */
    private function generateFingerprint(string ...$components): string
    {
        return hash('sha256', implode('|', $components));
    }
    
    /**
     * Check if an alert is a duplicate within the deduplication window.
     */
    private function isDuplicate(string $fingerprint): bool
    {
        if (!isset($this->deduplicationCache[$fingerprint])) {
            return false;
        }
        
        $age = time() - $this->deduplicationCache[$fingerprint];
        
        return $age < $this->deduplicationWindowSeconds;
    }
    
    /**
     * Record a fingerprint with current timestamp.
     */
    private function recordFingerprint(string $fingerprint): void
    {
        // Clean old entries first
        $this->cleanExpiredFingerprints();
        
        $this->deduplicationCache[$fingerprint] = time();
    }
    
    /**
     * Remove expired fingerprints from cache.
     */
    private function cleanExpiredFingerprints(): void
    {
        $now = time();
        
        foreach ($this->deduplicationCache as $fingerprint => $timestamp) {
            if (($now - $timestamp) >= $this->deduplicationWindowSeconds) {
                unset($this->deduplicationCache[$fingerprint]);
            }
        }
    }
    
    /**
     * Get short class name from exception.
     */
    private function getExceptionClassName(Throwable $exception): string
    {
        $className = get_class($exception);
        $parts = explode('\\', $className);
        
        return end($parts);
    }
}

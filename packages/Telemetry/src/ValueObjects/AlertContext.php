<?php

declare(strict_types=1);

namespace Nexus\Telemetry\ValueObjects;

use DateTimeImmutable;
use Throwable;

/**
 * Alert Context Value Object
 *
 * Immutable representation of an alert with enriched context.
 *
 * @package Nexus\Telemetry\ValueObjects
 */
final readonly class AlertContext
{
    /**
     * @param AlertSeverity $severity Alert severity level
     * @param string $message Human-readable alert message
     * @param array<string, mixed> $context Additional context data
     * @param Throwable|null $throwable Optional exception that triggered the alert
     * @param DateTimeImmutable $triggeredAt When the alert was triggered
     */
    public function __construct(
        public AlertSeverity $severity,
        public string $message,
        public array $context,
        public ?Throwable $throwable,
        public DateTimeImmutable $triggeredAt,
    ) {}

    /**
     * Calculate unique fingerprint for deduplication.
     * Fingerprint is based on severity, message, and key context fields.
     *
     * @return string
     */
    public function getFingerprint(): string
    {
        $fingerprintData = [
            'severity' => $this->severity->value,
            'message' => $this->message,
            'exception_class' => $this->throwable ? get_class($this->throwable) : null,
        ];

        return md5(json_encode($fingerprintData));
    }

    /**
     * Check if this alert requires immediate notification.
     *
     * @return bool
     */
    public function requiresImmediateNotification(): bool
    {
        return $this->severity->requiresImmediateNotification();
    }

    /**
     * Convert to array representation with enriched exception data.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'severity' => $this->severity->value,
            'message' => $this->message,
            'context' => $this->context,
            'triggered_at' => $this->triggeredAt->format('Y-m-d H:i:s.u'),
        ];

        if ($this->throwable !== null) {
            $data['exception'] = [
                'class' => get_class($this->throwable),
                'message' => $this->throwable->getMessage(),
                'code' => $this->throwable->getCode(),
                'file' => $this->throwable->getFile(),
                'line' => $this->throwable->getLine(),
                'trace_digest' => substr(md5($this->throwable->getTraceAsString()), 0, 8),
            ];
        }

        return $data;
    }
}

<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\ValueObjects;

/**
 * Transaction Monitoring Analysis Result
 * 
 * Immutable value object representing the results of transaction
 * pattern analysis for AML monitoring purposes.
 */
final class TransactionMonitoringResult
{
    /**
     * Alert severity thresholds
     */
    public const SEVERITY_LOW = 'low';
    public const SEVERITY_MEDIUM = 'medium';
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_CRITICAL = 'critical';

    /**
     * @param string $partyId The party being monitored
     * @param bool $isSuspicious Whether suspicious activity was detected
     * @param int $riskScore Transaction risk score (0-100)
     * @param array<string> $reasons Reasons for the assessment
     * @param array<TransactionAlert> $alerts Individual transaction alerts
     * @param array<string, mixed> $patterns Detected transaction patterns
     * @param \DateTimeImmutable $analyzedAt When analysis was performed
     * @param \DateTimeImmutable|null $periodStart Start of analysis period
     * @param \DateTimeImmutable|null $periodEnd End of analysis period
     * @param int $transactionCount Number of transactions analyzed
     * @param float $totalVolume Total transaction volume in base currency
     * @param array<string, mixed> $metadata Additional analysis data
     */
    public function __construct(
        public readonly string $partyId,
        public readonly bool $isSuspicious,
        public readonly int $riskScore,
        public readonly array $reasons,
        public readonly array $alerts,
        public readonly array $patterns,
        public readonly \DateTimeImmutable $analyzedAt,
        public readonly ?\DateTimeImmutable $periodStart = null,
        public readonly ?\DateTimeImmutable $periodEnd = null,
        public readonly int $transactionCount = 0,
        public readonly float $totalVolume = 0.0,
        public readonly array $metadata = [],
    ) {
        if ($riskScore < 0 || $riskScore > 100) {
            throw new \InvalidArgumentException(
                "Risk score must be between 0 and 100, got {$riskScore}"
            );
        }
    }

    /**
     * Create a clean result (no suspicious activity)
     */
    public static function clean(
        string $partyId,
        int $transactionCount = 0,
        float $totalVolume = 0.0,
    ): self {
        return new self(
            partyId: $partyId,
            isSuspicious: false,
            riskScore: 0,
            reasons: [],
            alerts: [],
            patterns: [],
            analyzedAt: new \DateTimeImmutable(),
            transactionCount: $transactionCount,
            totalVolume: $totalVolume,
        );
    }

    /**
     * Create from array (for hydration)
     * 
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $alerts = [];
        foreach (($data['alerts'] ?? []) as $alertData) {
            $alerts[] = $alertData instanceof TransactionAlert
                ? $alertData
                : TransactionAlert::fromArray($alertData);
        }

        return new self(
            partyId: (string) $data['party_id'],
            isSuspicious: (bool) ($data['is_suspicious'] ?? false),
            riskScore: (int) ($data['risk_score'] ?? 0),
            reasons: (array) ($data['reasons'] ?? []),
            alerts: $alerts,
            patterns: (array) ($data['patterns'] ?? []),
            analyzedAt: $data['analyzed_at'] instanceof \DateTimeImmutable
                ? $data['analyzed_at']
                : new \DateTimeImmutable($data['analyzed_at'] ?? 'now'),
            periodStart: isset($data['period_start'])
                ? ($data['period_start'] instanceof \DateTimeImmutable
                    ? $data['period_start']
                    : new \DateTimeImmutable($data['period_start']))
                : null,
            periodEnd: isset($data['period_end'])
                ? ($data['period_end'] instanceof \DateTimeImmutable
                    ? $data['period_end']
                    : new \DateTimeImmutable($data['period_end']))
                : null,
            transactionCount: (int) ($data['transaction_count'] ?? 0),
            totalVolume: (float) ($data['total_volume'] ?? 0.0),
            metadata: (array) ($data['metadata'] ?? []),
        );
    }

    /**
     * Check if SAR filing should be considered
     */
    public function shouldConsiderSar(): bool
    {
        if ($this->isSuspicious && $this->riskScore >= 70) {
            return true;
        }

        // Check for high-severity alerts
        foreach ($this->alerts as $alert) {
            if ($alert->severity === self::SEVERITY_HIGH || 
                $alert->severity === self::SEVERITY_CRITICAL) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the highest severity level among alerts
     */
    public function getHighestSeverity(): ?string
    {
        if (empty($this->alerts)) {
            return null;
        }

        $severityOrder = [
            self::SEVERITY_LOW => 1,
            self::SEVERITY_MEDIUM => 2,
            self::SEVERITY_HIGH => 3,
            self::SEVERITY_CRITICAL => 4,
        ];

        $highest = self::SEVERITY_LOW;
        foreach ($this->alerts as $alert) {
            if (($severityOrder[$alert->severity] ?? 0) > ($severityOrder[$highest] ?? 0)) {
                $highest = $alert->severity;
            }
        }

        return $highest;
    }

    /**
     * Get alerts by severity
     * 
     * @return array<TransactionAlert>
     */
    public function getAlertsBySeverity(string $severity): array
    {
        return array_values(array_filter(
            $this->alerts,
            fn(TransactionAlert $alert) => $alert->severity === $severity
        ));
    }

    /**
     * Get the count of alerts by severity
     * 
     * @return array<string, int>
     */
    public function getAlertCountBySeverity(): array
    {
        $counts = [
            self::SEVERITY_LOW => 0,
            self::SEVERITY_MEDIUM => 0,
            self::SEVERITY_HIGH => 0,
            self::SEVERITY_CRITICAL => 0,
        ];

        foreach ($this->alerts as $alert) {
            $counts[$alert->severity] = ($counts[$alert->severity] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * Check if structuring pattern was detected
     */
    public function hasStructuringPattern(): bool
    {
        return isset($this->patterns['structuring']) && $this->patterns['structuring'] === true;
    }

    /**
     * Check if velocity pattern was detected
     */
    public function hasVelocityPattern(): bool
    {
        return isset($this->patterns['velocity_spike']) && $this->patterns['velocity_spike'] === true;
    }

    /**
     * Check if geographic anomaly was detected
     */
    public function hasGeographicAnomaly(): bool
    {
        return isset($this->patterns['geographic_anomaly']) && $this->patterns['geographic_anomaly'] === true;
    }

    /**
     * Check if round-number pattern was detected
     */
    public function hasRoundNumberPattern(): bool
    {
        return isset($this->patterns['round_numbers']) && $this->patterns['round_numbers'] === true;
    }

    /**
     * Check if there are any critical severity alerts
     */
    public function hasCriticalAlerts(): bool
    {
        foreach ($this->alerts as $alert) {
            if ($alert->severity === self::SEVERITY_CRITICAL) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get alerts by type
     * 
     * @return array<TransactionAlert>
     */
    public function getAlertsByType(string $type): array
    {
        return array_values(array_filter(
            $this->alerts,
            fn(TransactionAlert $alert) => $alert->type === $type
        ));
    }

    /**
     * Get average transaction value
     */
    public function getAverageTransactionValue(): float
    {
        if ($this->transactionCount === 0) {
            return 0.0;
        }

        return $this->totalVolume / $this->transactionCount;
    }

    /**
     * Check if this requires immediate action
     */
    public function requiresImmediateAction(): bool
    {
        return $this->getHighestSeverity() === self::SEVERITY_CRITICAL;
    }

    /**
     * Check if this requires review within SLA
     */
    public function requiresReview(): bool
    {
        return $this->isSuspicious || !empty($this->alerts);
    }

    /**
     * Get the SLA hours for review based on severity
     */
    public function getReviewSlaHours(): int
    {
        return match ($this->getHighestSeverity()) {
            self::SEVERITY_CRITICAL => 4,
            self::SEVERITY_HIGH => 24,
            self::SEVERITY_MEDIUM => 72,
            self::SEVERITY_LOW => 168,
            default => 168,
        };
    }

    /**
     * Convert to array for serialization
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'party_id' => $this->partyId,
            'is_suspicious' => $this->isSuspicious,
            'risk_score' => $this->riskScore,
            'should_consider_sar' => $this->shouldConsiderSar(),
            'reasons' => $this->reasons,
            'alerts' => array_map(fn(TransactionAlert $a) => $a->toArray(), $this->alerts),
            'alert_count_by_severity' => $this->getAlertCountBySeverity(),
            'highest_severity' => $this->getHighestSeverity(),
            'patterns' => $this->patterns,
            'has_structuring' => $this->hasStructuringPattern(),
            'has_velocity_spike' => $this->hasVelocityPattern(),
            'has_geographic_anomaly' => $this->hasGeographicAnomaly(),
            'analyzed_at' => $this->analyzedAt->format(\DateTimeInterface::ATOM),
            'period_start' => $this->periodStart?->format(\DateTimeInterface::ATOM),
            'period_end' => $this->periodEnd?->format(\DateTimeInterface::ATOM),
            'transaction_count' => $this->transactionCount,
            'total_volume' => $this->totalVolume,
            'average_transaction' => $this->getAverageTransactionValue(),
            'requires_immediate_action' => $this->requiresImmediateAction(),
            'requires_review' => $this->requiresReview(),
            'review_sla_hours' => $this->getReviewSlaHours(),
            'metadata' => $this->metadata,
        ];
    }
}

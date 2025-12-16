<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\ValueObjects;

/**
 * Individual Transaction Alert
 * 
 * Represents a single alert generated during transaction monitoring.
 */
final class TransactionAlert
{
    /**
     * Alert types
     */
    public const TYPE_STRUCTURING = 'structuring';
    public const TYPE_VELOCITY = 'velocity';
    public const TYPE_GEOGRAPHIC = 'geographic';
    public const TYPE_AMOUNT = 'amount';
    public const TYPE_COUNTERPARTY = 'counterparty';
    public const TYPE_PATTERN = 'pattern';
    public const TYPE_THRESHOLD = 'threshold';
    public const TYPE_DORMANCY = 'dormancy';

    /**
     * @param string $type Alert type identifier
     * @param string $severity Alert severity level
     * @param string $message Human-readable alert message
     * @param string|null $transactionId Related transaction ID
     * @param float|null $amount Transaction amount if applicable
     * @param string|null $currency Transaction currency
     * @param \DateTimeImmutable $triggeredAt When alert was triggered
     * @param array<string, mixed> $evidence Supporting evidence data
     */
    public function __construct(
        public readonly string $type,
        public readonly string $severity,
        public readonly string $message,
        public readonly ?string $transactionId = null,
        public readonly ?float $amount = null,
        public readonly ?string $currency = null,
        public readonly \DateTimeImmutable $triggeredAt = new \DateTimeImmutable(),
        public readonly array $evidence = [],
    ) {
    }

    /**
     * Create a structuring alert
     */
    public static function structuring(
        float $cumulativeAmount,
        int $transactionCount,
        float $threshold,
        string $currency = 'USD',
    ): self {
        return new self(
            type: self::TYPE_STRUCTURING,
            severity: TransactionMonitoringResult::SEVERITY_HIGH,
            message: sprintf(
                'Potential structuring detected: %d transactions totaling %s %s just below %s threshold',
                $transactionCount,
                number_format($cumulativeAmount, 2),
                $currency,
                number_format($threshold, 2)
            ),
            amount: $cumulativeAmount,
            currency: $currency,
            evidence: [
                'transaction_count' => $transactionCount,
                'cumulative_amount' => $cumulativeAmount,
                'threshold' => $threshold,
                'pattern' => 'below_threshold',
            ],
        );
    }

    /**
     * Create a velocity spike alert
     */
    public static function velocitySpike(
        float $currentVolume,
        float $averageVolume,
        float $percentageIncrease,
        string $period = 'daily',
    ): self {
        $severity = match (true) {
            $percentageIncrease >= 500 => TransactionMonitoringResult::SEVERITY_CRITICAL,
            $percentageIncrease >= 300 => TransactionMonitoringResult::SEVERITY_HIGH,
            $percentageIncrease >= 150 => TransactionMonitoringResult::SEVERITY_MEDIUM,
            default => TransactionMonitoringResult::SEVERITY_LOW,
        };

        return new self(
            type: self::TYPE_VELOCITY,
            severity: $severity,
            message: sprintf(
                'Unusual %s transaction velocity: %.1f%% above average (current: %s, avg: %s)',
                $period,
                $percentageIncrease,
                number_format($currentVolume, 2),
                number_format($averageVolume, 2)
            ),
            amount: $currentVolume,
            evidence: [
                'current_volume' => $currentVolume,
                'average_volume' => $averageVolume,
                'percentage_increase' => $percentageIncrease,
                'period' => $period,
            ],
        );
    }

    /**
     * Create a geographic anomaly alert
     */
    public static function geographicAnomaly(
        string $transactionId,
        string $country,
        string $reason,
    ): self {
        $severity = TransactionMonitoringResult::SEVERITY_HIGH;

        return new self(
            type: self::TYPE_GEOGRAPHIC,
            severity: $severity,
            message: sprintf(
                'Geographic anomaly: Transaction from %s - %s',
                $country,
                $reason
            ),
            transactionId: $transactionId,
            evidence: [
                'country' => $country,
                'reason' => $reason,
            ],
        );
    }

    /**
     * Create a large amount alert
     */
    public static function largeAmount(
        string $transactionId,
        float $amount,
        float $threshold,
        string $currency = 'USD',
    ): self {
        $severity = $amount >= $threshold * 5
            ? TransactionMonitoringResult::SEVERITY_HIGH
            : TransactionMonitoringResult::SEVERITY_MEDIUM;

        return new self(
            type: self::TYPE_AMOUNT,
            severity: $severity,
            message: sprintf(
                'Large transaction detected: %s %s exceeds threshold of %s',
                number_format($amount, 2),
                $currency,
                number_format($threshold, 2)
            ),
            transactionId: $transactionId,
            amount: $amount,
            currency: $currency,
            evidence: [
                'amount' => $amount,
                'threshold' => $threshold,
                'exceeds_by' => $amount - $threshold,
                'multiple' => $amount / $threshold,
            ],
        );
    }

    /**
     * Create a threshold breach alert (CTR-level)
     */
    public static function thresholdBreach(
        string $transactionId,
        float $amount,
        string $thresholdType,
        string $currency = 'USD',
    ): self {
        return new self(
            type: self::TYPE_THRESHOLD,
            severity: TransactionMonitoringResult::SEVERITY_MEDIUM,
            message: sprintf(
                '%s threshold breach: %s %s - Regulatory reporting may be required',
                ucfirst($thresholdType),
                number_format($amount, 2),
                $currency
            ),
            transactionId: $transactionId,
            amount: $amount,
            currency: $currency,
            evidence: [
                'threshold_type' => $thresholdType,
                'amount' => $amount,
            ],
        );
    }

    /**
     * Create a counterparty risk alert
     */
    public static function counterpartyRisk(
        string $transactionId,
        string $counterpartyId,
        string $reason,
        float $amount,
        string $currency = 'USD',
    ): self {
        return new self(
            type: self::TYPE_COUNTERPARTY,
            severity: TransactionMonitoringResult::SEVERITY_HIGH,
            message: sprintf(
                'High-risk counterparty transaction: %s - %s',
                $counterpartyId,
                $reason
            ),
            transactionId: $transactionId,
            amount: $amount,
            currency: $currency,
            evidence: [
                'counterparty_id' => $counterpartyId,
                'reason' => $reason,
            ],
        );
    }

    /**
     * Create a dormant account reactivation alert
     */
    public static function dormancyReactivation(
        string $transactionId,
        int $dormantDays,
        float $amount,
        string $currency = 'USD',
    ): self {
        return new self(
            type: self::TYPE_DORMANCY,
            severity: TransactionMonitoringResult::SEVERITY_MEDIUM,
            message: sprintf(
                'Dormant account reactivated: First activity in %d days with %s %s transaction',
                $dormantDays,
                number_format($amount, 2),
                $currency
            ),
            transactionId: $transactionId,
            amount: $amount,
            currency: $currency,
            evidence: [
                'dormant_days' => $dormantDays,
                'reactivation_amount' => $amount,
            ],
        );
    }

    /**
     * Create from array (for hydration)
     * 
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: (string) ($data['type'] ?? self::TYPE_PATTERN),
            severity: (string) ($data['severity'] ?? TransactionMonitoringResult::SEVERITY_LOW),
            message: (string) ($data['message'] ?? ''),
            transactionId: $data['transaction_id'] ?? null,
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            currency: $data['currency'] ?? null,
            triggeredAt: $data['triggered_at'] instanceof \DateTimeImmutable
                ? $data['triggered_at']
                : new \DateTimeImmutable($data['triggered_at'] ?? 'now'),
            evidence: (array) ($data['evidence'] ?? []),
        );
    }

    /**
     * Check if this is a critical alert
     */
    public function isCritical(): bool
    {
        return $this->severity === TransactionMonitoringResult::SEVERITY_CRITICAL;
    }

    /**
     * Check if this is a high-severity alert
     */
    public function isHighSeverity(): bool
    {
        return $this->severity === TransactionMonitoringResult::SEVERITY_HIGH
            || $this->severity === TransactionMonitoringResult::SEVERITY_CRITICAL;
    }

    /**
     * Get the alert type label
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_STRUCTURING => 'Structuring Detection',
            self::TYPE_VELOCITY => 'Velocity Anomaly',
            self::TYPE_GEOGRAPHIC => 'Geographic Risk',
            self::TYPE_AMOUNT => 'Large Amount',
            self::TYPE_COUNTERPARTY => 'Counterparty Risk',
            self::TYPE_PATTERN => 'Pattern Detection',
            self::TYPE_THRESHOLD => 'Threshold Breach',
            self::TYPE_DORMANCY => 'Account Dormancy',
            default => 'Alert',
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
            'type' => $this->type,
            'type_label' => $this->getTypeLabel(),
            'severity' => $this->severity,
            'message' => $this->message,
            'transaction_id' => $this->transactionId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'triggered_at' => $this->triggeredAt->format(\DateTimeInterface::ATOM),
            'is_critical' => $this->isCritical(),
            'is_high_severity' => $this->isHighSeverity(),
            'evidence' => $this->evidence,
        ];
    }
}

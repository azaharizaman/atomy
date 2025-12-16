<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Services;

use Nexus\AmlCompliance\Contracts\TransactionMonitorInterface;
use Nexus\AmlCompliance\Enums\JurisdictionRisk;
use Nexus\AmlCompliance\ValueObjects\TransactionAlert;
use Nexus\AmlCompliance\ValueObjects\TransactionMonitoringResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Transaction Monitor Service
 * 
 * Production-ready implementation of transaction monitoring for AML compliance.
 * Detects suspicious patterns including:
 * - Structuring (smurfing)
 * - Velocity anomalies
 * - Geographic anomalies
 * - Round amount patterns
 * - Dormancy reactivation
 */
final class TransactionMonitor implements TransactionMonitorInterface
{
    /**
     * Default monitoring thresholds
     */
    private array $thresholds;

    public function __construct(
        private readonly LoggerInterface $logger = new NullLogger(),
        array $thresholds = [],
    ) {
        $this->thresholds = array_merge([
            'structuring_threshold' => 10000.0,      // Currency threshold (USD default)
            'structuring_margin' => 0.15,            // 15% below threshold
            'structuring_min_transactions' => 3,     // Minimum transactions to flag
            'velocity_multiplier' => 3.0,            // 3x normal is anomaly
            'dormancy_days' => 180,                  // 6 months dormancy
            'round_amount_threshold' => 0.8,         // 80% round amounts is suspicious
            'geographic_max_countries' => 5,         // Max countries in period
            'large_transaction_threshold' => 50000.0,// Large single transaction
            'daily_limit' => 25000.0,                // Daily aggregation limit
        ], $thresholds);
    }

    /**
     * {@inheritdoc}
     */
    public function monitor(
        string $partyId,
        array $transactions,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd
    ): TransactionMonitoringResult {
        $this->logger->debug('Starting transaction monitoring', [
            'party_id' => $partyId,
            'transaction_count' => count($transactions),
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
        ]);

        if (empty($transactions)) {
            return new TransactionMonitoringResult(
                partyId: $partyId,
                isSuspicious: false,
                riskScore: 0,
                reasons: [],
                alerts: [],
                patterns: [],
                analyzedAt: new \DateTimeImmutable(),
                periodStart: $periodStart,
                periodEnd: $periodEnd,
                transactionCount: 0,
                totalVolume: 0.0,
            );
        }

        $alerts = [];
        $patterns = [];
        $reasons = [];

        // Normalize transactions
        $normalizedTransactions = $this->normalizeTransactions($transactions);

        // Calculate totals
        $totalVolume = array_sum(array_column($normalizedTransactions, 'amount'));
        $transactionCount = count($normalizedTransactions);

        // Run detection algorithms
        if ($this->detectStructuring($partyId, $normalizedTransactions)) {
            $patterns[] = 'structuring';
            $reasons[] = 'Potential structuring detected - multiple transactions just below reporting threshold';
            $alerts[] = $this->createStructuringAlert($partyId, $normalizedTransactions);
        }

        // Detect velocity anomalies
        $velocityAlert = $this->detectVelocityAnomalyInternal($partyId, $normalizedTransactions);
        if ($velocityAlert !== null) {
            $patterns[] = 'velocity';
            $reasons[] = 'Unusual transaction velocity detected';
            $alerts[] = $velocityAlert;
        }

        // Detect geographic anomalies
        $geoAlerts = $this->detectGeographicAnomalyInternal($partyId, $normalizedTransactions);
        if (!empty($geoAlerts)) {
            $patterns[] = 'geographic';
            $reasons[] = 'Geographic anomalies detected';
            $alerts = array_merge($alerts, $geoAlerts);
        }

        // Detect round amount patterns
        if ($this->detectRoundAmountPattern($partyId, $normalizedTransactions)) {
            $patterns[] = 'round_amounts';
            $reasons[] = 'Suspicious round amount pattern detected';
            $alerts[] = $this->createRoundAmountAlert($partyId, $normalizedTransactions);
        }

        // Detect large transactions
        $largeTransactionAlerts = $this->detectLargeTransactions($partyId, $normalizedTransactions);
        if (!empty($largeTransactionAlerts)) {
            $patterns[] = 'large_amount';
            $reasons[] = 'Large transaction(s) detected';
            $alerts = array_merge($alerts, $largeTransactionAlerts);
        }

        // Detect daily aggregation exceeding thresholds
        $dailyAlerts = $this->detectDailyAggregation($partyId, $normalizedTransactions);
        if (!empty($dailyAlerts)) {
            $patterns[] = 'daily_aggregation';
            $reasons[] = 'Daily transaction aggregation exceeds threshold';
            $alerts = array_merge($alerts, $dailyAlerts);
        }

        // Calculate overall risk score
        $isSuspicious = !empty($alerts);
        $riskScore = $this->calculateRiskScoreInternal($alerts, $patterns);

        $result = new TransactionMonitoringResult(
            partyId: $partyId,
            isSuspicious: $isSuspicious,
            riskScore: $riskScore,
            reasons: $reasons,
            alerts: $alerts,
            patterns: $patterns,
            analyzedAt: new \DateTimeImmutable(),
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            transactionCount: $transactionCount,
            totalVolume: $totalVolume,
        );

        $this->logger->info('Transaction monitoring completed', [
            'party_id' => $partyId,
            'is_suspicious' => $isSuspicious,
            'risk_score' => $riskScore,
            'alert_count' => count($alerts),
            'patterns_detected' => $patterns,
        ]);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function detectStructuring(
        string $partyId,
        array $transactions,
        float $threshold = 10000.0
    ): bool {
        $effectiveThreshold = $threshold ?: $this->thresholds['structuring_threshold'];
        $margin = $this->thresholds['structuring_margin'];
        $lowerBound = $effectiveThreshold * (1 - $margin);

        $suspiciousTransactions = array_filter(
            $transactions,
            fn(array $tx) => $tx['amount'] >= $lowerBound && $tx['amount'] < $effectiveThreshold
        );

        return count($suspiciousTransactions) >= $this->thresholds['structuring_min_transactions'];
    }

    /**
     * {@inheritdoc}
     */
    public function detectVelocityAnomaly(
        string $partyId,
        array $transactions,
        array $historicalData,
        float $multiplierThreshold = 3.0
    ): bool {
        $currentCount = count($transactions);
        $averageCount = $historicalData['average_transaction_count'] ?? 10.0;

        if ($averageCount <= 0) {
            return false;
        }

        $multiplier = $currentCount / $averageCount;
        return $multiplier >= $multiplierThreshold;
    }

    /**
     * {@inheritdoc}
     */
    public function detectGeographicAnomaly(
        string $partyId,
        array $transactions,
        array $expectedCountries
    ): bool {
        $transactionCountries = [];

        foreach ($transactions as $tx) {
            $country = $tx['counterparty_country'] ?? null;
            if ($country !== null) {
                $transactionCountries[$country] = true;
            }
        }

        // Check for unexpected countries
        foreach (array_keys($transactionCountries) as $country) {
            if (!in_array(strtoupper($country), array_map('strtoupper', $expectedCountries), true)) {
                return true;
            }
        }

        // Check for high-risk countries
        foreach (array_keys($transactionCountries) as $country) {
            $risk = JurisdictionRisk::fromCountryCode($country);
            if ($risk === JurisdictionRisk::HIGH || $risk === JurisdictionRisk::VERY_HIGH) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function detectDormancyReactivation(
        string $partyId,
        ?\DateTimeImmutable $lastActivityDate,
        array $transactions,
        int $dormancyThresholdDays = 180
    ): bool {
        if ($lastActivityDate === null || empty($transactions)) {
            return false;
        }

        $now = new \DateTimeImmutable();
        $daysSinceActivity = $lastActivityDate->diff($now)->days;

        if ($daysSinceActivity < $dormancyThresholdDays) {
            return false;
        }

        // Account was dormant and now has transactions
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function detectRoundAmountPattern(
        string $partyId,
        array $transactions,
        float $percentageThreshold = 0.8
    ): bool {
        if (count($transactions) < 5) {
            return false;
        }

        $roundAmounts = 0;

        foreach ($transactions as $tx) {
            $amount = $tx['amount'] ?? 0.0;
            // Check if amount is a round number (divisible by 100, 500, or 1000)
            if ($this->isRoundAmount($amount)) {
                $roundAmounts++;
            }
        }

        $roundPercentage = $roundAmounts / count($transactions);
        return $roundPercentage >= $percentageThreshold;
    }

    /**
     * {@inheritdoc}
     */
    public function calculateRiskScore(TransactionMonitoringResult $result): int
    {
        return $result->riskScore;
    }

    /**
     * {@inheritdoc}
     */
    public function shouldConsiderSar(TransactionMonitoringResult $result): bool
    {
        return $result->shouldConsiderSar();
    }

    /**
     * {@inheritdoc}
     */
    public function getThresholds(): array
    {
        return $this->thresholds;
    }

    /**
     * {@inheritdoc}
     */
    public function setThresholds(array $thresholds): void
    {
        $this->thresholds = array_merge($this->thresholds, $thresholds);
    }

    /**
     * Normalize transaction data
     */
    private function normalizeTransactions(array $transactions): array
    {
        return array_map(function (array $tx): array {
            $dateValue = $tx['date'] ?? null;
            return [
                'id' => (string) ($tx['id'] ?? uniqid('tx_')),
                'amount' => (float) ($tx['amount'] ?? 0.0),
                'currency' => (string) ($tx['currency'] ?? 'USD'),
                'type' => (string) ($tx['type'] ?? 'unknown'),
                'date' => $dateValue instanceof \DateTimeImmutable
                    ? $dateValue
                    : new \DateTimeImmutable((string) ($dateValue ?? 'now')),
                'counterparty_id' => $tx['counterparty_id'] ?? null,
                'counterparty_country' => isset($tx['counterparty_country'])
                    ? strtoupper($tx['counterparty_country'])
                    : null,
                'description' => $tx['description'] ?? '',
                'metadata' => $tx['metadata'] ?? [],
            ];
        }, $transactions);
    }

    /**
     * Check if amount is a "round" number
     */
    private function isRoundAmount(float $amount): bool
    {
        // Check divisibility by common round amounts
        if (fmod($amount, 1000) === 0.0) {
            return true;
        }
        if (fmod($amount, 500) === 0.0) {
            return true;
        }
        if ($amount >= 100 && fmod($amount, 100) === 0.0) {
            return true;
        }

        return false;
    }

    /**
     * Calculate risk score based on alerts and patterns
     */
    private function calculateRiskScoreInternal(array $alerts, array $patterns): int
    {
        if (empty($alerts)) {
            return 0;
        }

        $patternWeights = [
            'structuring' => 35,
            'velocity' => 20,
            'geographic' => 25,
            'round_amounts' => 15,
            'large_amount' => 20,
            'daily_aggregation' => 25,
            'dormancy' => 15,
        ];

        $baseScore = 0;
        foreach ($patterns as $pattern) {
            $baseScore += $patternWeights[$pattern] ?? 10;
        }

        // Add alert severity
        $severityMultiplier = 1.0;
        foreach ($alerts as $alert) {
            if ($alert instanceof TransactionAlert) {
                $severityMultiplier = max($severityMultiplier, match ($alert->severity) {
                    'critical' => 1.5,
                    'high' => 1.3,
                    'medium' => 1.1,
                    default => 1.0,
                });
            }
        }

        return min(100, (int) round($baseScore * $severityMultiplier));
    }

    /**
     * Detect velocity anomaly and create alert
     */
    private function detectVelocityAnomalyInternal(string $partyId, array $transactions): ?TransactionAlert
    {
        // Group by day
        $dailyCounts = [];
        foreach ($transactions as $tx) {
            $date = $tx['date']->format('Y-m-d');
            $dailyCounts[$date] = ($dailyCounts[$date] ?? 0) + 1;
        }

        if (count($dailyCounts) < 2) {
            return null;
        }

        $avgDailyCount = array_sum($dailyCounts) / count($dailyCounts);
        $maxDailyCount = max($dailyCounts);

        if ($avgDailyCount > 0 && $maxDailyCount / $avgDailyCount >= $this->thresholds['velocity_multiplier']) {
            return TransactionAlert::velocitySpike(
                currentCount: (int) $maxDailyCount,
                averageCount: $avgDailyCount,
                periodDays: count($dailyCounts)
            );
        }

        return null;
    }

    /**
     * Detect geographic anomalies and create alerts
     * 
     * @return array<TransactionAlert>
     */
    private function detectGeographicAnomalyInternal(string $partyId, array $transactions): array
    {
        $alerts = [];
        $countries = [];

        foreach ($transactions as $tx) {
            $country = $tx['counterparty_country'] ?? null;
            if ($country === null) {
                continue;
            }

            $countries[$country] = ($countries[$country] ?? 0) + 1;

            // Check for high-risk countries
            $risk = JurisdictionRisk::fromCountryCode($country);
            if ($risk === JurisdictionRisk::HIGH || $risk === JurisdictionRisk::VERY_HIGH) {
                $alerts[] = TransactionAlert::geographicAnomaly(
                    countryCode: $country,
                    transactionId: $tx['id'],
                    reason: sprintf('Transaction with %s jurisdiction', $risk->value)
                );
            }
        }

        // Check for too many countries
        if (count($countries) > $this->thresholds['geographic_max_countries']) {
            $alerts[] = TransactionAlert::geographicAnomaly(
                countryCode: 'MULTIPLE',
                reason: sprintf(
                    'Transactions across %d countries (threshold: %d)',
                    count($countries),
                    $this->thresholds['geographic_max_countries']
                )
            );
        }

        return $alerts;
    }

    /**
     * Create structuring alert
     */
    private function createStructuringAlert(string $partyId, array $transactions): TransactionAlert
    {
        $threshold = $this->thresholds['structuring_threshold'];
        $margin = $this->thresholds['structuring_margin'];
        $lowerBound = $threshold * (1 - $margin);

        $suspiciousTransactions = array_filter(
            $transactions,
            fn(array $tx) => $tx['amount'] >= $lowerBound && $tx['amount'] < $threshold
        );

        $totalAmount = array_sum(array_column($suspiciousTransactions, 'amount'));

        return TransactionAlert::structuring(
            cumulativeAmount: $totalAmount,
            transactionCount: count($suspiciousTransactions),
            threshold: $threshold
        );
    }

    /**
     * Create round amount alert
     */
    private function createRoundAmountAlert(string $partyId, array $transactions): TransactionAlert
    {
        $roundCount = 0;
        foreach ($transactions as $tx) {
            if ($this->isRoundAmount($tx['amount'])) {
                $roundCount++;
            }
        }

        $percentage = count($transactions) > 0 ? ($roundCount / count($transactions)) * 100 : 0;

        return new TransactionAlert(
            type: TransactionAlert::TYPE_PATTERN,
            severity: 'medium',
            message: sprintf(
                'Round amount pattern: %d of %d transactions (%.1f%%) are round amounts',
                $roundCount,
                count($transactions),
                $percentage
            ),
            triggeredAt: new \DateTimeImmutable(),
            evidence: [
                'round_count' => $roundCount,
                'total_count' => count($transactions),
                'percentage' => $percentage,
            ]
        );
    }

    /**
     * Detect large transactions
     * 
     * @return array<TransactionAlert>
     */
    private function detectLargeTransactions(string $partyId, array $transactions): array
    {
        $alerts = [];
        $threshold = $this->thresholds['large_transaction_threshold'];

        foreach ($transactions as $tx) {
            if ($tx['amount'] >= $threshold) {
                $alerts[] = TransactionAlert::largeAmount(
                    amount: $tx['amount'],
                    currency: $tx['currency'],
                    transactionId: $tx['id'],
                    threshold: $threshold
                );
            }
        }

        return $alerts;
    }

    /**
     * Detect daily aggregation exceeding thresholds
     * 
     * @return array<TransactionAlert>
     */
    private function detectDailyAggregation(string $partyId, array $transactions): array
    {
        $alerts = [];
        $dailyLimit = $this->thresholds['daily_limit'];

        // Group by date
        $dailyTotals = [];
        foreach ($transactions as $tx) {
            $date = $tx['date']->format('Y-m-d');
            $dailyTotals[$date] = ($dailyTotals[$date] ?? 0.0) + $tx['amount'];
        }

        // Check each day
        foreach ($dailyTotals as $date => $total) {
            if ($total >= $dailyLimit) {
                $alerts[] = TransactionAlert::thresholdBreach(
                    transactionId: 'aggregate-' . $date,
                    amount: $total,
                    thresholdType: 'daily_aggregate',
                    currency: 'USD'
                );
            }
        }

        return $alerts;
    }
}

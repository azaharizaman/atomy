<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Contracts;

use Nexus\AmlCompliance\ValueObjects\TransactionMonitoringResult;

/**
 * Transaction Monitor interface
 * 
 * Defines contract for real-time and batch transaction monitoring services.
 * Implementations detect suspicious patterns including:
 * - Structuring (smurfing)
 * - Velocity anomalies
 * - Geographic anomalies
 * - Round amount patterns
 * - Dormancy reactivation
 */
interface TransactionMonitorInterface
{
    /**
     * Monitor transactions for a party within a time period
     * 
     * @param string $partyId Party identifier
     * @param array<array{
     *     id: string,
     *     amount: float,
     *     currency: string,
     *     type: string,
     *     date: \DateTimeImmutable|string,
     *     counterparty_id?: string,
     *     counterparty_country?: string,
     *     description?: string,
     *     metadata?: array
     * }> $transactions List of transactions to analyze
     * @param \DateTimeImmutable $periodStart Start of analysis period
     * @param \DateTimeImmutable $periodEnd End of analysis period
     * @return TransactionMonitoringResult Analysis result with alerts
     * 
     * @throws \Nexus\AmlCompliance\Exceptions\InvalidTransactionException
     */
    public function monitor(
        string $partyId,
        array $transactions,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd
    ): TransactionMonitoringResult;

    /**
     * Detect structuring pattern (transactions just below reporting threshold)
     * 
     * @param string $partyId
     * @param array $transactions
     * @param float $threshold Reporting threshold (default: 10,000)
     * @return bool True if structuring pattern detected
     */
    public function detectStructuring(
        string $partyId,
        array $transactions,
        float $threshold = 10000.0
    ): bool;

    /**
     * Detect velocity anomalies (unusual transaction frequency)
     * 
     * @param string $partyId
     * @param array $transactions Current period transactions
     * @param array $historicalData Historical baseline data
     * @param float $multiplierThreshold Multiplier above baseline (default: 3.0x)
     * @return bool True if velocity anomaly detected
     */
    public function detectVelocityAnomaly(
        string $partyId,
        array $transactions,
        array $historicalData,
        float $multiplierThreshold = 3.0
    ): bool;

    /**
     * Detect geographic anomalies
     * 
     * @param string $partyId
     * @param array $transactions
     * @param array<string> $expectedCountries Countries expected based on party profile
     * @return bool True if geographic anomaly detected
     */
    public function detectGeographicAnomaly(
        string $partyId,
        array $transactions,
        array $expectedCountries
    ): bool;

    /**
     * Detect dormancy reactivation
     * 
     * @param string $partyId
     * @param \DateTimeImmutable|null $lastActivityDate Last known activity date
     * @param array $transactions Recent transactions
     * @param int $dormancyThresholdDays Days of inactivity to consider dormant (default: 180)
     * @return bool True if dormancy reactivation detected
     */
    public function detectDormancyReactivation(
        string $partyId,
        ?\DateTimeImmutable $lastActivityDate,
        array $transactions,
        int $dormancyThresholdDays = 180
    ): bool;

    /**
     * Detect round amount patterns
     * 
     * @param string $partyId
     * @param array $transactions
     * @param float $percentageThreshold Percentage of round amounts to flag (default: 0.8 = 80%)
     * @return bool True if round amount pattern detected
     */
    public function detectRoundAmountPattern(
        string $partyId,
        array $transactions,
        float $percentageThreshold = 0.8
    ): bool;

    /**
     * Calculate risk score based on transaction patterns
     * 
     * @param TransactionMonitoringResult $result
     * @return int Risk score 0-100
     */
    public function calculateRiskScore(TransactionMonitoringResult $result): int;

    /**
     * Check if monitoring result warrants SAR consideration
     */
    public function shouldConsiderSar(TransactionMonitoringResult $result): bool;

    /**
     * Get configurable monitoring thresholds
     * 
     * @return array<string, mixed>
     */
    public function getThresholds(): array;

    /**
     * Set monitoring thresholds
     * 
     * @param array<string, mixed> $thresholds
     */
    public function setThresholds(array $thresholds): void;
}

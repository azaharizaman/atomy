<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Exceptions;

/**
 * Exception thrown for invalid or suspicious transactions
 */
final class InvalidTransactionException extends AmlException
{
    /**
     * Create for structuring detection
     */
    public static function structuringDetected(
        string $partyId,
        float $totalAmount,
        int $transactionCount,
        float $threshold
    ): self {
        return new self(
            message: sprintf(
                'Potential structuring detected for party %s: %d transactions totaling %.2f (threshold: %.2f)',
                $partyId,
                $transactionCount,
                $totalAmount,
                $threshold
            ),
            code: 3001,
            context: [
                'party_id' => $partyId,
                'total_amount' => $totalAmount,
                'transaction_count' => $transactionCount,
                'threshold' => $threshold,
                'pattern' => 'structuring',
            ]
        );
    }

    /**
     * Create for velocity spike detection
     */
    public static function velocitySpike(
        string $partyId,
        int $currentCount,
        float $averageCount,
        float $multiplier
    ): self {
        return new self(
            message: sprintf(
                'Transaction velocity spike for party %s: %d transactions (average: %.1f, multiplier: %.1fx)',
                $partyId,
                $currentCount,
                $averageCount,
                $multiplier
            ),
            code: 3002,
            context: [
                'party_id' => $partyId,
                'current_count' => $currentCount,
                'average_count' => $averageCount,
                'multiplier' => $multiplier,
                'pattern' => 'velocity',
            ]
        );
    }

    /**
     * Create for geographic anomaly
     */
    public static function geographicAnomaly(
        string $partyId,
        string $transactionId,
        string $countryCode,
        string $reason
    ): self {
        return new self(
            message: sprintf(
                'Geographic anomaly for party %s transaction %s: country "%s" - %s',
                $partyId,
                $transactionId,
                $countryCode,
                $reason
            ),
            code: 3003,
            context: [
                'party_id' => $partyId,
                'transaction_id' => $transactionId,
                'country_code' => $countryCode,
                'reason' => $reason,
                'pattern' => 'geographic',
            ]
        );
    }

    /**
     * Create for high-risk counterparty
     */
    public static function highRiskCounterparty(
        string $partyId,
        string $transactionId,
        string $counterpartyId,
        string $reason
    ): self {
        return new self(
            message: sprintf(
                'High-risk counterparty in transaction %s for party %s: counterparty %s - %s',
                $transactionId,
                $partyId,
                $counterpartyId,
                $reason
            ),
            code: 3004,
            context: [
                'party_id' => $partyId,
                'transaction_id' => $transactionId,
                'counterparty_id' => $counterpartyId,
                'reason' => $reason,
                'pattern' => 'counterparty',
            ]
        );
    }

    /**
     * Create for threshold breach
     */
    public static function thresholdBreach(
        string $partyId,
        string $transactionId,
        float $amount,
        float $threshold,
        string $thresholdType
    ): self {
        return new self(
            message: sprintf(
                'Threshold breach in transaction %s for party %s: amount %.2f exceeds %s threshold %.2f',
                $transactionId,
                $partyId,
                $amount,
                $thresholdType,
                $threshold
            ),
            code: 3005,
            context: [
                'party_id' => $partyId,
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'threshold' => $threshold,
                'threshold_type' => $thresholdType,
                'pattern' => 'threshold',
            ]
        );
    }

    /**
     * Create for dormancy reactivation
     */
    public static function dormancyReactivation(
        string $partyId,
        int $dormantDays,
        float $transactionAmount
    ): self {
        return new self(
            message: sprintf(
                'Dormant account reactivation for party %s: inactive for %d days, first transaction %.2f',
                $partyId,
                $dormantDays,
                $transactionAmount
            ),
            code: 3006,
            context: [
                'party_id' => $partyId,
                'dormant_days' => $dormantDays,
                'transaction_amount' => $transactionAmount,
                'pattern' => 'dormancy',
            ]
        );
    }

    /**
     * Create for round amount pattern
     */
    public static function roundAmountPattern(
        string $partyId,
        int $roundAmountCount,
        int $totalCount,
        float $percentage
    ): self {
        return new self(
            message: sprintf(
                'Suspicious round amount pattern for party %s: %d of %d transactions (%.1f%%) are round amounts',
                $partyId,
                $roundAmountCount,
                $totalCount,
                $percentage
            ),
            code: 3007,
            context: [
                'party_id' => $partyId,
                'round_amount_count' => $roundAmountCount,
                'total_count' => $totalCount,
                'percentage' => $percentage,
                'pattern' => 'round_amounts',
            ]
        );
    }

    /**
     * Create for layering detection
     */
    public static function layeringDetected(
        string $partyId,
        int $layerCount,
        array $entityChain
    ): self {
        return new self(
            message: sprintf(
                'Potential layering detected for party %s: %d layers involving %d entities',
                $partyId,
                $layerCount,
                count($entityChain)
            ),
            code: 3008,
            context: [
                'party_id' => $partyId,
                'layer_count' => $layerCount,
                'entity_chain' => $entityChain,
                'pattern' => 'layering',
            ]
        );
    }
}

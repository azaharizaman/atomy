<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Exceptions;

/**
 * Exception thrown when risk assessment fails
 */
final class RiskAssessmentFailedException extends AmlException
{
    /**
     * Create for missing party data
     */
    public static function missingPartyData(string $partyId, array $missingFields): self
    {
        return new self(
            message: sprintf(
                'Risk assessment failed for party %s: missing required fields (%s)',
                $partyId,
                implode(', ', $missingFields)
            ),
            code: 2001,
            context: [
                'party_id' => $partyId,
                'missing_fields' => $missingFields,
            ]
        );
    }

    /**
     * Create for invalid jurisdiction
     */
    public static function invalidJurisdiction(string $partyId, string $countryCode): self
    {
        return new self(
            message: sprintf(
                'Risk assessment failed for party %s: invalid jurisdiction code "%s"',
                $partyId,
                $countryCode
            ),
            code: 2002,
            context: [
                'party_id' => $partyId,
                'country_code' => $countryCode,
            ]
        );
    }

    /**
     * Create for sanctions check failure
     */
    public static function sanctionsCheckFailed(string $partyId, string $reason): self
    {
        return new self(
            message: sprintf(
                'Risk assessment failed for party %s: sanctions check error - %s',
                $partyId,
                $reason
            ),
            code: 2003,
            context: [
                'party_id' => $partyId,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Create for calculation error
     */
    public static function calculationError(string $partyId, string $component, string $reason): self
    {
        return new self(
            message: sprintf(
                'Risk assessment calculation error for party %s in component "%s": %s',
                $partyId,
                $component,
                $reason
            ),
            code: 2004,
            context: [
                'party_id' => $partyId,
                'component' => $component,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Create for threshold exceeded
     */
    public static function thresholdExceeded(string $partyId, int $score, int $threshold): self
    {
        return new self(
            message: sprintf(
                'Party %s exceeds risk threshold: score %d (threshold: %d)',
                $partyId,
                $score,
                $threshold
            ),
            code: 2005,
            context: [
                'party_id' => $partyId,
                'score' => $score,
                'threshold' => $threshold,
            ]
        );
    }

    /**
     * Create for prohibited jurisdiction
     */
    public static function prohibitedJurisdiction(string $partyId, string $countryCode): self
    {
        return new self(
            message: sprintf(
                'Risk assessment blocked for party %s: jurisdiction "%s" is prohibited (FATF blacklist)',
                $partyId,
                $countryCode
            ),
            code: 2006,
            context: [
                'party_id' => $partyId,
                'country_code' => $countryCode,
                'list_type' => 'FATF_BLACKLIST',
            ]
        );
    }

    /**
     * Create for assessment timeout
     */
    public static function timeout(string $partyId, float $durationSeconds): self
    {
        return new self(
            message: sprintf(
                'Risk assessment timeout for party %s after %.2f seconds',
                $partyId,
                $durationSeconds
            ),
            code: 2007,
            context: [
                'party_id' => $partyId,
                'duration_seconds' => $durationSeconds,
            ]
        );
    }
}

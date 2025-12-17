<?php

declare(strict_types=1);

namespace Nexus\PDPA\Exceptions;

use RuntimeException;

/**
 * PDPA-specific exception factory.
 */
final class PdpaException extends RuntimeException
{
    /**
     * Request deadline exceeded.
     */
    public static function deadlineExceeded(int $daysOverdue): self
    {
        return new self(
            "PDPA 21-day response deadline exceeded by {$daysOverdue} days (Section 30 violation)."
        );
    }

    /**
     * Extension limit already reached.
     */
    public static function extensionLimitExceeded(): self
    {
        return new self(
            'PDPA deadline has already been extended. Maximum extension is 14 days.'
        );
    }

    /**
     * Extension period exceeds maximum.
     */
    public static function invalidExtensionDuration(int $requested): self
    {
        return new self(
            "Extension of {$requested} days exceeds maximum of 14 days allowed under PDPA."
        );
    }

    /**
     * Extension period too long.
     */
    public static function extensionTooLong(int $requested, int $maximum): self
    {
        return new self(
            "Requested extension of {$requested} days exceeds maximum of {$maximum} days."
        );
    }

    /**
     * Consent required for processing.
     */
    public static function consentRequired(string $purpose): self
    {
        return new self(
            "PDPA Section 6 violation: Consent required for processing data for purpose '{$purpose}'."
        );
    }

    /**
     * Sensitive data violation.
     */
    public static function sensitiveDataViolation(string $category): self
    {
        return new self(
            "PDPA Section 40 violation: Explicit consent required for processing '{$category}' data."
        );
    }

    /**
     * Notice requirement violation.
     */
    public static function noticeRequirementViolation(): self
    {
        return new self(
            'PDPA Section 7 violation: Privacy notice must be provided to data subject.'
        );
    }

    /**
     * Security breach detected.
     */
    public static function securityBreach(string $details): self
    {
        return new self(
            "PDPA Section 9 violation: Security breach detected. {$details}"
        );
    }

    /**
     * Retention period violation.
     */
    public static function retentionViolation(int $expected, int $actual): self
    {
        return new self(
            "PDPA Section 10 violation: Data retained for {$actual} days exceeds expected {$expected} days."
        );
    }

    /**
     * Data integrity violation.
     */
    public static function integrityViolation(string $reason): self
    {
        return new self(
            "PDPA Section 11 violation: {$reason}"
        );
    }

    /**
     * Access principle violation.
     */
    public static function accessPrincipleViolation(string $reason): self
    {
        return new self(
            "PDPA Section 12 violation: {$reason}"
        );
    }

    /**
     * Cross-border transfer violation.
     */
    public static function crossBorderTransferViolation(string $country): self
    {
        return new self(
            "PDPA Section 129 violation: Cross-border transfer to '{$country}' requires approval."
        );
    }

    /**
     * Request not found.
     */
    public static function requestNotFound(string $requestId): self
    {
        return new self(
            "Data subject request '{$requestId}' not found."
        );
    }

    /**
     * Invalid request status transition.
     */
    public static function invalidRequestStatus(string $current, string $target): self
    {
        return new self(
            "Cannot transition request from '{$current}' to '{$target}'."
        );
    }

    /**
     * Missing consent mechanism.
     */
    public static function missingConsent(string $purpose): self
    {
        return new self(
            "PDPA Section 6 violation: Consent must be obtained for processing data for purpose '{$purpose}'."
        );
    }

    /**
     * Missing data protection measures.
     */
    public static function insufficientSecurityMeasures(): self
    {
        return new self(
            'PDPA Section 9 violation: Practical steps must be taken to protect personal data from unauthorized access.'
        );
    }

    /**
     * Data retained beyond necessary period.
     */
    public static function excessiveRetention(string $dataType, int $daysBeyondLimit): self
    {
        return new self(
            "PDPA Section 10 violation: Personal data '{$dataType}' retained {$daysBeyondLimit} days " .
            'beyond necessary period. Data should be destroyed or anonymized.'
        );
    }

    /**
     * Processing for purposes beyond original consent.
     */
    public static function purposeLimitation(string $originalPurpose, string $newPurpose): self
    {
        return new self(
            "PDPA Section 6(3) violation: Data collected for '{$originalPurpose}' " .
            "cannot be processed for incompatible purpose '{$newPurpose}' without new consent."
        );
    }

    /**
     * Data subject rights violation.
     */
    public static function dataSubjectRightViolation(string $right): self
    {
        return new self(
            "PDPA violation: Data subject right to '{$right}' has been denied without valid grounds."
        );
    }

    /**
     * Generic PDPA non-compliance.
     */
    public static function nonCompliance(string $section, string $description): self
    {
        return new self(
            "PDPA Section {$section} violation: {$description}"
        );
    }
}

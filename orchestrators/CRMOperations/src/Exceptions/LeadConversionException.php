<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Exceptions;

/**
 * Lead Conversion Exception
 * 
 * Thrown when lead conversion fails due to validation, state, or integration errors.
 * 
 * @package Nexus\CRMOperations\Exceptions
 */
final class LeadConversionException extends CRMOperationsException
{
    /**
     * Create for invalid lead status
     */
    public static function invalidLeadStatus(string $leadId, string $currentStatus): self
    {
        return new self(
            message: "Lead {$leadId} cannot be converted in status '{$currentStatus}'",
            context: "lead_id: {$leadId}, status: {$currentStatus}"
        );
    }

    /**
     * Create for missing required fields
     */
    public static function missingRequiredFields(string $leadId, array $fields): self
    {
        return new self(
            message: "Lead {$leadId} missing required fields: " . implode(', ', $fields),
            context: "lead_id: {$leadId}, fields: " . implode(', ', $fields)
        );
    }

    /**
     * Create for customer creation failure
     */
    public static function customerCreationFailed(string $leadId, string $reason): self
    {
        return new self(
            message: "Failed to create customer from lead {$leadId}: {$reason}",
            context: "lead_id: {$leadId}"
        );
    }

    /**
     * Create for opportunity creation failure
     */
    public static function opportunityCreationFailed(string $leadId, string $reason): self
    {
        return new self(
            message: "Failed to create opportunity from lead {$leadId}: {$reason}",
            context: "lead_id: {$leadId}"
        );
    }
}

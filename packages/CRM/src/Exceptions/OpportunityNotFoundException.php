<?php

declare(strict_types=1);

namespace Nexus\CRM\Exceptions;

/**
 * Opportunity Not Found Exception
 * 
 * Thrown when an opportunity cannot be found by the specified criteria.
 * 
 * @package Nexus\CRM\Exceptions
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
class OpportunityNotFoundException extends CRMException
{
    /**
     * @param string $identifier The opportunity identifier that was not found
     */
    public function __construct(string $identifier)
    {
        parent::__construct(
            sprintf('Opportunity not found with identifier: %s', $identifier)
        );
    }

    /**
     * Create exception for ID lookup
     */
    public static function forId(string $id): self
    {
        return new self("ID '{$id}'");
    }

    /**
     * Create exception for source lead lookup
     */
    public static function forSourceLead(string $leadId): self
    {
        return new self("source lead ID '{$leadId}'");
    }
}
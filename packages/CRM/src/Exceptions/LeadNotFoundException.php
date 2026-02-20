<?php

declare(strict_types=1);

namespace Nexus\CRM\Exceptions;

/**
 * Lead Not Found Exception
 * 
 * Thrown when a lead cannot be found by the specified criteria.
 * 
 * @package Nexus\CRM\Exceptions
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
class LeadNotFoundException extends CRMException
{
    /**
     * @param string $identifier The lead identifier that was not found
     */
    public function __construct(string $identifier)
    {
        parent::__construct(
            sprintf('Lead not found with identifier: %s', $identifier)
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
     * Create exception for external ref lookup
     */
    public static function forExternalRef(string $externalRef): self
    {
        return new self("external reference '{$externalRef}'");
    }
}
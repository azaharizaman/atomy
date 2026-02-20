<?php

declare(strict_types=1);

namespace Nexus\CRM\Exceptions;

/**
 * Pipeline Not Found Exception
 * 
 * Thrown when a pipeline cannot be found by the specified criteria.
 * 
 * @package Nexus\CRM\Exceptions
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
class PipelineNotFoundException extends CRMException
{
    /**
     * @param string $identifier The pipeline identifier that was not found
     */
    public function __construct(string $identifier)
    {
        parent::__construct(
            sprintf('Pipeline not found with identifier: %s', $identifier)
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
     * Create exception for name lookup
     */
    public static function forName(string $name): self
    {
        return new self("name '{$name}'");
    }

    /**
     * Create exception for default pipeline lookup
     */
    public static function noDefaultPipeline(): self
    {
        return new self('No default pipeline configured for tenant');
    }
}
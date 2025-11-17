<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Exceptions;

use Exception;

/**
 * Exception thrown when a company is not found.
 */
class CompanyNotFoundException extends Exception
{
    public function __construct(string $identifier, ?string $identifierType = 'ID')
    {
        parent::__construct("Company not found with {$identifierType}: {$identifier}");
    }
}

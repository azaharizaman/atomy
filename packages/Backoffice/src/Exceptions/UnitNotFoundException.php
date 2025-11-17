<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Exceptions;

use Exception;

class UnitNotFoundException extends Exception
{
    public function __construct(string $identifier, ?string $identifierType = 'ID')
    {
        parent::__construct("Unit not found with {$identifierType}: {$identifier}");
    }
}

<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Exceptions;

use Exception;

class TransferNotFoundException extends Exception
{
    public function __construct(string $identifier)
    {
        parent::__construct("Transfer not found with ID: {$identifier}");
    }
}

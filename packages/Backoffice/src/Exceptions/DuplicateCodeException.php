<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Exceptions;

use Exception;

class DuplicateCodeException extends Exception
{
    public function __construct(string $entityType, string $code, string $scope = 'system')
    {
        parent::__construct("{$entityType} code '{$code}' already exists in {$scope}");
    }
}

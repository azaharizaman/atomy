<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Exceptions;

use Exception;

/**
 * Exception thrown when a sequence is not found.
 */
class SequenceNotFoundException extends Exception
{
    public static function byNameAndScope(string $name, ?string $scope = null): self
    {
        $scopeText = $scope ? " and scope '{$scope}'" : '';
        return new self("Sequence with name '{$name}'{$scopeText} not found");
    }

    public static function byId(string $id): self
    {
        return new self("Sequence with ID '{$id}' not found");
    }
}

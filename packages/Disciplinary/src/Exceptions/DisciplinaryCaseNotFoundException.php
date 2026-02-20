<?php

declare(strict_types=1);

namespace Nexus\Disciplinary\Exceptions;

final class DisciplinaryCaseNotFoundException extends \Exception
{
    public function __construct(string $caseId)
    {
        parent::__construct("Disciplinary case not found: {$caseId}");
    }
}

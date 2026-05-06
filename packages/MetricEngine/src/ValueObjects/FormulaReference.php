<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

use Nexus\MetricEngine\Exceptions\FormulaValidationException;

final readonly class FormulaReference
{
    public function __construct(
        public string $identifier
    ) {
        if (trim($identifier) === '') {
            throw new FormulaValidationException('Formula reference identifier is required.');
        }
    }
}

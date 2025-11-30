<?php

declare(strict_types=1);

namespace Nexus\Budget\Exceptions;

use Nexus\Budget\Enums\BudgetingMethodology;

/**
 * Justification Required Exception
 * 
 * Thrown when creating a Zero-Based Budget without required justification.
 */
final class JustificationRequiredException extends BudgetException
{
    public function __construct(
        private readonly BudgetingMethodology $methodology,
        string $message = '',
        int $code = 400
    ) {
        $message = $message ?: sprintf(
            'Justification required for %s methodology',
            $methodology->label()
        );
        parent::__construct($message, $code);
    }

    public function getMethodology(): BudgetingMethodology
    {
        return $this->methodology;
    }
}

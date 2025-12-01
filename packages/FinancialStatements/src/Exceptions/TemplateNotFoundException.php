<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Exceptions;

/**
 * Exception thrown when a statement template is not found.
 */
final class TemplateNotFoundException extends \RuntimeException
{
    public function __construct(
        string $templateId,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct("Template not found: {$templateId}", $code, $previous);
    }
}

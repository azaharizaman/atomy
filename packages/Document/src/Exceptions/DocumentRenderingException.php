<?php

declare(strict_types=1);

namespace Nexus\Document\Exceptions;

/**
 * Exception thrown when document rendering fails.
 */
class DocumentRenderingException extends \RuntimeException
{
    public function __construct(
        public readonly string $templateName,
        public readonly string $format,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $message = $message ?: "Failed to render template '{$templateName}' to format '{$format}'";
        parent::__construct($message, $code, $previous);
    }
}

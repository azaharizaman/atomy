<?php

declare(strict_types=1);

namespace Nexus\Document\Exceptions;

/**
 * Exception thrown when content analysis fails.
 */
class ContentAnalysisException extends \RuntimeException
{
    public function __construct(
        public readonly string $documentPath,
        public readonly string $analysisType,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $message = $message ?: "Content analysis '{$analysisType}' failed for document: {$documentPath}";
        parent::__construct($message, $code, $previous);
    }
}

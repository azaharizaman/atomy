<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Exceptions;

/**
 * Duplicate Posting Exception
 * 
 * Thrown when a duplicate posting is detected based on source document and line.
 */
final class DuplicatePostingException extends GeneralLedgerException
{
    public function __construct(string $sourceDocumentId, string $lineId)
    {
        parent::__construct(
            sprintf('Duplicate posting detected for document %s, line %s', $sourceDocumentId, $lineId)
        );
    }
}

<?php

declare(strict_types=1);

namespace Nexus\Import\Contracts;

use Nexus\Import\ValueObjects\ImportDefinition;
use Nexus\Import\ValueObjects\ImportMetadata;
use Nexus\Import\ValueObjects\ImportFormat;

/**
 * Import parser contract
 * 
 * Parsers convert external file formats to unified ImportDefinition.
 * Native parsers (CSV, JSON, XML) are in package.
 * Framework-dependent parsers (Excel) are in Atomy.
 */
interface ImportParserInterface
{
    /**
     * Parse file into ImportDefinition
     * 
     * @param string $filePath Absolute path to import file
     * @param ImportMetadata $metadata Import metadata
     * @return ImportDefinition Parsed data in intermediate representation
     * @throws \Nexus\Import\Exceptions\ParserException
     */
    public function parse(string $filePath, ImportMetadata $metadata): ImportDefinition;

    /**
     * Check if parser supports given format
     */
    public function supports(ImportFormat $format): bool;

    /**
     * Parse file with streaming support for large files
     * 
     * @param string $filePath Absolute path to import file
     * @param ImportMetadata $metadata Import metadata
     * @param callable $callback Callback function to process chunks
     * @param int $chunkSize Number of rows per chunk
     */
    public function parseStream(
        string $filePath,
        ImportMetadata $metadata,
        callable $callback,
        int $chunkSize = 100
    ): void;
}

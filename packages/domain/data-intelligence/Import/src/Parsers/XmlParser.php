<?php

declare(strict_types=1);

namespace Nexus\Import\Parsers;

use Nexus\Import\Contracts\ImportParserInterface;
use Nexus\Import\ValueObjects\ImportDefinition;
use Nexus\Import\ValueObjects\ImportMetadata;
use Nexus\Import\ValueObjects\ImportFormat;
use Nexus\Import\Exceptions\ParserException;

/**
 * XML parser implementation
 * 
 * Parses XML documents to ImportDefinition.
 */
final class XmlParser implements ImportParserInterface
{
    public function __construct(
        private readonly string $recordElement = 'record',
        private readonly bool $treatAttributesAsFields = true
    ) {}

    public function parse(
        string $filePath,
        ImportMetadata $metadata
    ): ImportDefinition {
        if (!file_exists($filePath)) {
            throw new ParserException("File not found: {$filePath}");
        }

        libxml_use_internal_errors(true);
        
        $xml = simplexml_load_file($filePath);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            
            $errorMessage = !empty($errors) 
                ? $errors[0]->message 
                : 'Unknown XML parsing error';
                
            throw new ParserException("Invalid XML: {$errorMessage}");
        }

        $records = $xml->xpath("//{$this->recordElement}");
        
        if ($records === false || empty($records)) {
            return new ImportDefinition(
                headers: [],
                rows: [],
                metadata: $metadata
            );
        }

        // Extract headers from first record
        $headers = $this->extractHeaders($records[0]);
        
        // Parse all records
        $rows = array_map(
            fn($record) => $this->parseRecord($record, $headers),
            $records
        );

        return new ImportDefinition(
            headers: $headers,
            rows: $rows,
            metadata: $metadata
        );
    }

    public function supports(ImportFormat $format): bool
    {
        return $format === ImportFormat::XML;
    }

    public function parseStream(
        string $filePath,
        ImportMetadata $metadata,
        callable $callback,
        int $chunkSize = 100
    ): void {
        // XML streaming requires XMLReader for memory efficiency
        // For simplicity, parse entire file and chunk the result
        $definition = $this->parse($filePath, $metadata);
        
        $chunks = array_chunk($definition->rows, $chunkSize, true);
        
        foreach ($chunks as $index => $chunk) {
            $callback($chunk, $index + 1);
        }
    }

    /**
     * Extract headers from XML element
     * 
     * @return string[]
     */
    private function extractHeaders(\SimpleXMLElement $element): array
    {
        $headers = [];

        // Add attributes as headers
        if ($this->treatAttributesAsFields) {
            foreach ($element->attributes() as $name => $value) {
                $headers[] = "@{$name}";
            }
        }

        // Add child elements as headers
        foreach ($element->children() as $name => $child) {
            if (!in_array($name, $headers, true)) {
                $headers[] = $name;
            }
        }

        return $headers;
    }

    /**
     * Parse single XML record to associative array
     */
    private function parseRecord(\SimpleXMLElement $element, array $headers): array
    {
        $row = array_fill_keys($headers, null);

        // Parse attributes
        if ($this->treatAttributesAsFields) {
            foreach ($element->attributes() as $name => $value) {
                $row["@{$name}"] = (string) $value;
            }
        }

        // Parse child elements
        foreach ($element->children() as $name => $child) {
            $row[$name] = $this->parseValue($child);
        }

        return $row;
    }

    /**
     * Parse XML element value
     */
    private function parseValue(\SimpleXMLElement $element): mixed
    {
        // Check if element has children
        $children = $element->children();
        
        if (count($children) > 0) {
            // Has children - return as nested array
            $result = [];
            foreach ($children as $name => $child) {
                $result[$name] = $this->parseValue($child);
            }
            return $result;
        }

        // No children - return text value
        return (string) $element;
    }
}

<?php

declare(strict_types=1);

namespace Nexus\Export\Core\Formatters;

use Generator;
use Nexus\Export\Contracts\ExportFormatterInterface;
use Nexus\Export\Exceptions\FormatterException;
use Nexus\Export\ValueObjects\ExportDefinition;
use Nexus\Export\ValueObjects\ExportFormat;
use Nexus\Export\ValueObjects\ExportSection;
use Nexus\Export\ValueObjects\TableStructure;
use SimpleXMLElement;

/**
 * XML formatter implementation
 * 
 * Converts ExportDefinition to XML format.
 * Uses SimpleXMLElement for DOM construction.
 */
final class XmlFormatter implements ExportFormatterInterface
{
    /**
     * Format export definition as XML string
     * 
     * @throws FormatterException
     */
    public function format(ExportDefinition $definition): string
    {
        try {
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><export/>');
            
            // Add metadata
            $metadata = $xml->addChild('metadata');
            $metadata->addChild('title', htmlspecialchars($definition->metadata->title, ENT_XML1));
            
            if ($definition->metadata->author) {
                $metadata->addChild('author', htmlspecialchars($definition->metadata->author, ENT_XML1));
            }
            
            if ($definition->metadata->generatedAt) {
                $metadata->addChild('generatedAt', $definition->metadata->generatedAt->format('c'));
            }
            
            $metadata->addChild('schemaVersion', $definition->metadata->schemaVersion);

            // Add structure
            $structure = $xml->addChild('structure');
            
            foreach ($definition->structure as $section) {
                $this->addSection($structure, $section);
            }

            // Format with indentation
            $dom = dom_import_simplexml($xml)->ownerDocument;
            if ($dom === null) {
                throw new FormatterException('Failed to create DOM document');
            }
            
            $dom->formatOutput = true;
            $output = $dom->saveXML();
            
            return $output !== false ? $output : '';
            
        } catch (\Throwable $e) {
            throw new FormatterException("XML formatting failed: {$e->getMessage()}", previous: $e);
        }
    }

    /**
     * Stream export definition as XML generator
     * 
     * For XML, true streaming is complex due to tree structure.
     * We generate the full XML then yield in chunks.
     * 
     * @return Generator<string>
     * @throws FormatterException
     */
    public function stream(ExportDefinition $definition): Generator
    {
        try {
            $xml = $this->format($definition);
            
            // Yield in 8KB chunks
            $chunkSize = 8192;
            $offset = 0;
            $length = strlen($xml);

            while ($offset < $length) {
                yield substr($xml, $offset, $chunkSize);
                $offset += $chunkSize;
            }
            
        } catch (\Throwable $e) {
            throw new FormatterException("XML streaming failed: {$e->getMessage()}", previous: $e);
        }
    }

    /**
     * Get supported export format
     */
    public function getFormat(): ExportFormat
    {
        return ExportFormat::XML;
    }

    /**
     * Check if streaming is supported (chunked only)
     */
    public function supportsStreaming(): bool
    {
        return false; // Full DOM construction required
    }

    /**
     * Check if formatter requires external service
     */
    public function requiresExternalService(): bool
    {
        return false;
    }

    /**
     * Get required schema version
     */
    public function requiresSchemaVersion(): string
    {
        return '>=1.0';
    }

    /**
     * Add section to XML element recursively
     */
    private function addSection(SimpleXMLElement $parent, ExportSection $section): void
    {
        $sectionElement = $parent->addChild('section');
        $sectionElement->addAttribute('level', (string) $section->level);
        
        if ($section->name) {
            $sectionElement->addAttribute('name', htmlspecialchars($section->name, ENT_XML1));
        }

        foreach ($section->items as $item) {
            if ($item instanceof TableStructure) {
                $this->addTable($sectionElement, $item);
            } elseif ($item instanceof ExportSection) {
                $this->addSection($sectionElement, $item);
            } elseif (is_string($item)) {
                $sectionElement->addChild('text', htmlspecialchars($item, ENT_XML1));
            } elseif (is_array($item)) {
                $this->addArray($sectionElement, $item);
            }
        }
    }

    /**
     * Add table to XML element
     */
    private function addTable(SimpleXMLElement $parent, TableStructure $table): void
    {
        $tableElement = $parent->addChild('table');

        // Headers
        $thead = $tableElement->addChild('thead');
        $headerRow = $thead->addChild('tr');
        foreach ($table->headers as $header) {
            $headerRow->addChild('th', htmlspecialchars((string) $header, ENT_XML1));
        }

        // Rows
        $tbody = $tableElement->addChild('tbody');
        foreach ($table->rows as $row) {
            $rowElement = $tbody->addChild('tr');
            foreach ($row as $cell) {
                $rowElement->addChild('td', htmlspecialchars((string) $cell, ENT_XML1));
            }
        }

        // Footers
        if (!empty($table->footers)) {
            $tfoot = $tableElement->addChild('tfoot');
            $footerRow = $tfoot->addChild('tr');
            foreach ($table->footers as $footer) {
                $footerRow->addChild('td', htmlspecialchars((string) $footer, ENT_XML1));
            }
        }
    }

    /**
     * Add array as key-value pairs
     * 
     * @param array<string, mixed> $data
     */
    private function addArray(SimpleXMLElement $parent, array $data): void
    {
        $dataElement = $parent->addChild('data');
        
        foreach ($data as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $dataElement->addChild(
                    $this->sanitizeTagName($key),
                    htmlspecialchars((string) $value, ENT_XML1)
                );
            }
        }
    }

    /**
     * Sanitize key name for use as XML tag
     */
    private function sanitizeTagName(string $name): string
    {
        // Remove invalid XML tag characters
        $sanitized = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name);
        
        // Ensure starts with letter or underscore
        if ($sanitized && !preg_match('/^[a-zA-Z_]/', $sanitized)) {
            $sanitized = '_' . $sanitized;
        }

        return $sanitized ?: 'item';
    }
}

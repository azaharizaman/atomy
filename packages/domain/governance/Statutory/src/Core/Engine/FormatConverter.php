<?php

declare(strict_types=1);

namespace Nexus\Statutory\Core\Engine;

use Nexus\Statutory\Exceptions\ValidationException;
use Nexus\Statutory\ValueObjects\ReportFormat;
use Psr\Log\LoggerInterface;

/**
 * Format converter for statutory reports.
 * 
 * Converts report data to various formats (JSON, XML, CSV, etc.).
 */
final class FormatConverter
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Convert data to specified format.
     *
     * @param array<string, mixed> $data The data to convert
     * @param ReportFormat $format The target format
     * @return string The converted content
     * @throws ValidationException If conversion fails
     */
    public function convert(array $data, ReportFormat $format): string
    {
        $this->logger->debug("Converting data to format", [
            'format' => $format->value,
            'data_keys' => array_keys($data),
        ]);

        return match ($format) {
            ReportFormat::JSON => $this->toJson($data),
            ReportFormat::XML => $this->toXml($data),
            ReportFormat::CSV => $this->toCsv($data),
            ReportFormat::XBRL => $this->toXbrl($data),
            default => throw new ValidationException(
                'format_conversion',
                ["Unsupported format: {$format->value}"]
            ),
        };
    }

    /**
     * Convert data to JSON format.
     *
     * @param array<string, mixed> $data
     * @return string
     */
    private function toJson(array $data): string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        
        $this->logger->debug("Converted to JSON", [
            'length' => strlen($json),
        ]);

        return $json;
    }

    /**
     * Convert data to XML format.
     *
     * @param array<string, mixed> $data
     * @return string
     */
    private function toXml(array $data): string
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><report></report>');
        $this->arrayToXml($data, $xml);
        
        $result = $xml->asXML();
        if ($result === false) {
            throw new ValidationException('xml_conversion', ['Failed to generate XML']);
        }

        $this->logger->debug("Converted to XML", [
            'length' => strlen($result),
        ]);

        return $result;
    }

    /**
     * Convert data to CSV format.
     *
     * @param array<string, mixed> $data
     * @return string
     */
    private function toCsv(array $data): string
    {
        // If data is a simple array of arrays (table format), convert directly
        if ($this->isTableData($data)) {
            return $this->tableToCsv($data);
        }

        // Otherwise, flatten the data structure
        $flattened = $this->flattenArray($data);
        
        $output = fopen('php://temp', 'r+');
        if ($output === false) {
            throw new ValidationException('csv_conversion', ['Failed to create CSV stream']);
        }

        // Write header
        fputcsv($output, array_keys($flattened));

        // Write data
        fputcsv($output, array_values($flattened));

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        if ($csv === false) {
            throw new ValidationException('csv_conversion', ['Failed to read CSV stream']);
        }

        $this->logger->debug("Converted to CSV", [
            'length' => strlen($csv),
        ]);

        return $csv;
    }

    /**
     * Convert data to XBRL format (placeholder).
     *
     * @param array<string, mixed> $data
     * @return string
     */
    private function toXbrl(array $data): string
    {
        // XBRL is complex and typically requires a dedicated library
        // For now, this is a placeholder that generates basic XBRL structure
        
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><xbrl xmlns="http://www.xbrl.org/2003/instance"></xbrl>');
        $this->arrayToXml($data, $xml);
        
        $result = $xml->asXML();
        if ($result === false) {
            throw new ValidationException('xbrl_conversion', ['Failed to generate XBRL']);
        }

        $this->logger->debug("Converted to XBRL", [
            'length' => strlen($result),
        ]);

        return $result;
    }

    /**
     * Convert array to XML recursively.
     *
     * @param array<string, mixed> $data
     * @param \SimpleXMLElement $xml
     * @return void
     */
    private function arrayToXml(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            // Convert numeric keys to valid XML tag names
            if (is_numeric($key)) {
                $key = "item_{$key}";
            }

            if (is_array($value)) {
                $subnode = $xml->addChild($key);
                $this->arrayToXml($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars((string) $value));
            }
        }
    }

    /**
     * Check if data is in table format (array of rows).
     *
     * @param array<string, mixed> $data
     * @return bool
     */
    private function isTableData(array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $firstValue = reset($data);
        return is_array($firstValue) && !empty($firstValue);
    }

    /**
     * Convert table data to CSV.
     *
     * @param array<array<string, mixed>> $data
     * @return string
     */
    private function tableToCsv(array $data): string
    {
        $output = fopen('php://temp', 'r+');
        if ($output === false) {
            throw new ValidationException('csv_conversion', ['Failed to create CSV stream']);
        }

        $isFirstRow = true;
        foreach ($data as $row) {
            if ($isFirstRow) {
                fputcsv($output, array_keys($row));
                $isFirstRow = false;
            }
            fputcsv($output, array_values($row));
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        if ($csv === false) {
            throw new ValidationException('csv_conversion', ['Failed to read CSV stream']);
        }

        return $csv;
    }

    /**
     * Flatten a nested array into a single-level array.
     *
     * @param array<string, mixed> $array
     * @param string $prefix
     * @return array<string, mixed>
     */
    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];
        
        foreach ($array as $key => $value) {
            $newKey = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        
        return $result;
    }
}

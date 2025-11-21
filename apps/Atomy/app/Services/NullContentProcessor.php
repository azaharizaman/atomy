<?php

declare(strict_types=1);

namespace App\Services;

use Nexus\Document\Contracts\ContentProcessorInterface;
use Nexus\Document\ValueObjects\ContentAnalysisResult;
use Nexus\Document\ValueObjects\DocumentFormat;
use Psr\Log\LoggerInterface;

/**
 * Null content processor (no-op implementation).
 *
 * Default implementation when Intelligence package is not available.
 * Logs warnings and returns safe defaults.
 */
final readonly class NullContentProcessor implements ContentProcessorInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function render(string $templateName, array $data, DocumentFormat $format): string
    {
        $this->logger->warning('Content rendering not configured', [
            'template' => $templateName,
            'format' => $format->value,
            'message' => 'Install Nexus\Intelligence or configure rendering service',
        ]);

        return ''; // Empty content
    }

    public function analyze(string $documentPath): ContentAnalysisResult
    {
        $this->logger->info('Content analysis not configured', [
            'document_path' => $documentPath,
            'message' => 'Install Nexus\Intelligence for ML-driven analysis',
        ]);

        return ContentAnalysisResult::null();
    }

    public function extractText(string $documentPath): string
    {
        $this->logger->warning('Text extraction not configured', [
            'document_path' => $documentPath,
            'message' => 'Install Nexus\Intelligence or configure OCR service',
        ]);

        return '';
    }

    public function redact(string $documentPath, array $patterns): string
    {
        $this->logger->warning('Content redaction not configured', [
            'document_path' => $documentPath,
            'patterns_count' => count($patterns),
            'message' => 'Install Nexus\Intelligence for redaction capability',
        ]);

        return $documentPath; // Return original path unchanged
    }
}

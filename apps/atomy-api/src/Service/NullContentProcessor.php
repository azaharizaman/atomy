<?php

declare(strict_types=1);

namespace App\Service;

use Nexus\Document\Contracts\ContentProcessorInterface;
use Nexus\Document\ValueObjects\ContentAnalysisResult;
use Nexus\Document\ValueObjects\DocumentFormat;
use Psr\Log\LoggerInterface;

/**
 * Placeholder content processor for when ML features are unavailable.
 */
final readonly class NullContentProcessor implements ContentProcessorInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function render(string $templateName, array $data, DocumentFormat $format): string
    {
        $this->logger->warning('Document rendering requested but no renderer is configured', [
            'template' => $templateName,
            'format' => $format->value,
        ]);

        throw new \RuntimeException('Document rendering is not available.');
    }

    public function analyze(string $documentPath): ContentAnalysisResult
    {
        $this->logger->info('Document analysis requested in Null mode', [
            'path' => $documentPath,
        ]);

        return ContentAnalysisResult::null();
    }

    public function extractText(string $documentPath): string
    {
        return '';
    }

    public function redact(string $documentPath, array $patterns): string
    {
        return $documentPath;
    }
}

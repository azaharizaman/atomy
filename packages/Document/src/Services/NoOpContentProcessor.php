<?php

declare(strict_types=1);

namespace Nexus\Document\Services;

use Nexus\Document\Contracts\ContentProcessorInterface;
use Nexus\Document\ValueObjects\ContentAnalysisResult;
use Nexus\Document\ValueObjects\DocumentType;

/**
 * No-op content processor for environments without ML capabilities.
 */
final class NoOpContentProcessor implements ContentProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function analyze(string $storagePath): ContentAnalysisResult
    {
        return new ContentAnalysisResult(
            predictedType: DocumentType::OTHER,
            confidenceScore: 0.0,
            extractedMetadata: [],
            suggestedTags: []
        );
    }

    /**
     * {@inheritdoc}
     */
    public function extractText(string $storagePath): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function generateThumbnail(string $storagePath, int $width, int $height): string
    {
        return '';
    }
}

<?php

declare(strict_types=1);

namespace App\Adapters\InsightOperations\Exceptions;

use DomainException;

final class ProviderInsightNarrativeAdapterException extends DomainException
{
    public static function invalidPayload(): self
    {
        return new self("AI provider returned a non-array payload.");
    }
}

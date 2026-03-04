<?php

declare(strict_types=1);

namespace Nexus\DataExchangeOperations\Rules;

use Nexus\DataExchangeOperations\DTOs\DataOffboardingRequest;

final readonly class OffboardingPreflightRule
{
    private const SUPPORTED_FORMATS = ['csv', 'json', 'pdf', 'xlsx'];

    public function assert(DataOffboardingRequest $request): void
    {
        if (trim($request->destination) === '') {
            throw new \InvalidArgumentException('destination is required for offboarding.');
        }

        if (!in_array(strtolower($request->format), self::SUPPORTED_FORMATS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Unsupported offboarding format "%s". Supported: %s',
                $request->format,
                implode(', ', self::SUPPORTED_FORMATS)
            ));
        }
    }
}

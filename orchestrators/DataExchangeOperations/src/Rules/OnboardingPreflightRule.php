<?php

declare(strict_types=1);

namespace Nexus\DataExchangeOperations\Rules;

use Nexus\DataExchangeOperations\Contracts\StoragePortInterface;
use Nexus\DataExchangeOperations\DTOs\DataOnboardingRequest;

final readonly class OnboardingPreflightRule
{
    public function __construct(private StoragePortInterface $storage) {}

    public function assert(DataOnboardingRequest $request): void
    {
        if ($request->tenantId === '') {
            throw new \InvalidArgumentException('tenantId is required for onboarding.');
        }

        if ($request->sourcePath === '') {
            throw new \InvalidArgumentException('sourcePath is required for onboarding.');
        }

        if (!$this->storage->exists($request->sourcePath) && !is_file($request->sourcePath)) {
            throw new \InvalidArgumentException(sprintf('Onboarding source does not exist: %s', $request->sourcePath));
        }
    }
}

<?php

declare(strict_types=1);

namespace Nexus\ConnectivityOperations\Rules;

use Nexus\ConnectivityOperations\DTOs\ProviderCallRequest;

final class ProviderCallRule
{
    public function assert(ProviderCallRequest $request): void
    {
        if ($request->providerId === '') {
            throw new \InvalidArgumentException('providerId is required.');
        }

        if ($request->endpoint === '') {
            throw new \InvalidArgumentException('endpoint is required.');
        }
    }
}

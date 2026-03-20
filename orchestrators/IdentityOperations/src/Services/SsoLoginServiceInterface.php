<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

use Nexus\IdentityOperations\DTOs\UserContext;

interface SsoLoginServiceInterface
{
    /**
     * @return array{authorization_url: string, state: string}
     */
    public function initiate(string $tenantId, ?string $redirectUriOverride = null): array;

    public function callback(string $tenantId, string $code, string $state): UserContext;
}


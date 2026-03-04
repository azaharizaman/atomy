<?php

declare(strict_types=1);

namespace Nexus\ConnectivityOperations\Contracts;

interface SecretRotationPortInterface
{
    public function rotate(string $providerId): bool;
}

<?php

declare(strict_types=1);

namespace Nexus\Laravel\ConnectivityOperations\Adapters;

use Nexus\ConnectivityOperations\Contracts\SecretRotationPortInterface;
use Nexus\Crypto\Contracts\KeyRotationServiceInterface;

final readonly class SecretRotationPortAdapter implements SecretRotationPortInterface
{
    public function __construct(private KeyRotationServiceInterface $keyRotation) {}

    public function rotate(string $providerId): bool
    {
        $this->keyRotation->rotateKey('integration.' . $providerId . '.api_key');

        return true;
    }
}

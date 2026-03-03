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
        if (trim($providerId) === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $providerId)) {
            throw new \InvalidArgumentException('providerId must be a non-empty alphanumeric identifier with optional "_" or "-".');
        }

        $this->keyRotation->rotateKey('integration.' . $providerId . '.api_key');

        return true;
    }
}

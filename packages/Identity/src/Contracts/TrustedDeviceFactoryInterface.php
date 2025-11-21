<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

use Nexus\Identity\ValueObjects\DeviceFingerprint;

/**
 * Trusted device factory interface
 * 
 * Creates new trusted device instances
 */
interface TrustedDeviceFactoryInterface
{
    /**
     * Create a new trusted device
     * 
     * @param string $userId User identifier
     * @param DeviceFingerprint $fingerprint Device fingerprint
     * @param bool $trustImmediately Whether to trust device immediately
     * @param array<string, mixed> $location Geographic location data
     * @return TrustedDeviceInterface
     */
    public function create(
        string $userId,
        DeviceFingerprint $fingerprint,
        bool $trustImmediately = true,
        array $location = []
    ): TrustedDeviceInterface;
}

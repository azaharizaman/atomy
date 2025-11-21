<?php

declare(strict_types=1);

namespace App\Factories;

use App\Models\TrustedDevice;
use Nexus\Identity\Contracts\TrustedDeviceInterface;
use Nexus\Identity\Contracts\TrustedDeviceFactoryInterface;
use Nexus\Identity\ValueObjects\DeviceFingerprint;

/**
 * Trusted device factory implementation
 */
final readonly class TrustedDeviceFactory implements TrustedDeviceFactoryInterface
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
    ): TrustedDeviceInterface {
        $device = new TrustedDevice();
        $device->user_id = $userId;
        $device->device_fingerprint = $fingerprint->hash;
        $device->device_name = $fingerprint->getDescription();
        $device->is_trusted = $trustImmediately;
        $device->metadata = $fingerprint->toArray();
        $device->geographic_location = $location;
        $device->expires_at = now()->addYear(); // Default 1 year expiry
        
        if ($trustImmediately) {
            $device->trusted_at = now();
        }
        
        return $device;
    }
}

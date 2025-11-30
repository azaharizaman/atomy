<?php

declare(strict_types=1);

namespace Nexus\Identity\Services;

use Nexus\Identity\Contracts\TrustedDeviceInterface;
use Nexus\Identity\Contracts\TrustedDeviceRepositoryInterface;
use Nexus\Identity\Contracts\TrustedDeviceFactoryInterface;
use Nexus\Identity\ValueObjects\DeviceFingerprint;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Trusted device manager service
 * 
 * Manages device trust and recognition for multi-factor authentication
 */
final readonly class TrustedDeviceManager
{
    public function __construct(
        private TrustedDeviceRepositoryInterface $repository,
        private TrustedDeviceFactoryInterface $factory,
        private LoggerInterface $logger = new NullLogger()
    ) {
    }

    /**
     * Register a new device for a user
     * 
     * @param string $userId User identifier
     * @param DeviceFingerprint $fingerprint Device fingerprint
     * @param bool $trustImmediately Whether to trust device immediately (TOFU)
     * @param array<string, mixed> $location Geographic location data
     * @return TrustedDeviceInterface
     */
    public function registerDevice(
        string $userId,
        DeviceFingerprint $fingerprint,
        bool $trustImmediately = true,
        array $location = []
    ): TrustedDeviceInterface {
        // Check if device already exists
        $existing = $this->repository->findByUserIdAndFingerprint($userId, $fingerprint->hash);
        
        if ($existing !== null) {
            $this->logger->info('Device already registered', [
                'user_id' => $userId,
                'fingerprint' => $fingerprint->hash,
            ]);
            
            return $existing;
        }

        // Create new device record using factory
        $device = $this->factory->create($userId, $fingerprint, $trustImmediately, $location);
        
        $this->repository->save($device);

        $this->logger->info('Device registered', [
            'user_id' => $userId,
            'fingerprint' => $fingerprint->hash,
            'trusted' => $trustImmediately,
        ]);

        return $device;
    }

    /**
     * Check if device is recognized for user
     */
    public function isDeviceRecognized(string $userId, string $fingerprint): bool
    {
        return $this->repository->exists($userId, $fingerprint);
    }

    /**
     * Find device by user ID and fingerprint
     */
    public function findByUserIdAndFingerprint(string $userId, string $fingerprint): ?TrustedDeviceInterface
    {
        return $this->repository->findByUserIdAndFingerprint($userId, $fingerprint);
    }

    /**
     * Check if device is trusted for user
     */
    public function isDeviceTrusted(string $userId, string $fingerprint): bool
    {
        $device = $this->repository->findByUserIdAndFingerprint($userId, $fingerprint);
        
        if ($device === null) {
            return false;
        }

        return $device->isTrusted();
    }

    /**
     * Mark device as trusted
     */
    public function trustDevice(string $deviceId): void
    {
        $this->repository->markTrusted($deviceId);

        $this->logger->info('Device marked as trusted', [
            'device_id' => $deviceId,
        ]);
    }

    /**
     * Mark device as untrusted
     */
    public function untrustDevice(string $deviceId): void
    {
        $this->repository->markUntrusted($deviceId);

        $this->logger->warning('Device marked as untrusted', [
            'device_id' => $deviceId,
        ]);
    }

    /**
     * Revoke device access (delete device record)
     */
    public function revokeDevice(string $deviceId): void
    {
        $device = $this->repository->findById($deviceId);
        
        if ($device === null) {
            $this->logger->warning('Attempted to revoke non-existent device', [
                'device_id' => $deviceId,
            ]);
            return;
        }

        $this->repository->delete($deviceId);

        $this->logger->warning('Device access revoked', [
            'device_id' => $deviceId,
            'user_id' => $device->getUserId(),
        ]);
    }

    /**
     * Get all devices for a user
     * 
     * @return array<TrustedDeviceInterface>
     */
    public function getUserDevices(string $userId): array
    {
        return $this->repository->findByUserId($userId);
    }

    /**
     * Get trusted devices for a user
     * 
     * @return array<TrustedDeviceInterface>
     */
    public function getTrustedDevices(string $userId): array
    {
        return $this->repository->findTrustedByUserId($userId);
    }

    /**
     * Update device last used timestamp
     */
    public function updateLastUsed(string $deviceId): void
    {
        $this->repository->updateLastUsed($deviceId, new \DateTimeImmutable());
    }

    /**
     * Revoke all devices for a user
     */
    public function revokeAllDevices(string $userId): void
    {
        $this->repository->deleteByUserId($userId);

        $this->logger->warning('All devices revoked for user', [
            'user_id' => $userId,
        ]);
    }
}

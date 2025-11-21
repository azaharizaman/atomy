<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\TrustedDevice;
use Nexus\Identity\Contracts\TrustedDeviceInterface;
use Nexus\Identity\Contracts\TrustedDeviceRepositoryInterface;

/**
 * Trusted device repository implementation
 */
final readonly class DbTrustedDeviceRepository implements TrustedDeviceRepositoryInterface
{
    public function save(TrustedDeviceInterface $device): void
    {
        if (!($device instanceof TrustedDevice)) {
            throw new \InvalidArgumentException(
                'DbTrustedDeviceRepository only supports saving instances of TrustedDevice.'
            );
        }
        $device->save();
    }

    public function findById(string $id): ?TrustedDeviceInterface
    {
        return TrustedDevice::find($id);
    }

    public function findByFingerprint(string $fingerprint): ?TrustedDeviceInterface
    {
        return TrustedDevice::where('device_fingerprint', $fingerprint)->first();
    }

    /**
     * Find a trusted device by user ID and fingerprint.
     *
     * @param string $userId
     * @param string $fingerprint
     * @return TrustedDeviceInterface|null
     */
    public function findByUserIdAndFingerprint(string $userId, string $fingerprint): ?TrustedDeviceInterface
    {
        return TrustedDevice::where('user_id', $userId)
            ->where('device_fingerprint', $fingerprint)
            ->first();
    }

    public function findByUserId(string $userId): array
    {
        return TrustedDevice::where('user_id', $userId)
            ->orderBy('last_used_at', 'desc')
            ->get()
            ->all();
    }

    public function findTrustedByUserId(string $userId): array
    {
        return TrustedDevice::where('user_id', $userId)
            ->where('is_trusted', true)
            ->valid()
            ->orderBy('last_used_at', 'desc')
            ->get()
            ->all();
    }

    public function exists(string $userId, string $fingerprint): bool
    {
        return TrustedDevice::where('user_id', $userId)
            ->where('device_fingerprint', $fingerprint)
            ->exists();
    }

    public function delete(string $id): void
    {
        TrustedDevice::where('id', $id)->delete();
    }

    public function deleteByUserId(string $userId): void
    {
        TrustedDevice::where('user_id', $userId)->delete();
    }

    public function updateLastUsed(string $id, \DateTimeInterface $lastUsedAt): void
    {
        TrustedDevice::where('id', $id)->update([
            'last_used_at' => $lastUsedAt,
        ]);
    }

    public function markTrusted(string $id): void
    {
        TrustedDevice::where('id', $id)->update([
            'is_trusted' => true,
            'trusted_at' => now(),
        ]);
    }

    public function markUntrusted(string $id): void
    {
        TrustedDevice::where('id', $id)->update([
            'is_trusted' => false,
        ]);
    }
}

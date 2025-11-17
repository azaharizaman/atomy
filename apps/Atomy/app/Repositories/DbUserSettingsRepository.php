<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Nexus\Setting\Contracts\SettingRepositoryInterface;
use Nexus\Setting\Exceptions\ReadOnlySettingException;

/**
 * Database-backed repository for user-scoped settings.
 */
class DbUserSettingsRepository implements SettingRepositoryInterface
{
    /**
     * Create a new repository instance.
     */
    public function __construct(
        private readonly string $userId,
    ) {
    }

    /**
     * Retrieve a setting value by key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = Setting::user($this->userId)
            ->byKey($key)
            ->first();

        if ($setting === null) {
            return $default;
        }

        return $setting->is_encrypted ? $setting->getDecryptedValue() : $setting->value;
    }

    /**
     * Store a setting value.
     */
    public function set(string $key, mixed $value): void
    {
        $setting = Setting::user($this->userId)
            ->byKey($key)
            ->first();

        if ($setting !== null && $setting->is_readonly) {
            throw new ReadOnlySettingException($key);
        }

        if ($setting === null) {
            $setting = new Setting([
                'scope' => 'user',
                'scope_id' => $this->userId,
                'key' => $key,
            ]);
        }

        if ($setting->is_encrypted) {
            $setting->setEncryptedValue($value);
        } else {
            $setting->value = $value;
        }

        $setting->save();
    }

    /**
     * Delete a setting by key.
     */
    public function delete(string $key): void
    {
        $setting = Setting::user($this->userId)
            ->byKey($key)
            ->first();

        if ($setting !== null && $setting->is_readonly) {
            throw new ReadOnlySettingException($key);
        }

        $setting?->delete();
    }

    /**
     * Check if a setting exists.
     */
    public function has(string $key): bool
    {
        return Setting::user($this->userId)
            ->byKey($key)
            ->exists();
    }

    /**
     * Retrieve all settings.
     */
    public function getAll(): array
    {
        return Setting::user($this->userId)
            ->get()
            ->mapWithKeys(fn (Setting $setting) => [
                $setting->key => $setting->is_encrypted
                    ? $setting->getDecryptedValue()
                    : $setting->value,
            ])
            ->toArray();
    }

    /**
     * Retrieve all settings matching a key prefix.
     */
    public function getByPrefix(string $prefix): array
    {
        return Setting::user($this->userId)
            ->byKeyPrefix($prefix)
            ->get()
            ->mapWithKeys(fn (Setting $setting) => [
                $setting->key => $setting->is_encrypted
                    ? $setting->getDecryptedValue()
                    : $setting->value,
            ])
            ->toArray();
    }

    /**
     * Retrieve setting metadata.
     */
    public function getMetadata(string $key): ?array
    {
        $setting = Setting::user($this->userId)
            ->byKey($key)
            ->first();

        if ($setting === null) {
            return null;
        }

        return [
            'key' => $setting->key,
            'type' => $setting->type,
            'description' => $setting->description,
            'validation_rules' => $setting->validation_rules,
            'group' => $setting->group,
            'is_readonly' => $setting->is_readonly,
            'is_protected' => $setting->is_protected,
            'is_encrypted' => $setting->is_encrypted,
        ];
    }

    /**
     * Bulk update multiple settings in a single transaction.
     */
    public function bulkSet(array $settings): void
    {
        DB::transaction(function () use ($settings) {
            foreach ($settings as $key => $value) {
                $this->set($key, $value);
            }
        });
    }

    /**
     * Check if this repository is writable.
     */
    public function isWritable(): bool
    {
        return true;
    }
}

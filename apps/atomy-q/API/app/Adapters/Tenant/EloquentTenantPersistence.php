<?php

declare(strict_types=1);

namespace App\Adapters\Tenant;

use App\Models\Tenant;
use Nexus\Tenant\Contracts\TenantInterface;
use Nexus\Tenant\Contracts\TenantPersistenceInterface;
use Nexus\Tenant\Exceptions\TenantNotFoundException;

/**
 * Eloquent persistence adapter for the Tenant package write model.
 */
final readonly class EloquentTenantPersistence implements TenantPersistenceInterface
{
    public function create(array $data): TenantInterface
    {
        $now = now();

        /** @var Tenant $tenant */
        $tenant = Tenant::query()->create([
            'code' => strtoupper(trim((string) ($data['code'] ?? ''))),
            'name' => trim((string) ($data['name'] ?? '')),
            'email' => strtolower(trim((string) ($data['email'] ?? ''))),
            'domain' => $this->normalizeNullableString($data['domain'] ?? null),
            'subdomain' => $this->normalizeNullableString($data['subdomain'] ?? null),
            'database_name' => $this->normalizeNullableString($data['database_name'] ?? null),
            'status' => trim((string) ($data['status'] ?? 'pending')),
            'timezone' => trim((string) ($data['timezone'] ?? 'UTC')) ?: 'UTC',
            'locale' => trim((string) ($data['locale'] ?? 'en')) ?: 'en',
            'currency' => trim((string) ($data['currency'] ?? 'USD')) ?: 'USD',
            'date_format' => trim((string) ($data['date_format'] ?? 'Y-m-d')) ?: 'Y-m-d',
            'time_format' => trim((string) ($data['time_format'] ?? 'H:i')) ?: 'H:i',
            'parent_id' => $this->normalizeNullableString($data['parent_id'] ?? null),
            'metadata' => $this->normalizeMetadata($data['metadata'] ?? null),
            'trial_ends_at' => $data['trial_ends_at'] ?? null,
            'storage_quota' => $data['storage_quota'] ?? null,
            'storage_used' => (int) ($data['storage_used'] ?? 0),
            'max_users' => $data['max_users'] ?? null,
            'rate_limit' => $data['rate_limit'] ?? null,
            'is_readonly' => (bool) ($data['is_readonly'] ?? false),
            'billing_cycle_starts_at' => $data['billing_cycle_starts_at'] ?? null,
            'onboarding_progress' => (int) ($data['onboarding_progress'] ?? 0),
            'retention_hold_until' => $data['retention_hold_until'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $tenant;
    }

    public function update(string $id, array $data): TenantInterface
    {
        $tenant = Tenant::query()->withTrashed()->find($id);

        if ($tenant === null) {
            throw TenantNotFoundException::byId($id);
        }

        $payload = [];
        if (array_key_exists('code', $data)) {
            $payload['code'] = strtoupper(trim((string) $data['code']));
        }
        if (array_key_exists('name', $data)) {
            $payload['name'] = trim((string) $data['name']);
        }
        if (array_key_exists('email', $data)) {
            $payload['email'] = strtolower(trim((string) $data['email']));
        }
        if (array_key_exists('domain', $data)) {
            $payload['domain'] = $this->normalizeNullableString($data['domain']);
        }
        if (array_key_exists('subdomain', $data)) {
            $payload['subdomain'] = $this->normalizeNullableString($data['subdomain']);
        }
        if (array_key_exists('database_name', $data)) {
            $payload['database_name'] = $this->normalizeNullableString($data['database_name']);
        }
        if (array_key_exists('status', $data)) {
            $payload['status'] = trim((string) $data['status']);
        }
        if (array_key_exists('timezone', $data)) {
            $payload['timezone'] = trim((string) $data['timezone']) ?: 'UTC';
        }
        if (array_key_exists('locale', $data)) {
            $payload['locale'] = trim((string) $data['locale']) ?: 'en';
        }
        if (array_key_exists('currency', $data)) {
            $payload['currency'] = trim((string) $data['currency']) ?: 'USD';
        }
        if (array_key_exists('date_format', $data)) {
            $payload['date_format'] = trim((string) $data['date_format']) ?: 'Y-m-d';
        }
        if (array_key_exists('time_format', $data)) {
            $payload['time_format'] = trim((string) $data['time_format']) ?: 'H:i';
        }
        if (array_key_exists('parent_id', $data)) {
            $payload['parent_id'] = $this->normalizeNullableString($data['parent_id']);
        }
        if (array_key_exists('metadata', $data)) {
            $payload['metadata'] = $this->normalizeMetadata($data['metadata']);
        }
        if (array_key_exists('trial_ends_at', $data)) {
            $payload['trial_ends_at'] = $data['trial_ends_at'];
        }
        if (array_key_exists('storage_quota', $data)) {
            $payload['storage_quota'] = $data['storage_quota'];
        }
        if (array_key_exists('storage_used', $data)) {
            $payload['storage_used'] = (int) $data['storage_used'];
        }
        if (array_key_exists('max_users', $data)) {
            $payload['max_users'] = $data['max_users'];
        }
        if (array_key_exists('rate_limit', $data)) {
            $payload['rate_limit'] = $data['rate_limit'];
        }
        if (array_key_exists('is_readonly', $data)) {
            $payload['is_readonly'] = (bool) $data['is_readonly'];
        }
        if (array_key_exists('billing_cycle_starts_at', $data)) {
            $payload['billing_cycle_starts_at'] = $data['billing_cycle_starts_at'];
        }
        if (array_key_exists('onboarding_progress', $data)) {
            $payload['onboarding_progress'] = (int) $data['onboarding_progress'];
        }
        if (array_key_exists('retention_hold_until', $data)) {
            $payload['retention_hold_until'] = $data['retention_hold_until'];
        }

        if ($payload !== []) {
            $payload['updated_at'] = now();
            $tenant->fill($payload);
            $tenant->save();
        }

        return $tenant->fresh() ?? $tenant;
    }

    public function delete(string $id): bool
    {
        $tenant = Tenant::query()->withTrashed()->find($id);

        if ($tenant === null) {
            throw TenantNotFoundException::byId($id);
        }

        if (! $tenant->trashed()) {
            $tenant->status = 'archived';
            $tenant->updated_at = now();
            $tenant->save();
            $tenant->delete();
        }

        return true;
    }

    public function forceDelete(string $id): bool
    {
        $tenant = Tenant::query()->withTrashed()->find($id);

        if ($tenant === null) {
            throw TenantNotFoundException::byId($id);
        }

        return (bool) $tenant->forceDelete();
    }

    public function restore(string $id): TenantInterface
    {
        $tenant = Tenant::query()->withTrashed()->find($id);

        if ($tenant === null) {
            throw TenantNotFoundException::byId($id);
        }

        if ($tenant->trashed()) {
            $tenant->restore();
            $tenant->status = 'active';
            $tenant->updated_at = now();
            $tenant->save();
        }

        return $tenant->fresh() ?? $tenant;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeMetadata(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}

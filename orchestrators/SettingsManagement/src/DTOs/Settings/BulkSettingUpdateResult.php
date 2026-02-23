<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\Settings;

/**
 * Result DTO for bulk setting update operation.
 * 
 * @param array<SettingUpdateResult> $results Individual setting update results
 * @param array<string, mixed> $failedKeys Keys that failed to update
 */
final class BulkSettingUpdateResult
{
    /**
     * @param array<SettingUpdateResult> $results
     * @param array<string, mixed> $failedKeys
     */
    public function __construct(
        public readonly bool $success,
        public readonly array $results = [],
        public readonly array $failedKeys = [],
        public readonly ?string $error = null,
    ) {}

    public static function success(array $results): self
    {
        return new self(success: true, results: $results);
    }

    public static function failure(string $error, array $failedKeys = []): self
    {
        return new self(success: false, error: $error, failedKeys: $failedKeys);
    }

    public function countSuccessful(): int
    {
        return count(array_filter($this->results, fn($r) => $r->success));
    }

    public function countFailed(): int
    {
        return count($this->failedKeys);
    }
}

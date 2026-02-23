<?php

declare(strict_types=1);

namespace Nexus\Laravel\Setting\Adapters;

use Nexus\Setting\Contracts\SettingsAuthorizerInterface;
use Psr\Log\LoggerInterface;

/**
 * Laravel implementation of SettingsAuthorizerInterface.
 *
 * Uses Laravel's Gate for authorization.
 */
class SettingsAuthorizerAdapter implements SettingsAuthorizerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    /**
     * {@inheritdoc}
     */
    public function canView(string $userId, string $key): bool
    {
        // Implementation would use Laravel's Gate
        // return Gate::forUser($userId)->allows('view-setting', $key);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function canEdit(string $userId, string $key): bool
    {
        // Implementation would use Laravel's Gate
        // return Gate::forUser($userId)->allows('edit-setting', $key);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function canDelete(string $userId, string $key): bool
    {
        // Implementation would use Laravel's Gate
        // return Gate::forUser($userId)->allows('delete-setting', $key);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getViewableKeys(string $userId): array
    {
        // Implementation would return all keys the user can view
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getEditableKeys(string $userId): array
    {
        // Implementation would return all keys the user can edit
        return [];
    }
}

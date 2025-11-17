<?php

declare(strict_types=1);

namespace Nexus\Setting\Contracts;

/**
 * Authorization contract for settings access control.
 *
 * This interface defines the authorization contract for controlling
 * who can view and edit specific settings.
 */
interface SettingsAuthorizerInterface
{
    /**
     * Check if a user can view a specific setting.
     *
     * @param string $userId The user ID
     * @param string $key The setting key
     * @return bool True if user can view
     */
    public function canView(string $userId, string $key): bool;

    /**
     * Check if a user can edit a specific setting.
     *
     * @param string $userId The user ID
     * @param string $key The setting key
     * @return bool True if user can edit
     */
    public function canEdit(string $userId, string $key): bool;

    /**
     * Check if a user can delete a specific setting.
     *
     * @param string $userId The user ID
     * @param string $key The setting key
     * @return bool True if user can delete
     */
    public function canDelete(string $userId, string $key): bool;

    /**
     * Get all setting keys a user can view.
     *
     * @param string $userId The user ID
     * @return array<string> Array of setting keys
     */
    public function getViewableKeys(string $userId): array;

    /**
     * Get all setting keys a user can edit.
     *
     * @param string $userId The user ID
     * @return array<string> Array of setting keys
     */
    public function getEditableKeys(string $userId): array;
}

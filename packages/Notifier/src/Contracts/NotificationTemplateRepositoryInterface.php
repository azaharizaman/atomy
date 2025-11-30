<?php

declare(strict_types=1);

namespace Nexus\Notifier\Contracts;

/**
 * Notification Template Repository Interface
 *
 * Manages storage and retrieval of notification templates.
 */
interface NotificationTemplateRepositoryInterface
{
    /**
     * Find a template by ID
     *
     * @param string $templateId
     * @return array<string, mixed>|null Template data or null if not found
     */
    public function find(string $templateId): ?array;

    /**
     * Find templates by notification type
     *
     * @param string $notificationType
     * @return array<array<string, mixed>>
     */
    public function findByType(string $notificationType): array;

    /**
     * Save a notification template
     *
     * @param array<string, mixed> $templateData
     * @return string Template ID
     */
    public function save(array $templateData): string;

    /**
     * Delete a template
     *
     * @param string $templateId
     * @return bool True if deleted successfully
     */
    public function delete(string $templateId): bool;

    /**
     * Get all templates
     *
     * @return array<array<string, mixed>>
     */
    public function getAll(): array;
}

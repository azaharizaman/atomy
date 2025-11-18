<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\NotificationTemplate;
use Nexus\Notifier\Contracts\NotificationTemplateRepositoryInterface;

final readonly class DbNotificationTemplateRepository implements NotificationTemplateRepositoryInterface
{
    public function find(string $templateId): ?array
    {
        $template = NotificationTemplate::find($templateId);

        return $template?->toArray();
    }

    public function findByType(string $notificationType): array
    {
        return NotificationTemplate::where('type', $notificationType)
            ->where('is_active', true)
            ->get()
            ->toArray();
    }

    public function save(array $templateData): string
    {
        $template = NotificationTemplate::updateOrCreate(
            ['type' => $templateData['type'], 'locale' => $templateData['locale'] ?? 'en'],
            $templateData
        );

        return $template->id;
    }

    public function delete(string $templateId): bool
    {
        return NotificationTemplate::destroy($templateId) > 0;
    }

    public function getAll(): array
    {
        return NotificationTemplate::where('is_active', true)
            ->get()
            ->toArray();
    }
}

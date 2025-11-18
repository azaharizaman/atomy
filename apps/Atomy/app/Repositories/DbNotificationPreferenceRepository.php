<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\NotificationPreference;
use Nexus\Notifier\Contracts\NotificationPreferenceRepositoryInterface;
use Nexus\Notifier\ValueObjects\Category;
use Nexus\Notifier\ValueObjects\ChannelType;

final readonly class DbNotificationPreferenceRepository implements NotificationPreferenceRepositoryInterface
{
    public function getPreferences(string $recipientId): array
    {
        $preference = NotificationPreference::where('recipient_id', $recipientId)->first();

        if (!$preference) {
            return [
                'channels' => [],
                'categories' => [],
            ];
        }

        return [
            'channels' => $preference->preferred_channels ?? [],
            'categories' => $preference->category_preferences ?? [],
        ];
    }

    public function updatePreferences(string $recipientId, array $preferences): bool
    {
        NotificationPreference::updateOrCreate(
            ['recipient_id' => $recipientId],
            $preferences
        );

        return true;
    }

    public function canSendToRecipient(string $recipientId, Category $category, ChannelType $channel): bool
    {
        $preference = NotificationPreference::where('recipient_id', $recipientId)->first();

        if (!$preference) {
            return true; // Default allow if no preferences set
        }

        // Check global opt-out
        if ($preference->global_opt_out) {
            return false;
        }

        // Check category preferences
        $categoryPrefs = $preference->category_preferences ?? [];
        if (isset($categoryPrefs[$category->value]) && $categoryPrefs[$category->value] === false) {
            // Only allow if category is not opt-outable (transactional/system)
            return !$category->isOptOutable();
        }

        // Check channel preferences
        $channelPrefs = $preference->preferred_channels ?? [];
        if (!empty($channelPrefs) && !in_array($channel->value, $channelPrefs, true)) {
            return false;
        }

        return true;
    }

    public function optOut(string $recipientId, Category $category): bool
    {
        if (!$category->isOptOutable()) {
            return false;
        }

        $preference = NotificationPreference::firstOrCreate(['recipient_id' => $recipientId]);
        $categoryPrefs = $preference->category_preferences ?? [];
        $categoryPrefs[$category->value] = false;
        $preference->category_preferences = $categoryPrefs;
        $preference->save();

        return true;
    }

    public function optIn(string $recipientId, Category $category): bool
    {
        $preference = NotificationPreference::firstOrCreate(['recipient_id' => $recipientId]);
        $categoryPrefs = $preference->category_preferences ?? [];
        $categoryPrefs[$category->value] = true;
        $preference->category_preferences = $categoryPrefs;
        $preference->save();

        return true;
    }

    public function setPreferredChannels(string $recipientId, array $channels): bool
    {
        $preference = NotificationPreference::firstOrCreate(['recipient_id' => $recipientId]);
        $preference->preferred_channels = array_map(fn($ch) => $ch->value, $channels);
        $preference->save();

        return true;
    }
}

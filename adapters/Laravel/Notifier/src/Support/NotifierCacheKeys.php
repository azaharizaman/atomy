<?php

declare(strict_types=1);

namespace Nexus\Laravel\Notifier\Support;

final class NotifierCacheKeys
{
    public const TEMPORARY_PASSWORD_PREFIX = 'notifier:temporary-password:';
    public const STATUS_PREFIX = 'notifier:status:';
    public const CHANNELS_PREFIX = 'notifier:channels:';

    public static function temporaryPassword(string $token): string
    {
        return self::TEMPORARY_PASSWORD_PREFIX . $token;
    }

    public static function status(string $notificationId): string
    {
        return self::STATUS_PREFIX . $notificationId;
    }

    public static function channels(string $notificationId): string
    {
        return self::CHANNELS_PREFIX . $notificationId;
    }
}


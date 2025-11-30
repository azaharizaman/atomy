<?php

declare(strict_types=1);

namespace Nexus\Notifier\Services;

use Nexus\Notifier\Contracts\NotificationInterface;
use Nexus\Notifier\ValueObjects\Category;
use Nexus\Notifier\ValueObjects\NotificationContent;
use Nexus\Notifier\ValueObjects\Priority;

/**
 * Abstract Base Notification
 *
 * Convenient base class for notification implementations.
 * Provides default getContent() implementation.
 */
abstract readonly class AbstractNotification implements NotificationInterface
{
    public function getContent(): NotificationContent
    {
        return new NotificationContent(
            emailData: $this->toEmail(),
            smsText: $this->toSms(),
            pushData: $this->toPush(),
            inAppData: $this->toInApp()
        );
    }

    public function getPriority(): Priority
    {
        return Priority::Normal;
    }

    public function getCategory(): Category
    {
        return Category::Transactional;
    }

    public function getType(): string
    {
        return static::class;
    }
}

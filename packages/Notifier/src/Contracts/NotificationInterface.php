<?php

declare(strict_types=1);

namespace Nexus\Notifier\Contracts;

use Nexus\Notifier\ValueObjects\Priority;
use Nexus\Notifier\ValueObjects\Category;
use Nexus\Notifier\ValueObjects\NotificationContent;

/**
 * Notification Interface
 *
 * All notification definitions must implement this interface.
 * Provides content for all supported channels.
 */
interface NotificationInterface
{
    /**
     * Get email content
     *
     * @return array{subject: string, body: string, attachments?: array<string>}
     */
    public function toEmail(): array;

    /**
     * Get SMS content
     */
    public function toSms(): string;

    /**
     * Get push notification content
     *
     * @return array{title: string, body: string, action?: string, icon?: string}
     */
    public function toPush(): array;

    /**
     * Get in-app message content
     *
     * @return array{title: string, message: string, link?: string, icon?: string}
     */
    public function toInApp(): array;

    /**
     * Get notification priority
     */
    public function getPriority(): Priority;

    /**
     * Get notification category
     */
    public function getCategory(): Category;

    /**
     * Get notification content as value object
     */
    public function getContent(): NotificationContent;

    /**
     * Get unique notification type identifier
     */
    public function getType(): string;
}

<?php

declare(strict_types=1);

namespace Nexus\Reporting\Contracts;

use Nexus\Reporting\ValueObjects\ChannelType;

interface NotificationManagerInterface
{
    /** @param array<int, ChannelType> $channels */
    public function send(NotifiableInterface $recipient, NotificationInterface $notification, array $channels): string;
}

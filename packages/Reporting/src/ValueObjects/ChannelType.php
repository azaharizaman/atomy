<?php

declare(strict_types=1);

namespace Nexus\Reporting\ValueObjects;

enum ChannelType: string
{
    case EMAIL = 'email';
    case SMS = 'sms';
    case PUSH = 'push';
    case IN_APP = 'in_app';
}

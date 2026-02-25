<?php

declare(strict_types=1);

namespace Nexus\CustomerServiceOperations\Enums;

enum MessageChannel: string
{
    case EMAIL = 'email';
    case SMS = 'sms';
    case CHAT = 'chat';
    case PHONE = 'phone';
}

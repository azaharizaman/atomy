<?php

declare(strict_types=1);

namespace Nexus\CustomerServiceOperations\Enums;

enum SupportTier: string
{
    case BASIC = 'basic';
    case PREMIUM = 'premium';
    case ENTERPRISE = 'enterprise';
}

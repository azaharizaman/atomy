<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\FeatureFlags;

/**
 * Enum representing feature flag types.
 */
enum FlagType: string
{
    case BOOLEAN = 'boolean';
    case PERCENTAGE = 'percentage';
    case USER_LIST = 'user_list';
    case IP_BASED = 'ip_based';
    case CUSTOM_RULE = 'custom_rule';
}

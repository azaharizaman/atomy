<?php

declare(strict_types=1);

namespace Nexus\Hrm\ValueObjects;

/**
 * Review type value object.
 */
enum ReviewType: string
{
    case ANNUAL = 'annual';
    case PROBATION = 'probation';
    case MID_YEAR = 'mid_year';
    case PROJECT_END = 'project_end';
    case SELF = 'self';
    case PEER = 'peer';
    case THREE_SIXTY = '360';
    
    public function label(): string
    {
        return match($this) {
            self::ANNUAL => 'Annual Review',
            self::PROBATION => 'Probation Review',
            self::MID_YEAR => 'Mid-Year Review',
            self::PROJECT_END => 'Project End Review',
            self::SELF => 'Self Review',
            self::PEER => 'Peer Review',
            self::THREE_SIXTY => '360-Degree Review',
        };
    }
}

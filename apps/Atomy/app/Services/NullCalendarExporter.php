<?php

declare(strict_types=1);

namespace App\Services;

use Nexus\Scheduler\Contracts\CalendarExporterInterface;
use Nexus\Scheduler\Exceptions\FeatureNotImplementedException;
use Nexus\Scheduler\ValueObjects\ScheduledJob;

/**
 * Null Calendar Exporter
 *
 * No-op implementation for v1.
 * Throws FeatureNotImplementedException for all methods.
 * Will be replaced with actual implementation in v2.
 */
final readonly class NullCalendarExporter implements CalendarExporterInterface
{
    /**
     * Generate iCal format for a scheduled job
     *
     * @throws FeatureNotImplementedException
     */
    public function generateICal(ScheduledJob $job): string
    {
        throw new FeatureNotImplementedException('Calendar export (iCal)');
    }
    
    /**
     * Generate Google Calendar URL for a scheduled job
     *
     * @throws FeatureNotImplementedException
     */
    public function generateGoogleCalendarUrl(ScheduledJob $job): string
    {
        throw new FeatureNotImplementedException('Calendar export (Google Calendar)');
    }
    
    /**
     * Export multiple jobs to iCal file
     *
     * @param ScheduledJob[] $jobs
     * @throws FeatureNotImplementedException
     */
    public function exportMultiple(array $jobs): string
    {
        throw new FeatureNotImplementedException('Calendar export (bulk iCal)');
    }
}

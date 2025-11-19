<?php

declare(strict_types=1);

namespace Nexus\Scheduler\Contracts;

use Nexus\Scheduler\ValueObjects\ScheduledJob;

/**
 * Calendar Exporter Interface
 *
 * @experimental This interface is defined for future calendar integration.
 * Bound to NullCalendarExporter in v1 (throws FeatureNotImplementedException).
 *
 * Planned v2 features:
 * - Generate iCal files
 * - Google Calendar URL generation
 * - Outlook integration
 */
interface CalendarExporterInterface
{
    /**
     * Generate iCal format for a scheduled job
     *
     * @param ScheduledJob $job The job to export
     * @return string iCal formatted string
     * @throws \Nexus\Scheduler\Exceptions\FeatureNotImplementedException
     */
    public function generateICal(ScheduledJob $job): string;
    
    /**
     * Generate Google Calendar URL for a scheduled job
     *
     * @param ScheduledJob $job The job to export
     * @return string Google Calendar URL
     * @throws \Nexus\Scheduler\Exceptions\FeatureNotImplementedException
     */
    public function generateGoogleCalendarUrl(ScheduledJob $job): string;
    
    /**
     * Export multiple jobs to iCal file
     *
     * @param ScheduledJob[] $jobs Array of jobs to export
     * @return string iCal formatted string with multiple events
     * @throws \Nexus\Scheduler\Exceptions\FeatureNotImplementedException
     */
    public function exportMultiple(array $jobs): string;
}

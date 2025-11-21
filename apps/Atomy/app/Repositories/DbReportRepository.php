<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Reporting\ReportDefinition;
use App\Models\Reporting\ReportGenerated;
use App\Models\Reporting\ReportDistributionLog;
use Nexus\Reporting\Contracts\ReportDefinitionInterface;
use Nexus\Reporting\Contracts\ReportRepositoryInterface;
use Nexus\Reporting\ValueObjects\ScheduleType;

/**
 * Eloquent implementation of ReportRepositoryInterface.
 */
final class DbReportRepository implements ReportRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function save(array $data): string
    {
        $report = ReportDefinition::create($data);
        return $report->id;
    }

    /**
     * {@inheritdoc}
     */
    public function findById(string $id): ?ReportDefinitionInterface
    {
        return ReportDefinition::find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findByOwner(string $ownerId): array
    {
        return ReportDefinition::where('owner_id', $ownerId)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    /**
     * {@inheritdoc}
     */
    public function findDueForGeneration(\DateTimeImmutable $asOf): array
    {
        // Find all active reports with schedules
        $reports = ReportDefinition::where('is_active', true)
            ->whereNotNull('schedule_type')
            ->whereNotNull('schedule_config')
            ->get();

        $dueReports = [];

        foreach ($reports as $report) {
            if ($this->isScheduleDue($report, $asOf)) {
                $dueReports[] = $report;
            }
        }

        return $dueReports;
    }

    /**
     * {@inheritdoc}
     */
    public function archive(string $id): bool
    {
        return ReportDefinition::where('id', $id)
            ->update(['is_active' => false]) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $id, array $data): bool
    {
        $report = ReportDefinition::find($id);
        if (!$report) {
            return false;
        }

        $report->update($data);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function storeGeneratedReport(array $data): string
    {
        $report = ReportGenerated::create($data);
        return $report->id;
    }

    /**
     * {@inheritdoc}
     */
    public function findGeneratedReportById(string $id): ?array
    {
        $report = ReportGenerated::find($id);
        if (!$report) {
            return null;
        }

        return $report->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getGenerationHistory(string $reportDefinitionId, int $limit = 50): array
    {
        return ReportGenerated::where('report_definition_id', $reportDefinitionId)
            ->orderBy('generated_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($r) => $r->toArray())
            ->all();
    }

    /**
     * {@inheritdoc}
     */
    public function storeDistributionLog(array $data): string
    {
        $log = ReportDistributionLog::create($data);
        return $log->id;
    }

    /**
     * Update a distribution log entry.
     *
     * @param string $logId
     * @param array<string, mixed> $data
     * @return bool
     */
    public function updateDistributionLog(string $logId, array $data): bool
    {
        return ReportDistributionLog::where('id', $logId)->update($data) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getDistributionLogs(string $reportGeneratedId): array
    {
        return ReportDistributionLog::where('report_generated_id', $reportGeneratedId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($l) => $l->toArray())
            ->all();
    }

    /**
     * {@inheritdoc}
     */
    public function findReportsForRetentionTransition(string $tier, \DateTimeImmutable $olderThan): array
    {
        return ReportGenerated::where('retention_tier', $tier)
            ->where('generated_at', '<', $olderThan)
            ->where('is_successful', true) // Only transition successful reports
            ->get()
            ->map(fn($r) => $r->toArray())
            ->all();
    }

    /**
     * {@inheritdoc}
     */
    public function updateRetentionTier(string $reportGeneratedId, string $newTier): bool
    {
        return ReportGenerated::where('id', $reportGeneratedId)
            ->update(['retention_tier' => $newTier]) > 0;
    }

    /**
     * Check if a report's schedule is due for execution.
     */
    private function isScheduleDue(ReportDefinition $report, \DateTimeImmutable $asOf): bool
    {
        $schedule = $report->getSchedule();
        if (!$schedule) {
            return false;
        }

        // If schedule hasn't started yet
        if ($schedule->startsAt && $schedule->startsAt > $asOf) {
            return false;
        }

        // If schedule has ended
        if ($schedule->endsAt && $schedule->endsAt < $asOf) {
            return false;
        }

        // Check if max occurrences reached (would need execution count tracking)
        // For now, this is simplified

        // Get last generation time
        $lastGeneration = ReportGenerated::where('report_definition_id', $report->id)
            ->where('is_successful', true)
            ->orderBy('generated_at', 'desc')
            ->first();

        $lastGenerationTime = $lastGeneration
            ? new \DateTimeImmutable($lastGeneration->generated_at)
            : null;

        // Determine if due based on schedule type
        return match ($schedule->type) {
            ScheduleType::ONCE => !$lastGeneration, // Only run once
            ScheduleType::DAILY => $this->isDueDaily($lastGenerationTime, $asOf),
            ScheduleType::WEEKLY => $this->isDueWeekly($lastGenerationTime, $asOf),
            ScheduleType::MONTHLY => $this->isDueMonthly($lastGenerationTime, $asOf),
            ScheduleType::YEARLY => $this->isDueYearly($lastGenerationTime, $asOf),
            ScheduleType::CRON => $this->isDueCron($schedule->cronExpression, $lastGenerationTime, $asOf),
        };
    }

    private function isDueDaily(?\DateTimeImmutable $lastRun, \DateTimeImmutable $now): bool
    {
        if (!$lastRun) {
            return true;
        }

        return $now->format('Y-m-d') > $lastRun->format('Y-m-d');
    }

    private function isDueWeekly(?\DateTimeImmutable $lastRun, \DateTimeImmutable $now): bool
    {
        if (!$lastRun) {
            return true;
        }

        $daysSince = $now->diff($lastRun)->days;
        return $daysSince >= 7;
    }

    private function isDueMonthly(?\DateTimeImmutable $lastRun, \DateTimeImmutable $now): bool
    {
        if (!$lastRun) {
            return true;
        }

        return $now->format('Y-m') > $lastRun->format('Y-m');
    }

    private function isDueYearly(?\DateTimeImmutable $lastRun, \DateTimeImmutable $now): bool
    {
        if (!$lastRun) {
            return true;
        }

        return $now->format('Y') > $lastRun->format('Y');
    }

    private function isDueCron(?string $cronExpression, ?\DateTimeImmutable $lastRun, \DateTimeImmutable $now): bool
    {
        if (!$cronExpression) {
            return false;
        }

        // Full cron evaluation requires dragonmantank/cron-expression library
        // This is not implemented in v1 to avoid adding dependencies
        // Reports with cron schedules should use the Scheduler package's cron support instead
        throw new \BadMethodCallException(
            'Cron expression evaluation is not implemented in DbReportRepository. ' .
            'Use ScheduleType::DAILY, WEEKLY, or MONTHLY instead, or integrate with Scheduler package for cron support.'
        );
    }
}

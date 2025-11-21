<?php

declare(strict_types=1);

namespace App\Listeners\FieldService;

use Nexus\FieldService\Events\SlaBreachedEvent;
use Nexus\Workflow\Contracts\WorkflowManagerInterface;
use Nexus\Notifier\Contracts\NotificationManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * SLA Breach Escalation Listener
 *
 * When an SLA is breached, trigger escalation workflow and notify stakeholders
 *
 * This implements FUN-FIE-0067: SLA breach escalation automation
 */
final readonly class EscalateOnSlaBreach
{
    public function __construct(
        private WorkflowManagerInterface $workflowManager,
        private NotificationManagerInterface $notificationManager,
        private LoggerInterface $logger
    ) {}

    public function handle(SlaBreachedEvent $event): void
    {
        try {
            $workOrderId = $event->getWorkOrderId();
            $breachDuration = $event->getBreachDuration();

            // Trigger escalation workflow
            $this->workflowManager->startProcess('work_order_sla_breach_escalation', [
                'work_order_id' => $workOrderId,
                'work_order_number' => $event->getWorkOrderNumber(),
                'sla_deadline' => $event->getSlaDeadline()->format('Y-m-d H:i:s'),
                'actual_completion' => $event->getActualCompletion()->format('Y-m-d H:i:s'),
                'breach_duration_minutes' => $breachDuration->i + ($breachDuration->h * 60),
            ]);

            // Send notifications to management
            $this->notificationManager->send([
                'channel' => 'email',
                'template' => 'field_service.sla_breach',
                'recipients' => $this->getEscalationRecipients(),
                'data' => [
                    'work_order_number' => $event->getWorkOrderNumber(),
                    'sla_deadline' => $event->getSlaDeadline()->format('Y-m-d H:i:s'),
                    'actual_completion' => $event->getActualCompletion()->format('Y-m-d H:i:s'),
                    'breach_duration' => $this->formatDuration($breachDuration),
                ],
            ]);

            $this->logger->warning('SLA breach escalation triggered', [
                'work_order_id' => $workOrderId,
                'work_order_number' => $event->getWorkOrderNumber(),
                'breach_duration_minutes' => $breachDuration->i + ($breachDuration->h * 60),
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to escalate SLA breach', [
                'work_order_id' => $event->getWorkOrderId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get escalation recipients from configuration
     * TODO: Make this configurable via Nexus\Setting
     */
    private function getEscalationRecipients(): array
    {
        return [
            'operations-manager@company.com',
            'service-director@company.com',
        ];
    }

    private function formatDuration(\DateInterval $interval): string
    {
        $parts = [];

        if ($interval->h > 0) {
            $parts[] = "{$interval->h} hours";
        }

        if ($interval->i > 0) {
            $parts[] = "{$interval->i} minutes";
        }

        return implode(' ', $parts) ?: '0 minutes';
    }
}

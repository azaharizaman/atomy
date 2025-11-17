<?php

declare(strict_types=1);

namespace Nexus\Workflow\Contracts;

/**
 * Contract for workflow timers.
 *
 * Timers enable scheduled actions (escalations, SLA checks, reminders).
 */
interface TimerInterface
{
    /**
     * Get the timer ID.
     */
    public function getId(): string;

    /**
     * Get the associated workflow instance ID.
     */
    public function getWorkflowId(): string;

    /**
     * Get the timer type (escalation, sla_check, reminder, scheduled_task).
     */
    public function getType(): string;

    /**
     * Get the trigger timestamp.
     */
    public function getTriggerAt(): \DateTimeInterface;

    /**
     * Get the action to execute when timer fires.
     *
     * @return array<string, mixed>
     */
    public function getAction(): array;

    /**
     * Check if timer has fired.
     */
    public function isFired(): bool;

    /**
     * Get the timestamp when timer was fired (null if not fired).
     */
    public function getFiredAt(): ?\DateTimeInterface;
}

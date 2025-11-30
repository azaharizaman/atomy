<?php

declare(strict_types=1);

namespace Nexus\Receivable\Contracts;

use DateTimeInterface;

/**
 * Dunning Manager Interface
 *
 * Manages collections/dunning process for overdue invoices.
 * Integrates with Workflow and Notifier packages.
 */
interface DunningManagerInterface
{
    /**
     * Detect overdue invoices and trigger dunning workflow
     *
     * @param string $tenantId
     * @param DateTimeInterface $asOfDate
     * @return int Number of dunning notices sent
     */
    public function processOverdueInvoices(string $tenantId, DateTimeInterface $asOfDate): int;

    /**
     * Send dunning notice to a specific customer
     *
     * @param string $customerId
     * @param string $escalationLevel (e.g., 'first_reminder', 'second_reminder', 'final_notice', 'collections')
     * @return void
     * @throws \Nexus\Receivable\Exceptions\DunningFailedException
     */
    public function sendDunningNotice(string $customerId, string $escalationLevel): void;

    /**
     * Get dunning escalation level for an invoice based on days overdue
     *
     * @param int $daysOverdue
     * @return string|null Null if not yet eligible for dunning
     */
    public function getEscalationLevel(int $daysOverdue): ?string;

    /**
     * Get overdue invoices requiring dunning action
     *
     * @param string $tenantId
     * @param DateTimeInterface $asOfDate
     * @return array<string, mixed>[] Array of ['customer_id', 'invoices', 'escalation_level']
     */
    public function getOverdueCustomers(string $tenantId, DateTimeInterface $asOfDate): array;
}

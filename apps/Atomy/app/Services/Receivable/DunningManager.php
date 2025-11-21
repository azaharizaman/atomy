<?php

declare(strict_types=1);

namespace App\Services\Receivable;

use Nexus\Notifier\Contracts\NotifierInterface;
use Nexus\Receivable\Contracts\CustomerInvoiceRepositoryInterface;
use Nexus\Receivable\Contracts\DunningManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Dunning Manager Service
 *
 * Manages collections and dunning notice generation.
 */
final readonly class DunningManager implements DunningManagerInterface
{
    public function __construct(
        private CustomerInvoiceRepositoryInterface $invoiceRepository,
        private NotifierInterface $notifier,
        private LoggerInterface $logger
    ) {}

    public function sendDunningNotices(string $tenantId, int $minDaysPastDue = 0): int
    {
        $overdueInvoices = $this->invoiceRepository->getOverdueInvoices(
            $tenantId,
            new \DateTimeImmutable(),
            $minDaysPastDue
        );

        $sentCount = 0;

        foreach ($overdueInvoices as $invoice) {
            try {
                $level = $this->getDunningLevel($invoice->getDaysPastDue());
                
                $this->notifier->send(
                    channel: 'email',
                    recipient: $invoice->getCustomer()->getEmail() ?? '',
                    template: "dunning_notice_level_{$level}",
                    data: [
                        'customer_name' => $invoice->getCustomer()->getName(),
                        'invoice_number' => $invoice->getInvoiceNumber(),
                        'invoice_date' => $invoice->getInvoiceDate()->format('Y-m-d'),
                        'due_date' => $invoice->getDueDate()->format('Y-m-d'),
                        'total_amount' => $invoice->getTotalAmount(),
                        'outstanding_balance' => $invoice->getOutstandingBalance(),
                        'currency' => $invoice->getCurrency(),
                        'days_past_due' => $invoice->getDaysPastDue(),
                        'dunning_level' => $level,
                    ]
                );

                $sentCount++;

                $this->logger->info('Dunning notice sent', [
                    'invoice_id' => $invoice->getId(),
                    'invoice_number' => $invoice->getInvoiceNumber(),
                    'customer_id' => $invoice->getCustomerId(),
                    'days_past_due' => $invoice->getDaysPastDue(),
                    'dunning_level' => $level,
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Failed to send dunning notice', [
                    'invoice_id' => $invoice->getId(),
                    'invoice_number' => $invoice->getInvoiceNumber(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->logger->info('Dunning notices batch completed', [
            'tenant_id' => $tenantId,
            'min_days_past_due' => $minDaysPastDue,
            'total_overdue_invoices' => count($overdueInvoices),
            'notices_sent' => $sentCount,
        ]);

        return $sentCount;
    }

    public function getDunningLevel(int $daysPastDue): int
    {
        return match (true) {
            $daysPastDue < 30 => 1,  // Friendly reminder
            $daysPastDue < 60 => 2,  // Urgent reminder
            $daysPastDue < 90 => 3,  // Final notice
            default => 4,            // Collection/legal action
        };
    }

    /**
     * @return array<int, int>
     */
    public function getInvoiceCountByDunningLevel(string $tenantId): array
    {
        $overdueInvoices = $this->invoiceRepository->getOverdueInvoices(
            $tenantId,
            new \DateTimeImmutable()
        );

        $levelCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0];

        foreach ($overdueInvoices as $invoice) {
            $level = $this->getDunningLevel($invoice->getDaysPastDue());
            $levelCounts[$level]++;
        }

        return $levelCounts;
    }
}

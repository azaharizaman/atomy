<?php

declare(strict_types=1);

namespace App\Listeners\FieldService;

use Nexus\FieldService\Events\WorkOrderCompletedEvent;
use Nexus\Accounting\Contracts\JournalEntryManagerInterface;
use Nexus\Accounting\Contracts\ChartOfAccountsInterface;
use Psr\Log\LoggerInterface;

/**
 * GL Posting Listener for Work Order Completion
 *
 * When a work order is completed, post revenue recognition journal entry:
 * DR: Accounts Receivable (asset)
 * CR: Service Revenue (income)
 */
final readonly class PostRevenueOnWorkOrderCompletion
{
    public function __construct(
        private JournalEntryManagerInterface $journalEntryManager,
        private ChartOfAccountsInterface $chartOfAccounts,
        private LoggerInterface $logger
    ) {}

    public function handle(WorkOrderCompletedEvent $event): void
    {
        try {
            // Get labor cost from work order
            $laborHours = $event->getLaborHours();
            
            if ($laborHours === null || $laborHours->getTotalCost() === 0.0) {
                $this->logger->info('Work order completed with no labor cost, skipping GL posting', [
                    'work_order_id' => $event->getWorkOrderId(),
                ]);
                return;
            }

            $amount = $laborHours->getTotalCost();
            $currency = $laborHours->getCurrency();

            // Find AR and Revenue accounts (should be configurable)
            $arAccount = $this->chartOfAccounts->findByCode('1200'); // Accounts Receivable
            $revenueAccount = $this->chartOfAccounts->findByCode('4100'); // Service Revenue

            if ($arAccount === null || $revenueAccount === null) {
                $this->logger->error('GL accounts not found for revenue posting', [
                    'work_order_id' => $event->getWorkOrderId(),
                ]);
                return;
            }

            // Create journal entry
            $this->journalEntryManager->create([
                'description' => "Service revenue - Work Order {$event->getWorkOrderNumber()}",
                'lines' => [
                    [
                        'account_id' => $arAccount->getId(),
                        'debit' => $amount,
                        'credit' => 0,
                        'currency' => $currency,
                    ],
                    [
                        'account_id' => $revenueAccount->getId(),
                        'debit' => 0,
                        'credit' => $amount,
                        'currency' => $currency,
                    ],
                ],
                'metadata' => [
                    'source' => 'field_service',
                    'work_order_id' => $event->getWorkOrderId(),
                    'completed_at' => $event->getOccurredAt()->format('Y-m-d H:i:s'),
                ],
            ]);

            $this->logger->info('Revenue posted for work order completion', [
                'work_order_id' => $event->getWorkOrderId(),
                'amount' => $amount,
                'currency' => $currency,
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to post revenue for work order', [
                'work_order_id' => $event->getWorkOrderId(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}

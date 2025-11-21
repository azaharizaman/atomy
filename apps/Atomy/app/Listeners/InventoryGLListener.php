<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Events\Dispatcher;
use Nexus\Accounting\Services\GeneralLedgerManager;
use Nexus\Inventory\Events\StockReceivedEvent;
use Nexus\Inventory\Events\StockIssuedEvent;
use Nexus\Inventory\Events\StockAdjustedEvent;
use Nexus\Setting\Services\SettingsManager;
use Psr\Log\LoggerInterface;

final class InventoryGLListener
{
    public function __construct(
        private readonly GeneralLedgerManager $glManager,
        private readonly SettingsManager $settings,
        private readonly LoggerInterface $logger
    ) {}

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            StockReceivedEvent::class,
            [self::class, 'handleStockReceived']
        );

        $events->listen(
            StockIssuedEvent::class,
            [self::class, 'handleStockIssued']
        );

        $events->listen(
            StockAdjustedEvent::class,
            [self::class, 'handleStockAdjusted']
        );
    }

    public function handleStockReceived(StockReceivedEvent $event): void
    {
        if (!$this->isGLIntegrationEnabled()) {
            return;
        }

        try {
            // DR Inventory Asset / CR GR-IR Clearing
            $inventoryAccount = $this->settings->getString('inventory.gl.asset_account', '1200');
            $grirAccount = $this->settings->getString('inventory.gl.grir_clearing_account', '2000');

            $this->glManager->postJournalEntry([
                'date' => $event->receivedDate->format('Y-m-d'),
                'description' => "Stock Receipt - Product {$event->productId} - Qty: {$event->quantity}",
                'reference_type' => $event->grnId ? 'grn' : null,
                'reference_id' => $event->grnId,
                'lines' => [
                    [
                        'account_code' => $inventoryAccount,
                        'debit' => $event->totalValue,
                        'credit' => 0,
                        'description' => "Inventory received to warehouse {$event->warehouseId}",
                    ],
                    [
                        'account_code' => $grirAccount,
                        'debit' => 0,
                        'credit' => $event->totalValue,
                        'description' => "GR-IR clearing",
                    ],
                ],
            ]);

            $this->logger->info('GL entry posted for stock receipt', [
                'product_id' => $event->productId,
                'quantity' => $event->quantity,
                'value' => $event->totalValue,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to post GL entry for stock receipt', [
                'error' => $e->getMessage(),
                'event' => $event,
            ]);
        }
    }

    public function handleStockIssued(StockIssuedEvent $event): void
    {
        if (!$this->isGLIntegrationEnabled()) {
            return;
        }

        try {
            // DR COGS / CR Inventory Asset
            $cogsAccount = $this->settings->getString('inventory.gl.cogs_account', '5000');
            $inventoryAccount = $this->settings->getString('inventory.gl.asset_account', '1200');

            $this->glManager->postJournalEntry([
                'date' => $event->issuedDate->format('Y-m-d'),
                'description' => "Stock Issue - Product {$event->productId} - Reason: {$event->issueReason->value}",
                'reference_type' => $event->referenceType,
                'reference_id' => $event->referenceId,
                'lines' => [
                    [
                        'account_code' => $cogsAccount,
                        'debit' => $event->costOfGoodsSold,
                        'credit' => 0,
                        'description' => "COGS for {$event->quantity} units",
                    ],
                    [
                        'account_code' => $inventoryAccount,
                        'debit' => 0,
                        'credit' => $event->costOfGoodsSold,
                        'description' => "Inventory issued from warehouse {$event->warehouseId}",
                    ],
                ],
            ]);

            $this->logger->info('GL entry posted for stock issue', [
                'product_id' => $event->productId,
                'quantity' => $event->quantity,
                'cogs' => $event->costOfGoodsSold,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to post GL entry for stock issue', [
                'error' => $e->getMessage(),
                'event' => $event,
            ]);
        }
    }

    public function handleStockAdjusted(StockAdjustedEvent $event): void
    {
        if (!$this->isGLIntegrationEnabled()) {
            return;
        }

        try {
            $inventoryAccount = $this->settings->getString('inventory.gl.asset_account', '1200');
            $adjustmentAccount = $this->settings->getString('inventory.gl.adjustment_account', '5100');

            // Positive adjustment: DR Inventory / CR Adjustment Income
            // Negative adjustment: DR Adjustment Expense / CR Inventory
            if ($event->quantityChange > 0) {
                $lines = [
                    [
                        'account_code' => $inventoryAccount,
                        'debit' => abs($event->valueChange),
                        'credit' => 0,
                        'description' => "Inventory increase adjustment",
                    ],
                    [
                        'account_code' => $adjustmentAccount,
                        'debit' => 0,
                        'credit' => abs($event->valueChange),
                        'description' => "Adjustment income",
                    ],
                ];
            } else {
                $lines = [
                    [
                        'account_code' => $adjustmentAccount,
                        'debit' => abs($event->valueChange),
                        'credit' => 0,
                        'description' => "Adjustment expense",
                    ],
                    [
                        'account_code' => $inventoryAccount,
                        'debit' => 0,
                        'credit' => abs($event->valueChange),
                        'description' => "Inventory decrease adjustment",
                    ],
                ];
            }

            $this->glManager->postJournalEntry([
                'date' => $event->adjustedDate->format('Y-m-d'),
                'description' => "Stock Adjustment - Product {$event->productId} - Reason: {$event->reason}",
                'lines' => $lines,
            ]);

            $this->logger->info('GL entry posted for stock adjustment', [
                'product_id' => $event->productId,
                'quantity_change' => $event->quantityChange,
                'value_change' => $event->valueChange,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to post GL entry for stock adjustment', [
                'error' => $e->getMessage(),
                'event' => $event,
            ]);
        }
    }

    private function isGLIntegrationEnabled(): bool
    {
        return $this->settings->getBool('inventory.gl_integration_enabled', true);
    }
}

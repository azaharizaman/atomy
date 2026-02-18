<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Listeners;

use Nexus\Sales\Events\RmaCreatedEvent;
use Nexus\SupplyChainOperations\Workflows\Rma\RmaWorkflow;
use Nexus\SupplyChainOperations\Workflows\Rma\RmaRequest;
use Psr\Log\LoggerInterface;

final readonly class RmaCreatedListener
{
    public function __construct(
        private RmaWorkflow $rmaWorkflow,
        private LoggerInterface $logger
    ) {
    }

    public function onRmaCreated(RmaCreatedEvent $event): void
    {
        $this->logger->info("Processing RMA creation for Sales Order {$event->salesOrderId}");

        $rmaRequest = new RmaRequest(
            tenantId: $event->tenantId,
            salesOrderId: $event->salesOrderId,
            customerId: $event->customerId,
            items: $event->items,
            reason: $event->reason,
            requestedBy: $event->requestedBy ?? null
        );

        $rmaResult = $this->rmaWorkflow->initiateReturn($rmaRequest);

        $this->logger->info(
            "RMA {$rmaResult->rmaId} initiated with status {$rmaResult->status->value}"
        );
    }
}

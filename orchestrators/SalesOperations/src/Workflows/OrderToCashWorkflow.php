<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Workflows;

use Nexus\SalesOperations\Contracts\SagaInterface;
use Nexus\SalesOperations\Contracts\SagaStepInterface;
use Nexus\SalesOperations\Contracts\WorkflowStorageInterface;
use Nexus\SalesOperations\DTOs\SagaContext;
use Nexus\SalesOperations\DTOs\SagaResult;
use Nexus\SalesOperations\Enums\SagaStatus;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class OrderToCashWorkflow extends AbstractSaga implements SagaInterface
{
    private const string ID = 'order_to_cash_workflow';

    public function __construct(
        private readonly array $steps,
        WorkflowStorageInterface $storage,
        EventDispatcherInterface $eventDispatcher,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($storage, $eventDispatcher, $logger ?? new NullLogger());
    }

    public static function create(
        SagaStepInterface $validateCreditStep,
        SagaStepInterface $reserveStockStep,
        SagaStepInterface $confirmOrderStep,
        SagaStepInterface $createShipmentStep,
        SagaStepInterface $createInvoiceStep,
        SagaStepInterface $trackPaymentStep,
        WorkflowStorageInterface $storage,
        EventDispatcherInterface $eventDispatcher,
        ?LoggerInterface $logger = null,
    ): self {
        return new self(
            steps: [
                $validateCreditStep,
                $reserveStockStep,
                $confirmOrderStep,
                $createShipmentStep,
                $createInvoiceStep,
                $trackPaymentStep,
            ],
            storage: $storage,
            eventDispatcher: $eventDispatcher,
            logger: $logger,
        );
    }

    public function getId(): string
    {
        return self::ID;
    }

    public function getName(): string
    {
        return 'Order-to-Cash Workflow';
    }

    public function getSteps(): array
    {
        return $this->steps;
    }

    public function canExecute(SagaContext $context): bool
    {
        if (!$context->has('order_id') && !$context->has('order_data')) {
            return false;
        }

        if ($context->has('order_data')) {
            $orderData = $context->get('order_data', []);
            if (empty($orderData['customer_id'] ?? null)) {
                return false;
            }
        }

        return true;
    }

    public function execute(SagaContext $context): SagaResult
    {
        $instanceId = $this->generateInstanceId();

        $this->logger->info('Starting Order-to-Cash workflow', [
            'instance_id' => $instanceId,
            'tenant_id' => $context->tenantId,
            'user_id' => $context->userId,
        ]);

        $result = parent::execute($context->withInstanceId($instanceId));

        if ($result->status === SagaStatus::COMPLETED) {
            $this->logger->info('Order-to-Cash workflow completed', [
                'instance_id' => $instanceId,
                'order_id' => $context->get('order_id'),
            ]);
        }

        return $result;
    }

    public static function getVariants(): array
    {
        return [
            'full' => 'Complete O2C cycle from order to payment',
            'quick_sale' => 'Skip credit check for COD/prepaid orders',
            'consignment' => 'Order on consignment with deferred invoicing',
        ];
    }
}

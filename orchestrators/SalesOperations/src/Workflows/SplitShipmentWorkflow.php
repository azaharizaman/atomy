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

final readonly class SplitShipmentWorkflow extends AbstractSaga implements SagaInterface
{
    private const string ID = 'split_shipment_workflow';

    public function __construct(
        private readonly array $steps,
        WorkflowStorageInterface $storage,
        EventDispatcherInterface $eventDispatcher,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($storage, $eventDispatcher, $logger ?? new NullLogger());
    }

    public static function create(
        SagaStepInterface $checkAvailabilityStep,
        SagaStepInterface $allocateWarehouseStep,
        SagaStepInterface $createPartialShipmentStep,
        SagaStepInterface $updateBackorderStep,
        WorkflowStorageInterface $storage,
        EventDispatcherInterface $eventDispatcher,
        ?LoggerInterface $logger = null,
    ): self {
        return new self(
            steps: [
                $checkAvailabilityStep,
                $allocateWarehouseStep,
                $createPartialShipmentStep,
                $updateBackorderStep,
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
        return 'Split Shipment Workflow';
    }

    public function getSteps(): array
    {
        return $this->steps;
    }

    public function canExecute(SagaContext $context): bool
    {
        if (!$context->has('order_id')) {
            return false;
        }

        if (!$context->has('shipment_lines') && !$context->has('warehouse_allocations')) {
            return false;
        }

        return true;
    }

    public function execute(SagaContext $context): SagaResult
    {
        $instanceId = $this->generateInstanceId();

        $this->logger->info('Starting Split Shipment workflow', [
            'instance_id' => $instanceId,
            'tenant_id' => $context->tenantId,
            'order_id' => $context->get('order_id'),
        ]);

        $result = parent::execute($context->withInstanceId($instanceId));

        if ($result->status === SagaStatus::COMPLETED) {
            $this->logger->info('Split Shipment workflow completed', [
                'instance_id' => $instanceId,
                'order_id' => $context->get('order_id'),
            ]);
        }

        return $result;
    }
}

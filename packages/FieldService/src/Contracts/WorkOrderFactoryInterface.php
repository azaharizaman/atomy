<?php

declare(strict_types=1);

namespace Nexus\FieldService\Contracts;

use Nexus\FieldService\ValueObjects\WorkOrderNumber;

/**
 * Work Order Factory Interface
 *
 * Creates WorkOrder entities. Implementation is in the application layer.
 */
interface WorkOrderFactoryInterface
{
    /**
     * Create a new work order entity.
     *
     * @param array{
     *     number: WorkOrderNumber,
     *     customer_party_id: string,
     *     service_location_id?: string|null,
     *     asset_id?: string|null,
     *     service_contract_id?: string|null,
     *     service_type: string,
     *     priority: string,
     *     description: string,
     *     scheduled_start?: \DateTimeImmutable|null,
     *     scheduled_end?: \DateTimeImmutable|null,
     *     sla_deadline?: \DateTimeImmutable|null,
     *     metadata?: array
     * } $data
     */
    public function create(array $data): WorkOrderInterface;
}

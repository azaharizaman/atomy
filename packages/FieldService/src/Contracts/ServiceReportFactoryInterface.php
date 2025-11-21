<?php

declare(strict_types=1);

namespace Nexus\FieldService\Contracts;

/**
 * Service Report Factory Interface
 *
 * Creates ServiceReport entities. Implementation is in the application layer.
 */
interface ServiceReportFactoryInterface
{
    /**
     * Create a new service report entity.
     *
     * @param array{
     *     work_order_id: string,
     *     document_path: string,
     *     generated_at: \DateTimeImmutable,
     *     metadata?: array
     * } $data
     */
    public function create(array $data): ServiceReportInterface;
}

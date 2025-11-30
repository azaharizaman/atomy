<?php

declare(strict_types=1);

namespace Nexus\Payroll\Contracts;

/**
 * Combined repository contract for employee component operations.
 *
 * @deprecated Use EmployeeComponentQueryInterface and EmployeeComponentPersistInterface separately for CQRS compliance.
 *             This interface is maintained for backward compatibility only.
 */
interface EmployeeComponentRepositoryInterface extends EmployeeComponentQueryInterface, EmployeeComponentPersistInterface
{
    // All methods are inherited from EmployeeComponentQueryInterface and EmployeeComponentPersistInterface
}

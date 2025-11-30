<?php

declare(strict_types=1);

namespace Nexus\Payroll\Contracts;

/**
 * Combined repository contract for payslip operations.
 *
 * @deprecated Use PayslipQueryInterface and PayslipPersistInterface separately for CQRS compliance.
 *             This interface is maintained for backward compatibility only.
 */
interface PayslipRepositoryInterface extends PayslipQueryInterface, PayslipPersistInterface
{
    // All methods are inherited from PayslipQueryInterface and PayslipPersistInterface
}

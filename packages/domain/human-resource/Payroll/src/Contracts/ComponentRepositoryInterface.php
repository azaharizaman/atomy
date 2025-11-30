<?php

declare(strict_types=1);

namespace Nexus\Payroll\Contracts;

/**
 * Combined repository contract for payroll component operations.
 *
 * @deprecated Use ComponentQueryInterface and ComponentPersistInterface separately for CQRS compliance.
 *             This interface is maintained for backward compatibility only.
 */
interface ComponentRepositoryInterface extends ComponentQueryInterface, ComponentPersistInterface
{
    // All methods are inherited from ComponentQueryInterface and ComponentPersistInterface
}

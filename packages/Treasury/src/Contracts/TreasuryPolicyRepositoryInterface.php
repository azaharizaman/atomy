<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

interface TreasuryPolicyRepositoryInterface extends
    TreasuryPolicyQueryInterface,
    TreasuryPolicyPersistInterface
{
}

<?php

declare(strict_types=1);

namespace Nexus\PDPA\Contracts;

use Nexus\PDPA\ValueObjects\DataSubjectRequest;

interface DataSubjectRequestManagerInterface
{
    /** @return array<int, DataSubjectRequest> */
    public function getActiveRequests(): array;
}

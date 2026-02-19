<?php

declare(strict_types=1);

namespace Nexus\CRM\Exceptions;

use Nexus\CRM\Enums\LeadStatus;

final class InvalidLeadStatusTransitionException extends CRMException
{
    public function __construct(
        public readonly LeadStatus $fromStatus,
        public readonly LeadStatus $toStatus
    ) {
        parent::__construct(
            sprintf(
                'Invalid lead status transition from %s to %s',
                $fromStatus->value,
                $toStatus->value
            )
        );
    }

    public static function fromStatuses(LeadStatus $from, LeadStatus $to): self
    {
        return new self($from, $to);
    }
}

<?php

declare(strict_types=1);

namespace Nexus\CRM\Exceptions;

use Nexus\CRM\Enums\LeadStatus;

final class LeadNotConvertibleException extends CRMException
{
    public function __construct(
        public readonly string $leadId,
        public readonly LeadStatus $currentStatus
    ) {
        parent::__construct(
            sprintf(
                'Lead %s cannot be converted. Current status: %s',
                $leadId,
                $currentStatus->value
            )
        );
    }

    public static function forLead(string $leadId, LeadStatus $status): self
    {
        return new self($leadId, $status);
    }
}

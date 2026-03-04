<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

interface ApprovalRejectedEventInterface
{
    public function getWorkflowType(): string;

    public function getEntityId(): string;

    public function getRejectedBy(): string;

    public function getReason(): ?string;
}

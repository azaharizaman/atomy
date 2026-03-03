<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

interface ApprovalCompletedEventInterface
{
    public function getWorkflowType(): string;

    public function getEntityId(): string;

    public function getApprovedBy(): string;

    public function getNotes(): ?string;
}

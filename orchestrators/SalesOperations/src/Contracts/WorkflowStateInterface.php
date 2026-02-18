<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Contracts;

interface WorkflowStateInterface
{
    public function getInstanceId(): string;

    public function getWorkflowId(): string;

    public function getTenantId(): string;

    public function getStatus(): string;

    public function getCurrentStep(): ?string;

    public function getContextData(): array;

    public function getCreatedAt(): \DateTimeImmutable;

    public function getUpdatedAt(): \DateTimeImmutable;
}

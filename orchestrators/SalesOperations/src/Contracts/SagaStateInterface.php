<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Contracts;

interface SagaStateInterface
{
    public function getInstanceId(): string;

    public function getSagaId(): string;

    public function getTenantId(): string;

    public function getStatus(): string;

    public function getCompletedSteps(): array;

    public function getCompensatedSteps(): array;

    public function getContextData(): array;

    public function getStepData(): array;

    public function getCreatedAt(): \DateTimeImmutable;

    public function getUpdatedAt(): \DateTimeImmutable;
}

<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Contracts;

use Nexus\SalesOperations\DTOs\SagaContext;
use Nexus\SalesOperations\DTOs\SagaResult;

interface SagaInterface
{
    public function getId(): string;

    public function getName(): string;

    public function execute(SagaContext $context): SagaResult;

    public function compensate(string $sagaInstanceId, ?string $reason = null): SagaResult;

    public function getState(string $sagaInstanceId): ?SagaStateInterface;

    public function getSteps(): array;

    public function canExecute(SagaContext $context): bool;
}

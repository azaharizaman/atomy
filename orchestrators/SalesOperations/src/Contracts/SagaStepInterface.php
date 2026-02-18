<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Contracts;

use Nexus\SalesOperations\DTOs\SagaStepContext;
use Nexus\SalesOperations\DTOs\SagaStepResult;

interface SagaStepInterface
{
    public function getId(): string;

    public function getName(): string;

    public function execute(SagaStepContext $context): SagaStepResult;

    public function compensate(SagaStepContext $context): SagaStepResult;

    public function hasCompensation(): bool;

    public function getOrder(): int;

    public function isRequired(): bool;

    public function getTimeout(): int;

    public function getRetryAttempts(): int;
}

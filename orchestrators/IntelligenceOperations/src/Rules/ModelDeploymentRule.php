<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\Rules;

use Nexus\IntelligenceOperations\DTOs\ModelDeploymentRequest;

final class ModelDeploymentRule
{
    public function assert(ModelDeploymentRequest $request): void
    {
        if ($request->modelId === '') {
            throw new \InvalidArgumentException('modelId is required.');
        }

        if ($request->version === '') {
            throw new \InvalidArgumentException('version is required.');
        }

        if (!preg_match('/^[A-Za-z0-9._-]+$/', $request->version)) {
            throw new \InvalidArgumentException('version format is invalid.');
        }
    }
}

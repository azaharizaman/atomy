<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\Workflows;

use Nexus\IntelligenceOperations\Contracts\ModelRegistryPortInterface;
use Nexus\IntelligenceOperations\Contracts\ModelTelemetryPortInterface;
use Nexus\IntelligenceOperations\DTOs\ModelDeploymentRequest;
use Nexus\IntelligenceOperations\Rules\ModelDeploymentRule;

final readonly class ModelDeploymentWorkflow
{
    public function __construct(
        private ModelDeploymentRule $rule,
        private ModelRegistryPortInterface $registryPort,
        private ModelTelemetryPortInterface $telemetryPort,
    ) {}

    public function run(ModelDeploymentRequest $request): bool
    {
        $this->rule->assert($request);

        $started = microtime(true);
        $success = $this->registryPort->registerVersion($request->modelId, $request->version, $request->config);

        $tags = ['model_id' => $request->modelId, 'version' => $request->version];
        $this->telemetryPort->increment('intelligence.model.deploy.total', 1.0, $tags);
        $this->telemetryPort->increment('intelligence.model.deploy.' . ($success ? 'success' : 'failure'), 1.0, $tags);
        $this->telemetryPort->timing('intelligence.model.deploy.duration_ms', (microtime(true) - $started) * 1000, $tags);

        return $success;
    }
}

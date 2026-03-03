<?php

declare(strict_types=1);

namespace Nexus\ConnectivityOperations\Workflows;

use Nexus\ConnectivityOperations\Contracts\ConnectivityTelemetryPortInterface;
use Nexus\ConnectivityOperations\Contracts\SecretRotationPortInterface;

final readonly class SecretRotationWorkflow
{
    public function __construct(
        private SecretRotationPortInterface $secretRotationPort,
        private ConnectivityTelemetryPortInterface $telemetryPort,
    ) {}

    public function run(string $providerId): bool
    {
        if ($providerId === '') {
            throw new \InvalidArgumentException('providerId is required for secret rotation.');
        }

        $success = $this->secretRotationPort->rotate($providerId);
        $this->telemetryPort->increment(
            'connectivity.secret_rotation.' . ($success ? 'success' : 'failure'),
            1.0,
            ['provider' => $providerId]
        );

        return $success;
    }
}

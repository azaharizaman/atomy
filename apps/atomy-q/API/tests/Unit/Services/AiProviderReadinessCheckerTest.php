<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Ai\AiProviderCheckFinding;
use App\Services\Ai\AiProviderCheckSeverity;
use App\Services\Ai\AiProviderEndpointCheck;
use App\Services\Ai\AiProviderReadinessResult;
use Tests\TestCase;

final class AiProviderReadinessCheckerTest extends TestCase
{
    public function testReadinessResultRollsUpFailedSeverityAndSerializesPayload(): void
    {
        $result = new AiProviderReadinessResult(
            checkedAt: '2026-05-02T00:00:00+00:00',
            mode: 'provider',
            provider: 'openrouter',
            deep: false,
            endpointGroups: [
                new AiProviderEndpointCheck(
                    endpointGroup: 'document',
                    configured: true,
                    enabled: true,
                    endpointUri: 'https://openrouter.ai/api/v1/chat/completions',
                    probeHealth: 'unavailable',
                    latencyMs: 120,
                    severity: AiProviderCheckSeverity::FAILED,
                    reasonCodes: ['health_probe_failed'],
                    diagnostics: ['provider_name' => 'openrouter'],
                ),
            ],
            operatorFindings: [
                new AiProviderCheckFinding(
                    severity: AiProviderCheckSeverity::WARNING,
                    area: 'security',
                    message: 'Endpoint [document] uses plain HTTP outside local development.',
                    endpointGroup: 'document',
                    reasonCode: 'plain_http_endpoint',
                ),
            ],
            publishedAlerts: [],
        );

        self::assertSame(AiProviderCheckSeverity::FAILED, $result->exitSeverity());

        $payload = $result->toArray();
        self::assertSame('provider', $payload['mode']);
        self::assertSame('openrouter', $payload['provider']);
        self::assertFalse($payload['deep']);
        self::assertSame(AiProviderCheckSeverity::FAILED, $payload['global_status']);
        self::assertSame(AiProviderCheckSeverity::FAILED, $payload['exit_severity']);
        self::assertSame('document', $payload['endpoint_groups'][0]['endpoint_group']);
        self::assertSame('security', $payload['operator_findings'][0]['area']);
    }
}

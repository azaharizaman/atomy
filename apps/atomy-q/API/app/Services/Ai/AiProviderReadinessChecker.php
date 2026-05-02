<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Adapters\Ai\Contracts\AiEndpointRegistryInterface;
use App\Adapters\Ai\Contracts\AiRuntimeStatusInterface;
use App\Services\Ai\Contracts\AiOperationalAlertPublisherInterface;
use App\Services\Ai\Contracts\AiProviderReadinessCheckerInterface;
use App\Services\Ai\Contracts\ProviderContractVerifierInterface;
use DateTimeImmutable;
use DateTimeZone;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Nexus\MachineLearning\Contracts\AiHealthProbeInterface;
use Nexus\MachineLearning\Enums\AiHealth;
use Nexus\MachineLearning\ValueObjects\AiEndpointConfig;
use Throwable;

final readonly class AiProviderReadinessChecker implements AiProviderReadinessCheckerInterface
{
    public function __construct(
        private AiEndpointRegistryInterface $endpointRegistry,
        private AiHealthProbeInterface $healthProbe,
        private AiRuntimeStatusInterface $runtimeStatus,
        private ProviderContractVerifierInterface $contractVerifier,
        private ?AiOperationalAlertPublisherInterface $alertPublisher = null,
    ) {}

    /**
     * @param  list<string>  $endpointGroups
     */
    public function check(
        array $endpointGroups,
        bool $deep,
        bool $publishAlerts,
        string $tenantId,
        string $rfqId,
    ): AiProviderReadinessResult {
        $mode = $this->endpointRegistry->mode();
        $checkedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $selectedGroups = $endpointGroups === []
            ? $this->endpointRegistry->endpointGroups()
            : array_values($endpointGroups);
        $endpointChecks = [];
        $findings = [];

        foreach ($selectedGroups as $endpointGroup) {
            $config = $this->endpointRegistry->endpointConfig($endpointGroup);

            $endpointChecks[] = $this->checkEndpoint($endpointGroup, $mode, $config);
            array_push($findings, ...$this->configurationFindings($endpointGroup, $mode, $config));
        }

        if ($deep) {
            foreach ($this->contractVerifier->verify($selectedGroups, $tenantId, $rfqId) as $deepResult) {
                $findings[] = new AiProviderCheckFinding(
                    severity: $deepResult->severity,
                    area: 'deep_contract',
                    message: $deepResult->message,
                    endpointGroup: $deepResult->endpointGroup,
                    reasonCode: $deepResult->reasonCodes[0] ?? null,
                );
            }
        }

        $publishedAlerts = [];
        if ($publishAlerts && $this->alertPublisher instanceof AiOperationalAlertPublisherInterface) {
            $publishedAlerts = $this->alertPublisher->publishSnapshot($this->runtimeStatus->snapshot());
        }

        return new AiProviderReadinessResult(
            checkedAt: $checkedAt->format(DATE_ATOM),
            mode: $mode,
            provider: $this->endpointRegistry->providerName(),
            deep: $deep,
            endpointGroups: $endpointChecks,
            operatorFindings: $findings,
            publishedAlerts: $publishedAlerts,
        );
    }

    private function checkEndpoint(string $endpointGroup, string $mode, ?AiEndpointConfig $config): AiProviderEndpointCheck
    {
        if ($mode === AiStatusSchema::MODE_OFF) {
            return $this->skippedEndpoint($endpointGroup, ['ai_disabled_by_config']);
        }

        if ($mode === AiStatusSchema::MODE_DETERMINISTIC) {
            return $this->skippedEndpoint($endpointGroup, ['deterministic_fallback_mode']);
        }

        if ($config === null) {
            return new AiProviderEndpointCheck(
                endpointGroup: $endpointGroup,
                configured: false,
                enabled: false,
                endpointUri: null,
                probeHealth: null,
                latencyMs: null,
                severity: AiProviderCheckSeverity::FAILED,
                reasonCodes: ['endpoint_not_configured'],
                diagnostics: [],
            );
        }

        if (! $config->enabled) {
            return new AiProviderEndpointCheck(
                endpointGroup: $endpointGroup,
                configured: true,
                enabled: false,
                endpointUri: $this->sanitizedEndpointUri($config->endpointUri),
                probeHealth: AiHealth::DISABLED->value,
                latencyMs: null,
                severity: AiProviderCheckSeverity::SKIPPED,
                reasonCodes: ['endpoint_disabled_by_config'],
                diagnostics: ['provider_name' => $config->providerName],
            );
        }

        try {
            $snapshot = $this->healthProbe->probe($config);
        } catch (Throwable) {
            return new AiProviderEndpointCheck(
                endpointGroup: $endpointGroup,
                configured: true,
                enabled: true,
                endpointUri: $this->sanitizedEndpointUri($config->endpointUri),
                probeHealth: AiHealth::UNAVAILABLE->value,
                latencyMs: null,
                severity: AiProviderCheckSeverity::FAILED,
                reasonCodes: ['health_probe_failed'],
                diagnostics: ['provider_name' => $config->providerName],
            );
        }

        return new AiProviderEndpointCheck(
            endpointGroup: $endpointGroup,
            configured: true,
            enabled: true,
            endpointUri: $this->sanitizedEndpointUri($config->endpointUri),
            probeHealth: $snapshot->health->value,
            latencyMs: $snapshot->latencyMs,
            severity: $this->severityForHealth($snapshot->health),
            reasonCodes: $snapshot->reasonCodes,
            diagnostics: $this->sanitizedDiagnostics($snapshot->diagnostics),
        );
    }

    /**
     * @param  list<string>  $reasonCodes
     */
    private function skippedEndpoint(string $endpointGroup, array $reasonCodes): AiProviderEndpointCheck
    {
        return new AiProviderEndpointCheck(
            endpointGroup: $endpointGroup,
            configured: false,
            enabled: false,
            endpointUri: null,
            probeHealth: AiHealth::DISABLED->value,
            latencyMs: null,
            severity: AiProviderCheckSeverity::SKIPPED,
            reasonCodes: $reasonCodes,
            diagnostics: [],
        );
    }

    private function severityForHealth(AiHealth $health): string
    {
        return match ($health) {
            AiHealth::HEALTHY => AiProviderCheckSeverity::OK,
            AiHealth::DEGRADED => AiProviderCheckSeverity::WARNING,
            AiHealth::UNAVAILABLE => AiProviderCheckSeverity::FAILED,
            AiHealth::DISABLED => AiProviderCheckSeverity::SKIPPED,
        };
    }

    /**
     * @return list<AiProviderCheckFinding>
     */
    private function configurationFindings(string $endpointGroup, string $mode, ?AiEndpointConfig $config): array
    {
        if ($mode !== AiStatusSchema::MODE_PROVIDER || $config === null) {
            return [];
        }

        $findings = [];
        $authToken = $config->metadata['auth_token'] ?? null;

        if (! is_string($authToken) || trim($authToken) === '') {
            $findings[] = new AiProviderCheckFinding(
                severity: AiProviderCheckSeverity::WARNING,
                area: 'auth',
                message: 'Endpoint ['.$endpointGroup.'] has no configured provider token.',
                endpointGroup: $endpointGroup,
                reasonCode: 'missing_auth_token',
            );
        }

        if ($this->usesPlainHttpOutsideLocal($config->endpointUri)) {
            $findings[] = new AiProviderCheckFinding(
                severity: AiProviderCheckSeverity::WARNING,
                area: 'security',
                message: 'Endpoint ['.$endpointGroup.'] uses plain HTTP outside local development.',
                endpointGroup: $endpointGroup,
                reasonCode: 'plain_http_endpoint',
            );
        }

        if ($config->timeoutSeconds <= 2) {
            $findings[] = new AiProviderCheckFinding(
                severity: AiProviderCheckSeverity::WARNING,
                area: 'timeout',
                message: 'Endpoint ['.$endpointGroup.'] timeout is very low for provider calls.',
                endpointGroup: $endpointGroup,
                reasonCode: 'low_timeout_seconds',
            );
        }

        return $findings;
    }

    private function usesPlainHttpOutsideLocal(string $uri): bool
    {
        $parts = parse_url($uri);
        if (! is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        return $scheme === 'http' && ! in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }

    private function sanitizedEndpointUri(string $uri): string
    {
        $parts = parse_url($uri);
        if (! is_array($parts)) {
            return '[invalid-endpoint-uri]';
        }

        $scheme = isset($parts['scheme']) ? $parts['scheme'].'://' : '';
        $host = (string) ($parts['host'] ?? '');
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = (string) ($parts['path'] ?? '');
        $query = isset($parts['query']) ? '?'.$this->sanitizedQuery($parts['query']) : '';

        return $scheme.$host.$port.$path.$query;
    }

    private function sanitizedQuery(string $query): string
    {
        $pairs = [];

        foreach (explode('&', $query) as $pair) {
            if ($pair === '') {
                continue;
            }

            [$key] = explode('=', $pair, 2) + [1 => ''];
            if (! $this->isSensitiveKey(rawurldecode($key))) {
                $pairs[] = $pair;

                continue;
            }

            $pairs[] = $key.'=[redacted]';
        }

        return implode('&', $pairs);
    }

    private function isSensitiveKey(string $key): bool
    {
        $lowerKey = strtolower($key);

        return str_contains($lowerKey, 'key')
            || str_contains($lowerKey, 'token')
            || str_contains($lowerKey, 'secret')
            || str_contains($lowerKey, 'signature')
            || str_contains($lowerKey, 'credential')
            || str_contains($lowerKey, 'password')
            || str_contains($lowerKey, 'auth');
    }

    /**
     * @param  array<string, scalar|null>  $diagnostics
     * @return array<string, scalar|null>
     */
    private function sanitizedDiagnostics(array $diagnostics): array
    {
        $sanitized = [];

        foreach ($diagnostics as $key => $value) {
            if ($this->isSensitiveKey($key)) {
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}

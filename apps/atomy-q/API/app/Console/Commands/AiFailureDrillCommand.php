<?php

declare(strict_types=1);

namespace App\Console\Commands;

use JsonException;

use Illuminate\Console\Command;

final class AiFailureDrillCommand extends Command
{
    private const SCENARIOS = [
        'ai-off' => [
            'trigger' => 'Set AI_MODE=off and refresh the runtime snapshot.',
            'api_outcome' => 'Manual RFQ continuity remains available; AI-only endpoints return truthful unavailable payloads.',
            'web_outcome' => 'AI controls hide or render scoped unavailable messaging while manual workflow stays visible.',
            'success_signal' => 'No synthetic AI success payloads are emitted.',
        ],
        'single-endpoint-degraded' => [
            'trigger' => 'Degrade one endpoint group or force its health probe to report degraded/unavailable.',
            'api_outcome' => 'Only the affected capability group degrades; unrelated endpoint groups remain unchanged.',
            'web_outcome' => 'Only the affected AI panel/callout changes state.',
            'success_signal' => 'The rest of the RFQ chain remains usable.',
        ],
        'provider-auth-failure' => [
            'trigger' => 'Use an invalid token or service credential for the selected endpoint group.',
            'api_outcome' => 'Provider calls fail loudly with truthful unavailable/failure behavior.',
            'web_outcome' => 'The affected AI surface becomes unavailable and manual continuity remains visible.',
            'success_signal' => 'Operational logs include provider_auth_failed.',
        ],
        'quota-exceeded' => [
            'trigger' => 'Force the provider to return HTTP 429 or an equivalent quota error.',
            'api_outcome' => 'The capability falls back truthfully and alerts publish once per cooldown window.',
            'web_outcome' => 'AI assist surfaces show scoped unavailable copy rather than empty success.',
            'success_signal' => 'Operational logs include provider_quota_exceeded.',
        ],
        'timeout-retry-storm' => [
            'trigger' => 'Force repeated timeouts until the configured retry budget is exhausted.',
            'api_outcome' => 'Retry policy is honored and the request fails truthfully without synthetic fallback data.',
            'web_outcome' => 'Only the AI-powered controls degrade; deterministic/manual continuity remains usable.',
            'success_signal' => 'Operational logs include provider_timeout or provider_retry_exhausted.',
        ],
    ];

    protected $signature = 'atomy:ai-drill
        {scenario : ai-off|single-endpoint-degraded|provider-auth-failure|quota-exceeded|timeout-retry-storm}
        {--json : Emit the drill definition as JSON}';

    protected $description = 'Describe the expected API/WEB outcomes for Atomy-Q AI failure drills.';

    public function handle(): int
    {
        $scenario = (string) $this->argument('scenario');
        $definition = self::SCENARIOS[$scenario] ?? null;
        if (! is_array($definition)) {
            $this->error('Unknown AI drill scenario.');

            return self::FAILURE;
        }

        $payload = ['scenario' => $scenario, ...$definition];
        if ($this->option('json') === true) {
            try {
                $this->line((string) json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
            } catch (JsonException $exception) {
                report($exception);
                $this->error('Failed to encode AI drill payload for scenario: ' . $scenario);

                return self::FAILURE;
            }

            return self::SUCCESS;
        }

        $this->info('AI drill: ' . $scenario);
        $this->line('Trigger: ' . $definition['trigger']);
        $this->line('Expected API outcome: ' . $definition['api_outcome']);
        $this->line('Expected WEB outcome: ' . $definition['web_outcome']);
        $this->line('Success signal: ' . $definition['success_signal']);

        return self::SUCCESS;
    }
}

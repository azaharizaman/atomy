<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Ai\AiProviderCheckFinding;
use App\Services\Ai\AiProviderCheckSeverity;
use App\Services\Ai\AiProviderEndpointCheck;
use App\Services\Ai\AiProviderReadinessResult;
use App\Services\Ai\Contracts\AiProviderReadinessCheckerInterface;
use Illuminate\Console\Command;
use JsonException;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;

/**
 * Reports Atomy-Q AI provider readiness for operator preflight checks.
 */
final class AiProviderCheckCommand extends Command
{
    private const FAIL_ON_WARNING = 'warning';

    protected $signature = 'atomy:ai-provider-check
        {--endpoint-group=* : Restrict checks to one or more endpoint groups}
        {--deep : Run provider contract checks after readiness probes}
        {--json : Emit machine-readable JSON output}
        {--fail-on= : Exit non-zero for warnings when set to "warning"}
        {--publish-alerts : Publish operational alerts from the current AI status snapshot}
        {--tenant-id=plan6-tenant : Tenant identifier used in deep provider sample payloads}
        {--rfq-id=plan6-rfq : RFQ identifier used in deep provider sample payloads}';

    protected $description = 'Check Atomy-Q AI provider readiness across configured endpoint groups.';

    public function __construct(
        private readonly AiProviderReadinessCheckerInterface $checker,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenantId = trim((string) $this->option('tenant-id'));
        $rfqId = trim((string) $this->option('rfq-id'));

        if ($tenantId === '') {
            $this->error('The --tenant-id option must be non-empty.');

            return self::FAILURE;
        }

        if ($rfqId === '') {
            $this->error('The --rfq-id option must be non-empty.');

            return self::FAILURE;
        }

        $failOn = strtolower(trim((string) $this->option('fail-on')));
        if (! in_array($failOn, ['', self::FAIL_ON_WARNING], true)) {
            $this->error('Unsupported --fail-on value ['.$failOn.']. Supported values: warning.');

            return self::FAILURE;
        }

        $endpointGroups = $this->requestedEndpointGroups();
        $unsupported = array_values(array_diff($endpointGroups, AiStatusSchema::endpointGroups()));
        if ($unsupported !== []) {
            $this->error('Unsupported endpoint group(s): '.implode(', ', $unsupported));

            return self::FAILURE;
        }

        $result = $this->checker->check(
            endpointGroups: $endpointGroups,
            deep: (bool) $this->option('deep'),
            publishAlerts: (bool) $this->option('publish-alerts'),
            tenantId: $tenantId,
            rfqId: $rfqId,
        );

        if ((bool) $this->option('json')) {
            if (! $this->writeJsonOutput($result)) {
                return self::FAILURE;
            }
        } else {
            $this->writeHumanOutput($result);
        }

        return $this->exitCodeFor($result, $failOn);
    }

    /**
     * @return list<string>
     */
    private function requestedEndpointGroups(): array
    {
        $requested = $this->option('endpoint-group');
        if (! is_array($requested) || $requested === []) {
            return AiStatusSchema::endpointGroups();
        }

        $filtered = array_values(array_filter(array_map(
            static function (mixed $value): ?string {
                if (! is_string($value)) {
                    return null;
                }

                $normalized = trim($value);

                return $normalized !== '' ? $normalized : null;
            },
            $requested,
        )));

        return $filtered === [] ? AiStatusSchema::endpointGroups() : $filtered;
    }

    private function writeJsonOutput(AiProviderReadinessResult $result): bool
    {
        try {
            $json = json_encode(
                $result->toArray(),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            );
        } catch (JsonException) {
            $this->error('Failed to encode AI provider check payload.');

            return false;
        }

        foreach (explode(PHP_EOL, $json) as $line) {
            $this->line($line);
        }

        return true;
    }

    private function writeHumanOutput(AiProviderReadinessResult $result): void
    {
        $this->info('Atomy-Q AI provider readiness');
        $this->line('Checked at: '.$result->checkedAt);
        $this->line('Mode: '.$result->mode);
        $this->line('Provider: '.$result->provider);
        $this->line('Deep checks: '.($result->deep ? 'yes' : 'no'));
        $this->line('Exit severity: '.$result->exitSeverity());
        $this->newLine();

        $this->table(
            ['Endpoint group', 'Severity', 'Configured', 'Enabled', 'Health', 'Latency', 'Reasons'],
            array_map(
                static fn (AiProviderEndpointCheck $check): array => [
                    $check->endpointGroup,
                    $check->severity,
                    $check->configured ? 'yes' : 'no',
                    $check->enabled ? 'yes' : 'no',
                    $check->probeHealth ?? '-',
                    $check->latencyMs === null ? '-' : (string) $check->latencyMs.'ms',
                    implode(', ', $check->reasonCodes),
                ],
                $result->endpointGroups,
            ),
        );

        $this->newLine();
        $this->info('Operator findings');
        if ($result->operatorFindings === []) {
            $this->line('None.');

            return;
        }

        foreach ($result->operatorFindings as $finding) {
            $this->line($this->formatFinding($finding));
        }
    }

    private function formatFinding(AiProviderCheckFinding $finding): string
    {
        $prefix = '['.$finding->severity.'] '.$finding->area;
        if ($finding->endpointGroup !== null) {
            $prefix .= ' '.$finding->endpointGroup;
        }

        $suffix = $finding->reasonCode === null ? '' : ' ('.$finding->reasonCode.')';

        return $prefix.': '.$finding->message.$suffix;
    }

    private function exitCodeFor(AiProviderReadinessResult $result, string $failOn): int
    {
        $severity = $result->exitSeverity();
        if ($severity === AiProviderCheckSeverity::FAILED) {
            return self::FAILURE;
        }

        if ($severity === AiProviderCheckSeverity::WARNING && $failOn === self::FAIL_ON_WARNING) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}

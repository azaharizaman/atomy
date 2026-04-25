<?php

declare(strict_types=1);

namespace App\Console\Commands;

use JsonException;

use App\Adapters\Ai\Contracts\AiRuntimeStatusInterface;
use App\Services\Ai\Contracts\AiOperationalAlertPublisherInterface;
use Illuminate\Console\Command;

final class AiStatusSnapshotCommand extends Command
{
    protected $signature = 'atomy:ai-status
        {--json : Emit the runtime snapshot as JSON}
        {--publish-alerts : Publish degraded/unavailable alerts after collecting the snapshot}';

    protected $description = 'Capture the current AI runtime snapshot and optionally publish operational alerts.';

    public function __construct(
        readonly AiRuntimeStatusInterface $runtimeStatus,
        readonly AiOperationalAlertPublisherInterface $alertPublisher,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $snapshot = $this->runtimeStatus->snapshot();
        $payload = $snapshot->toArray();
        $payload['provider_name'] = $this->runtimeStatus->providerName();

        if ($this->option('publish-alerts') === true) {
            $payload['published_alerts'] = $this->alertPublisher->publishSnapshot($snapshot);
        }

        if ($this->option('json') === true) {
            try {
                $this->line((string) json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
            } catch (JsonException $exception) {
                report($exception);
                $this->error('Failed to encode AI status payload.');

                return self::FAILURE;
            }

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'AI mode=%s health=%s provider=%s',
            $snapshot->mode,
            $snapshot->globalHealth,
            $this->runtimeStatus->providerName(),
        ));

        $rows = [];
        foreach ($snapshot->capabilityStatuses as $status) {
            $rows[] = [
                $status->featureKey,
                $status->status,
                implode(',', $status->reasonCodes),
                $status->endpointGroup,
            ];
        }

        $this->table(['Feature', 'Status', 'Reason Codes', 'Endpoint Group'], $rows);

        return self::SUCCESS;
    }
}

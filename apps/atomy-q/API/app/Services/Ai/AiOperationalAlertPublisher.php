<?php

declare(strict_types=1);

namespace App\Services\Ai;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Log\LogManager;
use Nexus\Common\Contracts\ClockInterface;
use Nexus\IntelligenceOperations\DTOs\AiCapabilityStatus;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Nexus\IntelligenceOperations\DTOs\AiStatusSnapshot;
use Nexus\Notifier\Contracts\NotificationManagerInterface;
use Nexus\Outbox\Contracts\OutboxServiceInterface;
use Nexus\Outbox\Domain\OutboxEnqueueCommand;
use Nexus\Outbox\ValueObjects\DedupKey;
use Nexus\Outbox\ValueObjects\EventTypeRef;
use Nexus\Outbox\ValueObjects\TenantId;
use Throwable;

final readonly class AiOperationalAlertPublisher
{
    public function __construct(
        private ClockInterface $clock,
        private CacheRepository $cache,
        private LogManager $logs,
        private ?NotificationManagerInterface $notificationManager = null,
        private ?OutboxServiceInterface $outbox = null,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function publishSnapshot(AiStatusSnapshot $snapshot): array
    {
        $published = [];

        foreach ($snapshot->capabilityStatuses as $capabilityStatus) {
            if (! $capabilityStatus instanceof AiCapabilityStatus) {
                continue;
            }

            if (! in_array($capabilityStatus->status, [
                AiStatusSchema::CAPABILITY_STATUS_DEGRADED,
                AiStatusSchema::CAPABILITY_STATUS_UNAVAILABLE,
            ], true)) {
                continue;
            }

            if (! $this->shouldPublish($capabilityStatus)) {
                continue;
            }

            $notification = new AiOperationalAlertNotification(
                featureKey: $capabilityStatus->featureKey,
                capabilityGroup: $capabilityStatus->capabilityGroup,
                status: $capabilityStatus->status,
                reasonCodes: $capabilityStatus->reasonCodes,
                diagnostics: $capabilityStatus->diagnostics,
            );

            $channels = [];

            if ($this->outbox instanceof OutboxServiceInterface) {
                $this->publishOutboxAlert($snapshot, $capabilityStatus);
                $channels[] = 'outbox';
            }

            if ($this->notificationManager instanceof NotificationManagerInterface) {
                $recipients = $this->configuredRecipients();
                if ($recipients !== []) {
                    $this->notificationManager->sendBatch($recipients, $notification, ['email']);
                    $channels[] = 'notifier';
                }
            }

            $context = $this->alertLogContext($snapshot, $capabilityStatus, $channels);
            $this->logger()->warning('AI capability alert published.', $context);

            $published[] = $context;
        }

        return $published;
    }

    private function shouldPublish(AiCapabilityStatus $status): bool
    {
        $cooldownSeconds = max(60, (int) config('atomy.ai.operations.alert_cooldown_seconds', 300));
        $cacheKey = 'ai:alert:' . sha1(json_encode([
            'feature_key' => $status->featureKey,
            'status' => $status->status,
            'reason_codes' => $status->reasonCodes,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: $status->featureKey . ':' . $status->status);

        if ($this->cache->has($cacheKey)) {
            return false;
        }

        $this->cache->put($cacheKey, $this->clock->now()->format(DATE_ATOM), $cooldownSeconds);

        return true;
    }

    /**
     * @return list<AiOperationalAlertRecipient>
     */
    private function configuredRecipients(): array
    {
        $configured = config('atomy.ai.operations.alert_recipient_emails', []);
        if (! is_array($configured)) {
            return [];
        }

        $emails = array_values(array_unique(array_filter(array_map(static function (mixed $value): ?string {
            if (! is_string($value)) {
                return null;
            }

            $email = trim($value);

            return $email === '' ? null : strtolower($email);
        }, $configured))));

        return array_map(
            static fn (string $email): AiOperationalAlertRecipient => new AiOperationalAlertRecipient($email),
            $emails,
        );
    }

    private function publishOutboxAlert(AiStatusSnapshot $snapshot, AiCapabilityStatus $status): void
    {
        try {
            $this->outbox?->enqueue(new OutboxEnqueueCommand(
                tenantId: new TenantId('atomy-ai-ops'),
                dedupKey: new DedupKey(
                    'ai-alert:' . $status->featureKey . ':' . $status->status . ':' . implode(',', $status->reasonCodes),
                ),
                eventType: new EventTypeRef('atomy_q.ai.capability_alert'),
                payload: [
                    'feature_key' => $status->featureKey,
                    'capability_group' => $status->capabilityGroup,
                    'endpoint_group' => $status->endpointGroup,
                    'status' => $status->status,
                    'reason_codes' => $status->reasonCodes,
                    'diagnostics' => $status->diagnostics,
                    'mode' => $snapshot->mode,
                    'global_health' => $snapshot->globalHealth,
                    'generated_at' => $snapshot->generatedAt->format(DATE_ATOM),
                ],
                metadata: [
                    'source' => 'atomy-q-api',
                    'channel' => 'ai_operations',
                ],
                correlationId: $status->featureKey,
                causationId: null,
                createdAt: $this->clock->now(),
            ));
        } catch (Throwable $exception) {
            $this->logger()->warning('AI capability alert outbox publish failed.', [
                'feature_key' => $status->featureKey,
                'capability_group' => $status->capabilityGroup,
                'status' => $status->status,
                'reason_codes' => $status->reasonCodes,
                'outcome' => 'outbox_failed',
                'reason_code' => 'alert_publish_failed',
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param list<string> $channels
     * @return array<string, mixed>
     */
    private function alertLogContext(AiStatusSnapshot $snapshot, AiCapabilityStatus $status, array $channels): array
    {
        return [
            'ai_mode' => $snapshot->mode,
            'capability_group' => $status->capabilityGroup,
            'feature_key' => $status->featureKey,
            'provider' => config('atomy.ai.provider.name') ?: config('atomy.ai.provider.key'),
            'endpoint_group' => $status->endpointGroup,
            'tenant_id' => null,
            'rfq_id' => null,
            'outcome' => 'alert_published',
            'reason_code' => $status->reasonCodes[0] ?? 'provider_unavailable',
            'status' => $status->status,
            'channels' => $channels,
        ];
    }

    private function logger(): \Psr\Log\LoggerInterface
    {
        return $this->logs->channel((string) config('atomy.ai.operations.log_channel', 'stack'));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Ai\AiOperationalAlertPublisher;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Log\LogManager;
use Nexus\Common\Contracts\ClockInterface;
use Nexus\IntelligenceOperations\DTOs\AiCapabilityStatus;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Nexus\IntelligenceOperations\DTOs\AiStatusSnapshot;
use Nexus\Notifier\Contracts\NotificationManagerInterface;
use Nexus\Outbox\Contracts\OutboxServiceInterface;
use Tests\TestCase;

final class AiOperationalAlertPublisherTest extends TestCase
{
    public function testPublishSnapshotSendsNotifierAndOutboxAlertsForDegradedCapability(): void
    {
        config()->set('atomy.ai.operations.alert_recipient_emails', ['ops@example.test']);
        config()->set('atomy.ai.operations.alert_cooldown_seconds', 300);
        config()->set('atomy.ai.operations.log_channel', 'stack');
        config()->set('atomy.ai.provider.key', 'openrouter');

        $cache = $this->app->make(CacheFactory::class)->store();
        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn(new \DateTimeImmutable('2026-04-24T03:00:00+00:00'));

        $notifier = $this->createMock(NotificationManagerInterface::class);
        $notifier->expects(self::once())
            ->method('sendBatch')
            ->with(
                self::callback(static fn (array $recipients): bool => count($recipients) === 1),
                self::anything(),
                ['email'],
            )
            ->willReturn(['ai-ops:ops@example.test' => 'notification-1']);

        $outbox = $this->createMock(OutboxServiceInterface::class);
        $outbox->expects(self::once())
            ->method('enqueue');

        $publisher = new AiOperationalAlertPublisher(
            clock: $clock,
            cache: $cache,
            logs: $this->app->make(LogManager::class),
            notificationManager: $notifier,
            outbox: $outbox,
        );

        $published = $publisher->publishSnapshot($this->degradedSnapshot());

        self::assertCount(1, $published);
        self::assertSame('comparison_ai_overlay', $published[0]['feature_key']);
        self::assertSame('alert_published', $published[0]['outcome']);
    }

    private function degradedSnapshot(): AiStatusSnapshot
    {
        return new AiStatusSnapshot(
            mode: AiStatusSchema::MODE_PROVIDER,
            globalHealth: AiStatusSchema::HEALTH_DEGRADED,
            capabilityDefinitions: [],
            capabilityStatuses: [
                'comparison_ai_overlay' => new AiCapabilityStatus(
                    featureKey: 'comparison_ai_overlay',
                    capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_COMPARISON_INTELLIGENCE,
                    endpointGroup: AiStatusSchema::ENDPOINT_GROUP_COMPARISON_AWARD,
                    fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                    messageKey: 'ai.comparison_ai_overlay.degraded',
                    status: AiStatusSchema::CAPABILITY_STATUS_DEGRADED,
                    available: false,
                    reasonCodes: ['endpoint_group_degraded'],
                    operatorCritical: true,
                    diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
                ),
            ],
            endpointGroupHealthSnapshots: [],
            reasonCodes: ['endpoint_group_degraded'],
            generatedAt: new \DateTimeImmutable('2026-04-24T03:00:00+00:00'),
        );
    }
}

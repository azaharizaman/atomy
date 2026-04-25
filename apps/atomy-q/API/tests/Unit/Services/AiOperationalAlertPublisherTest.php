<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use DateTimeImmutable;
use App\Services\Ai\AiOperationalAlertPublisher;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Support\Carbon;
use Nexus\Common\Contracts\ClockInterface;
use Nexus\IntelligenceOperations\DTOs\AiCapabilityStatus;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Nexus\IntelligenceOperations\DTOs\AiStatusSnapshot;
use Nexus\Notifier\Contracts\NotificationManagerInterface;
use Nexus\Outbox\Contracts\OutboxServiceInterface;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

final class AiOperationalAlertPublisherTest extends TestCase
{
    private CacheRepository $cache;

    private ClockInterface $clock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new CacheRepository(new ArrayStore());
        $this->clock = new readonly class implements ClockInterface {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable(Carbon::now('UTC')->format(DATE_ATOM));
            }
        };

        Carbon::setTestNow('2026-04-24T03:00:00+00:00');

        config()->set('atomy.ai.operations.alert_recipient_emails', ['ops@example.test']);
        config()->set('atomy.ai.operations.alert_cooldown_seconds', 240);
        config()->set('atomy.ai.provider.key', 'openrouter');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function testPublishSnapshotSendsNotifierAndOutboxAlertsForDegradedCapability(): void
    {
        $notifier = $this->createMock(NotificationManagerInterface::class);
        $notifier->expects(self::once())
            ->method('sendBatch')
            ->with(
                self::callback(static fn (array $recipients): bool => count($recipients) === 1),
                self::anything(),
                ['email'],
            )
            ->willReturn(['ai-ops:3cc31cd246149aec' => 'notification-1']);

        $outbox = $this->createMock(OutboxServiceInterface::class);
        $outbox->expects(self::once())
            ->method('enqueue');

        $published = $this->publisher($notifier, $outbox)->publishSnapshot($this->snapshot(
            featureKey: 'comparison_ai_overlay',
            status: AiStatusSchema::CAPABILITY_STATUS_DEGRADED,
            reasonCodes: ['endpoint_group_degraded'],
        ));

        self::assertCount(1, $published);
        self::assertSame('comparison_ai_overlay', $published[0]['feature_key']);
        self::assertSame('alert_published', $published[0]['outcome']);
    }

    public function testPublishSnapshotDoesNotSendForAvailableCapabilityWithoutActiveIncident(): void
    {
        $notifier = $this->createMock(NotificationManagerInterface::class);
        $notifier->expects(self::never())->method('sendBatch');

        $outbox = $this->createMock(OutboxServiceInterface::class);
        $outbox->expects(self::never())->method('enqueue');

        $published = $this->publisher($notifier, $outbox)->publishSnapshot($this->snapshot(
            featureKey: 'comparison_ai_overlay',
            status: AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
            reasonCodes: ['provider_available'],
        ));

        self::assertSame([], $published);
    }

    public function testPublishSnapshotSuppressesSecondAlertWithinCooldownWindow(): void
    {
        $notifier = $this->createMock(NotificationManagerInterface::class);
        $notifier->expects(self::once())
            ->method('sendBatch')
            ->willReturn(['ai-ops:3cc31cd246149aec' => 'notification-1']);

        $outbox = $this->createMock(OutboxServiceInterface::class);
        $outbox->expects(self::once())
            ->method('enqueue');

        $publisher = $this->publisher($notifier, $outbox);
        $first = $publisher->publishSnapshot($this->snapshot(
            featureKey: 'comparison_ai_overlay',
            status: AiStatusSchema::CAPABILITY_STATUS_DEGRADED,
            reasonCodes: ['endpoint_group_degraded'],
        ));

        Carbon::setTestNow('2026-04-24T03:03:59+00:00');

        $second = $publisher->publishSnapshot($this->snapshot(
            featureKey: 'comparison_ai_overlay',
            status: AiStatusSchema::CAPABILITY_STATUS_DEGRADED,
            reasonCodes: ['endpoint_group_degraded'],
        ));

        self::assertCount(1, $first);
        self::assertSame([], $second);
    }

    public function testPublishSnapshotRepublishesAtCooldownBoundary(): void
    {
        $notifier = $this->createMock(NotificationManagerInterface::class);
        $notifier->expects(self::exactly(2))
            ->method('sendBatch')
            ->willReturn(['ai-ops:3cc31cd246149aec' => 'notification-1']);

        $outbox = $this->createMock(OutboxServiceInterface::class);
        $outbox->expects(self::exactly(2))
            ->method('enqueue');

        $publisher = $this->publisher($notifier, $outbox);
        $publisher->publishSnapshot($this->snapshot(
            featureKey: 'comparison_ai_overlay',
            status: AiStatusSchema::CAPABILITY_STATUS_DEGRADED,
            reasonCodes: ['endpoint_group_degraded'],
        ));

        Carbon::setTestNow('2026-04-24T03:04:00+00:00');

        $republished = $publisher->publishSnapshot($this->snapshot(
            featureKey: 'comparison_ai_overlay',
            status: AiStatusSchema::CAPABILITY_STATUS_DEGRADED,
            reasonCodes: ['endpoint_group_degraded'],
        ));

        self::assertCount(1, $republished);
    }

    public function testPublishSnapshotClearsPreviouslyActiveIncidentWhenCapabilityRecovers(): void
    {
        $notifier = $this->createMock(NotificationManagerInterface::class);
        $notifier->expects(self::exactly(2))
            ->method('sendBatch')
            ->willReturn(['ai-ops:3cc31cd246149aec' => 'notification-1']);

        $outbox = $this->createMock(OutboxServiceInterface::class);
        $outbox->expects(self::exactly(2))
            ->method('enqueue');

        $publisher = $this->publisher($notifier, $outbox);
        $publisher->publishSnapshot($this->snapshot(
            featureKey: 'comparison_ai_overlay',
            status: AiStatusSchema::CAPABILITY_STATUS_DEGRADED,
            reasonCodes: ['endpoint_group_degraded'],
        ));

        $cleared = $publisher->publishSnapshot($this->snapshot(
            featureKey: 'comparison_ai_overlay',
            status: AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
            reasonCodes: ['provider_available'],
        ));

        self::assertCount(1, $cleared);
        self::assertSame('alert_cleared', $cleared[0]['outcome']);
    }

    public function testPublishSnapshotEnqueuesOutboxWithoutEmailRecipients(): void
    {
        config()->set('atomy.ai.operations.alert_recipient_emails', []);

        $notifier = $this->createMock(NotificationManagerInterface::class);
        $notifier->expects(self::never())->method('sendBatch');

        $outbox = $this->createMock(OutboxServiceInterface::class);
        $outbox->expects(self::once())
            ->method('enqueue');

        $published = $this->publisher($notifier, $outbox)->publishSnapshot($this->snapshot(
            featureKey: 'comparison_ai_overlay',
            status: AiStatusSchema::CAPABILITY_STATUS_UNAVAILABLE,
            reasonCodes: ['endpoint_group_unavailable'],
        ));

        self::assertCount(1, $published);
        self::assertSame(['outbox'], $published[0]['channels']);
    }

    private function publisher(
        ?NotificationManagerInterface $notifier = null,
        ?OutboxServiceInterface $outbox = null,
    ): AiOperationalAlertPublisher {
        return new AiOperationalAlertPublisher(
            clock: $this->clock,
            cache: $this->cache,
            logger: $this->createMock(LoggerInterface::class),
            notificationManager: $notifier,
            outbox: $outbox,
        );
    }

    /**
     * @param list<string> $reasonCodes
     */
    private function snapshot(string $featureKey, string $status, array $reasonCodes): AiStatusSnapshot
    {
        return new AiStatusSnapshot(
            mode: AiStatusSchema::MODE_PROVIDER,
            globalHealth: $status === AiStatusSchema::CAPABILITY_STATUS_AVAILABLE
                ? AiStatusSchema::HEALTH_HEALTHY
                : AiStatusSchema::HEALTH_DEGRADED,
            capabilityDefinitions: [],
            capabilityStatuses: [
                $featureKey => new AiCapabilityStatus(
                    featureKey: $featureKey,
                    capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_COMPARISON_INTELLIGENCE,
                    endpointGroup: AiStatusSchema::ENDPOINT_GROUP_COMPARISON_AWARD,
                    fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                    messageKey: 'ai.' . $featureKey . '.' . $status,
                    status: $status,
                    available: $status === AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
                    reasonCodes: $reasonCodes,
                    operatorCritical: true,
                    diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
                ),
            ],
            endpointGroupHealthSnapshots: [],
            reasonCodes: $reasonCodes,
            generatedAt: new DateTimeImmutable(Carbon::now('UTC')->format(DATE_ATOM)),
        );
    }
}

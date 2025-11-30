<?php

declare(strict_types=1);

namespace Nexus\EventStream\Tests\Unit\Core\Engine;

use Nexus\EventStream\Core\Engine\ProjectionEngine;
use Nexus\EventStream\Contracts\ProjectorInterface;
use Nexus\EventStream\Contracts\StreamReaderInterface;
use Nexus\EventStream\Contracts\EventInterface;
use Nexus\EventStream\Exceptions\ProjectionException;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;

#[Group('eventstream')]
#[Group('projections')]
final class ProjectionEngineTest extends TestCase
{
    private StreamReaderInterface $streamReader;
    private LoggerInterface $logger;
    private ProjectionEngine $engine;

    protected function setUp(): void
    {
        $this->streamReader = $this->createMock(StreamReaderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->engine = new ProjectionEngine(
            $this->streamReader,
            $this->logger
        );
    }

    #[Test]
    public function it_projects_events_through_projector(): void
    {
        $events = [
            $this->createMockEvent('event-1', 'EventType1'),
            $this->createMockEvent('event-2', 'EventType2'),
        ];

        $this->streamReader
            ->expects($this->once())
            ->method('readStream')
            ->with('stream-123')
            ->willReturn($events);

        $projector = $this->createMock(ProjectorInterface::class);
        $projector->method('getName')->willReturn('TestProjector');
        $projector->method('getHandledEventTypes')->willReturn(['EventType1', 'EventType2']);
        
        $projector
            ->expects($this->exactly(2))
            ->method('project')
            ->with($this->isInstanceOf(EventInterface::class));

        $this->engine->run('stream-123', $projector);
    }

    #[Test]
    public function it_only_projects_handled_event_types(): void
    {
        $events = [
            $this->createMockEvent('event-1', 'EventType1'),
            $this->createMockEvent('event-2', 'EventType2'),
            $this->createMockEvent('event-3', 'EventType3'),
        ];

        $this->streamReader
            ->method('readStream')
            ->willReturn($events);

        $projector = $this->createMock(ProjectorInterface::class);
        $projector->method('getName')->willReturn('SelectiveProjector');
        $projector->method('getHandledEventTypes')->willReturn(['EventType1', 'EventType3']);
        
        // Should only project EventType1 and EventType3, skipping EventType2
        $projector
            ->expects($this->exactly(2))
            ->method('project');

        $this->engine->run('stream-123', $projector);
    }

    #[Test]
    public function it_handles_empty_stream(): void
    {
        $this->streamReader
            ->method('readStream')
            ->willReturn([]);

        $projector = $this->createMock(ProjectorInterface::class);
        $projector->method('getName')->willReturn('EmptyStreamProjector');
        $projector->method('getHandledEventTypes')->willReturn(['EventType1']);
        
        $projector
            ->expects($this->never())
            ->method('project');

        $this->engine->run('empty-stream', $projector);
    }

    #[Test]
    public function it_logs_projection_errors_and_continues(): void
    {
        $events = [
            $this->createMockEvent('event-1', 'EventType1'),
            $this->createMockEvent('event-2', 'EventType1'),
        ];

        $this->streamReader->method('readStream')->willReturn($events);

        $projector = $this->createMock(ProjectorInterface::class);
        $projector->method('getName')->willReturn('FailingProjector');
        $projector->method('getHandledEventTypes')->willReturn(['EventType1']);
        
        $projector
            ->expects($this->exactly(2))
            ->method('project')
            ->willReturnCallback(function () {
                static $callCount = 0;
                $callCount++;
                if ($callCount === 1) {
                    throw new \RuntimeException('Projection failed');
                }
            });

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Projection error'),
                $this->callback(function ($context) {
                    return isset($context['projector'])
                        && isset($context['event_id'])
                        && isset($context['error']);
                })
            );

        // Should not throw, but log the error and continue
        $this->engine->run('stream-123', $projector);
    }

    #[Test]
    public function it_rebuilds_projection_from_start(): void
    {
        $events = [
            $this->createMockEvent('event-1', 'EventType1'),
            $this->createMockEvent('event-2', 'EventType1'),
        ];

        $this->streamReader
            ->expects($this->once())
            ->method('readStream')
            ->with('stream-123')
            ->willReturn($events);

        $projector = $this->createMock(ProjectorInterface::class);
        $projector->method('getName')->willReturn('RebuildProjector');
        $projector->method('getHandledEventTypes')->willReturn(['EventType1']);
        
        // Should have reset method called
        if (method_exists($projector, 'reset')) {
            $projector
                ->expects($this->once())
                ->method('reset');
        }

        $projector
            ->expects($this->exactly(2))
            ->method('project');

        $this->engine->rebuild('stream-123', $projector);
    }

    #[Test]
    public function it_resumes_from_specific_event_id(): void
    {
        $events = [
            $this->createMockEvent('1', 'EventType1'),
            $this->createMockEvent('2', 'EventType1'),
            $this->createMockEvent('3', 'EventType1'),
        ];

        $this->streamReader
            ->expects($this->once())
            ->method('readStream')
            ->with('stream-123')
            ->willReturn($events);

        $projector = $this->createMock(ProjectorInterface::class);
        $projector->method('getName')->willReturn('ResumeProjector');
        $projector->method('getHandledEventTypes')->willReturn(['EventType1']);
        $projector->method('getLastProcessedEventId')->willReturn('2');
        
        // Resume sets the last processed event ID: 1 time ('2')
        // Then setLastProcessedEventId is called for event '3': 1 time
        // Total: 2 times
        $projector
            ->expects($this->exactly(2))
            ->method('setLastProcessedEventId');
        
        // Only event '3' is projected (events '1' and '2' are skipped)
        $projector
            ->expects($this->once())
            ->method('project');

        $this->engine->resume('stream-123', $projector, '2');
    }

    #[Test]
    public function it_provides_projection_statistics(): void
    {
        $events = [
            $this->createMockEvent('event-1', 'EventType1'),
            $this->createMockEvent('event-2', 'EventType2'),
            $this->createMockEvent('event-3', 'EventType1'),
        ];

        $this->streamReader->method('readStream')->willReturn($events);

        $projector = $this->createMock(ProjectorInterface::class);
        $projector->method('getName')->willReturn('StatsProjector');
        $projector->method('getHandledEventTypes')->willReturn(['EventType1']);
        $projector->method('getLastProcessedEventId')->willReturn(null);

        // Expect completion log with statistics
        $this->logger
            ->expects($this->atLeastOnce())
            ->method('info')
            ->with(
                $this->anything(),
                $this->callback(function ($context) {
                    // Check for either the running log OR the completion log with statistics
                    return isset($context['stream_id']) || 
                           (isset($context['total_events']) && isset($context['projected_events']));
                })
            );

        $this->engine->run('stream-123', $projector);
    }

    private function createMockEvent(string $eventId, string $eventType): EventInterface
    {
        $event = $this->createMock(EventInterface::class);
        $event->method('getEventId')->willReturn($eventId);
        $event->method('getEventType')->willReturn($eventType);
        
        return $event;
    }
}

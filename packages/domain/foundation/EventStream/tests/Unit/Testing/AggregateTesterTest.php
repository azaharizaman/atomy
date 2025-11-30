<?php

declare(strict_types=1);

namespace Nexus\EventStream\Tests\Unit\Testing;

use Nexus\EventStream\Contracts\EventInterface;
use Nexus\EventStream\Testing\AggregateTester;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AggregateTester::class)]
final class AggregateTesterTest extends TestCase
{
    #[Test]
    public function it_implements_aggregate_tester_interface(): void
    {
        $tester = new AggregateTester();

        $this->assertInstanceOf(\Nexus\EventStream\Testing\AggregateTesterInterface::class, $tester);
    }

    #[Test]
    public function it_supports_fluent_interface(): void
    {
        $tester = new AggregateTester();
        $event1 = $this->createMockEvent('Event1');
        $event2 = $this->createMockEvent('Event2');

        $result = $tester
            ->given([$event1])
            ->when(fn() => [$event2]);

        $this->assertSame($tester, $result);
    }

    #[Test]
    public function it_can_test_aggregate_with_empty_history(): void
    {
        $tester = new AggregateTester();
        $newEvent = $this->createMockEvent('AccountCreated');

        $tester
            ->given([])
            ->when(fn() => [$newEvent])
            ->then([$newEvent]);

        $this->assertTrue(true); // Assertion passed
    }

    #[Test]
    public function it_can_test_aggregate_with_historical_events(): void
    {
        $tester = new AggregateTester();
        $event1 = $this->createMockEvent('AccountCreated');
        $event2 = $this->createMockEvent('AccountCredited');
        $event3 = $this->createMockEvent('AccountDebited');

        $tester
            ->given([$event1, $event2])
            ->when(fn() => [$event3])
            ->then([$event3]);

        $this->assertTrue(true); // Assertion passed
    }

    #[Test]
    public function it_asserts_expected_events_match_actual_events(): void
    {
        $tester = new AggregateTester();
        $event = $this->createMockEvent('AccountDebited', ['amount' => 500]);

        $tester
            ->given([])
            ->when(fn() => [$event])
            ->then([$event]);

        $this->assertTrue(true); // Assertion passed
    }

    #[Test]
    public function it_fails_when_event_count_differs(): void
    {
        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Expected 2 events, but got 1');

        $tester = new AggregateTester();
        $event1 = $this->createMockEvent('Event1');
        $event2 = $this->createMockEvent('Event2');

        $tester
            ->given([])
            ->when(fn() => [$event1])
            ->then([$event1, $event2]); // Expects 2, got 1
    }

    #[Test]
    public function it_fails_when_event_types_differ(): void
    {
        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Event type mismatch at index 0: expected');

        $tester = new AggregateTester();
        $event1 = $this->createMockEvent('AccountCreated');
        $event2 = $this->createMockEvent('AccountDebited');

        $tester
            ->given([])
            ->when(fn() => [$event1])
            ->then([$event2]); // Different event type
    }

    #[Test]
    public function it_fails_when_event_payloads_differ(): void
    {
        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Event payload mismatch at index 0');

        $tester = new AggregateTester();
        $event1 = $this->createMockEvent('AccountDebited', ['amount' => 500]);
        $event2 = $this->createMockEvent('AccountDebited', ['amount' => 1000]);

        $tester
            ->given([])
            ->when(fn() => [$event1])
            ->then([$event2]); // Same type, different payload
    }

    #[Test]
    public function it_can_assert_exception_is_thrown(): void
    {
        $tester = new AggregateTester();

        $tester
            ->given([])
            ->when(fn() => throw new \InvalidArgumentException('Insufficient balance'))
            ->thenThrows(\InvalidArgumentException::class);

        $this->assertTrue(true); // Assertion passed
    }

    #[Test]
    public function it_fails_when_expected_exception_not_thrown(): void
    {
        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Expected exception InvalidArgumentException was not thrown');

        $tester = new AggregateTester();
        $event = $this->createMockEvent('Event1');

        $tester
            ->given([])
            ->when(fn() => [$event]) // No exception
            ->thenThrows(\InvalidArgumentException::class);
    }

    #[Test]
    public function it_fails_when_wrong_exception_type_thrown(): void
    {
        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Expected exception InvalidArgumentException but got RuntimeException');

        $tester = new AggregateTester();

        $tester
            ->given([])
            ->when(fn() => throw new \RuntimeException('Wrong exception'))
            ->thenThrows(\InvalidArgumentException::class);
    }

    #[Test]
    public function it_fails_when_then_called_without_when(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot call then() before when()');

        $tester = new AggregateTester();
        $event = $this->createMockEvent('Event1');

        $tester
            ->given([$event])
            ->then([$event]); // Missing when()
    }

    #[Test]
    public function it_fails_when_then_throws_called_without_when(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot call thenThrows() before when()');

        $tester = new AggregateTester();

        $tester
            ->given([])
            ->thenThrows(\InvalidArgumentException::class); // Missing when()
    }

    #[Test]
    public function it_supports_multiple_events_in_sequence(): void
    {
        $tester = new AggregateTester();
        $event1 = $this->createMockEvent('Event1');
        $event2 = $this->createMockEvent('Event2');
        $event3 = $this->createMockEvent('Event3');

        $tester
            ->given([$event1])
            ->when(fn() => [$event2, $event3])
            ->then([$event2, $event3]);

        $this->assertTrue(true); // Assertion passed
    }

    #[Test]
    public function it_validates_event_order(): void
    {
        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Event type mismatch at index 0');

        $tester = new AggregateTester();
        $event1 = $this->createMockEvent('Event1');
        $event2 = $this->createMockEvent('Event2');

        $tester
            ->given([])
            ->when(fn() => [$event1, $event2])
            ->then([$event2, $event1]); // Wrong order
    }

    #[Test]
    public function it_can_be_reused_for_multiple_tests(): void
    {
        $tester = new AggregateTester();
        $event1 = $this->createMockEvent('Event1');
        $event2 = $this->createMockEvent('Event2');

        // First test
        $tester
            ->given([])
            ->when(fn() => [$event1])
            ->then([$event1]);

        // Second test
        $tester
            ->given([$event1])
            ->when(fn() => [$event2])
            ->then([$event2]);

        $this->assertTrue(true); // Both assertions passed
    }

    #[Test]
    public function it_handles_complex_event_payloads(): void
    {
        $tester = new AggregateTester();
        $complexPayload = [
            'amount' => 1000,
            'currency' => 'MYR',
            'metadata' => ['user' => 'admin', 'timestamp' => '2024-01-01'],
        ];
        $event = $this->createMockEvent('ComplexEvent', $complexPayload);

        $tester
            ->given([])
            ->when(fn() => [$event])
            ->then([$event]);

        $this->assertTrue(true); // Assertion passed
    }

    #[Test]
    public function it_provides_clear_error_messages(): void
    {
        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessageMatches('/Event type mismatch at index 0: expected .+ but got .+/');

        $tester = new AggregateTester();
        $event1 = $this->createMockEvent('ExpectedEvent');
        $event2 = $this->createMockEvent('ActualEvent');

        $tester
            ->given([])
            ->when(fn() => [$event2])
            ->then([$event1]);
    }

    /**
     * Create a mock event for testing
     */
    private function createMockEvent(string $type, array $payload = []): EventInterface
    {
        $event = $this->createMock(EventInterface::class);
        $event->method('getEventType')->willReturn($type);
        $event->method('getPayload')->willReturn($payload);

        return $event;
    }
}

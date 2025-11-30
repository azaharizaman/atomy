<?php

declare(strict_types=1);

namespace Nexus\EventStream\Tests\Unit\Services;

use Nexus\EventStream\Contracts\EventUpcasterInterface;
use Nexus\EventStream\Contracts\UpcasterInterface;
use Nexus\EventStream\Exceptions\UpcasterFailedException;
use Nexus\EventStream\Services\DefaultEventUpcaster;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DefaultEventUpcaster::class)]
final class DefaultEventUpcasterTest extends TestCase
{
    #[Test]
    public function it_implements_event_upcaster_interface(): void
    {
        $upcaster = new DefaultEventUpcaster();

        $this->assertInstanceOf(EventUpcasterInterface::class, $upcaster);
    }

    #[Test]
    public function it_returns_unchanged_data_when_no_upcasters_registered(): void
    {
        $upcaster = new DefaultEventUpcaster();
        $eventData = ['amount' => 1000, 'account' => 'ACC-001'];

        $result = $upcaster->upcastEvent('AccountCreated', 1, $eventData);

        $this->assertSame(1, $result['version']);
        $this->assertSame($eventData, $result['data']);
    }

    #[Test]
    public function it_upcasts_event_through_single_upcaster(): void
    {
        $upcaster = new DefaultEventUpcaster();
        
        $v1ToV2 = $this->createUpcaster('AccountCreated', 1, 2, function ($data) {
            return array_merge($data, ['currency' => 'MYR']);
        });

        $upcaster->registerUpcaster($v1ToV2);

        $result = $upcaster->upcastEvent('AccountCreated', 1, ['amount' => 1000]);

        $this->assertSame(2, $result['version']);
        $this->assertSame(['amount' => 1000, 'currency' => 'MYR'], $result['data']);
    }

    #[Test]
    public function it_chains_multiple_upcasters(): void
    {
        $upcaster = new DefaultEventUpcaster();

        // v1 → v2: Add currency
        $v1ToV2 = $this->createUpcaster('AccountCreated', 1, 2, function ($data) {
            return array_merge($data, ['currency' => 'MYR']);
        });

        // v2 → v3: Add timestamp
        $v2ToV3 = $this->createUpcaster('AccountCreated', 2, 3, function ($data) {
            return array_merge($data, ['created_at' => '2024-01-01']);
        });

        $upcaster
            ->registerUpcaster($v1ToV2)
            ->registerUpcaster($v2ToV3);

        $result = $upcaster->upcastEvent('AccountCreated', 1, ['amount' => 1000]);

        $this->assertSame(3, $result['version']);
        $this->assertSame([
            'amount' => 1000,
            'currency' => 'MYR',
            'created_at' => '2024-01-01',
        ], $result['data']);
    }

    #[Test]
    public function it_skips_upcasters_for_different_event_types(): void
    {
        $upcaster = new DefaultEventUpcaster();

        $accountUpcaster = $this->createUpcaster('AccountCreated', 1, 2, function ($data) {
            return array_merge($data, ['currency' => 'MYR']);
        });

        $upcaster->registerUpcaster($accountUpcaster);

        // Different event type
        $result = $upcaster->upcastEvent('CustomerCreated', 1, ['name' => 'John']);

        $this->assertSame(1, $result['version']);
        $this->assertSame(['name' => 'John'], $result['data']);
    }

    #[Test]
    public function it_only_applies_necessary_upcasters(): void
    {
        $upcaster = new DefaultEventUpcaster();

        $v1ToV2 = $this->createUpcaster('AccountCreated', 1, 2, function ($data) {
            return array_merge($data, ['currency' => 'MYR']);
        });

        $v2ToV3 = $this->createUpcaster('AccountCreated', 2, 3, function ($data) {
            return array_merge($data, ['created_at' => '2024-01-01']);
        });

        $upcaster
            ->registerUpcaster($v1ToV2)
            ->registerUpcaster($v2ToV3);

        // Start from v2, should only apply v2→v3
        $result = $upcaster->upcastEvent('AccountCreated', 2, ['amount' => 1000, 'currency' => 'MYR']);

        $this->assertSame(3, $result['version']);
        $this->assertSame([
            'amount' => 1000,
            'currency' => 'MYR',
            'created_at' => '2024-01-01',
        ], $result['data']);
    }

    #[Test]
    public function it_returns_unchanged_when_already_at_latest_version(): void
    {
        $upcaster = new DefaultEventUpcaster();

        $v1ToV2 = $this->createUpcaster('AccountCreated', 1, 2, function ($data) {
            return array_merge($data, ['currency' => 'MYR']);
        });

        $upcaster->registerUpcaster($v1ToV2);

        // Already at latest version
        $eventData = ['amount' => 1000, 'currency' => 'MYR'];
        $result = $upcaster->upcastEvent('AccountCreated', 2, $eventData);

        $this->assertSame(2, $result['version']);
        $this->assertSame($eventData, $result['data']);
    }

    #[Test]
    public function it_throws_exception_for_version_gap(): void
    {
        $this->expectException(UpcasterFailedException::class);
        $this->expectExceptionMessage('Version gap detected');

        $upcaster = new DefaultEventUpcaster();

        // v1 → v2 exists, but v2 → v3 is missing
        $v1ToV2 = $this->createUpcaster('AccountCreated', 1, 2, function ($data) {
            return $data;
        });

        $v3ToV4 = $this->createUpcaster('AccountCreated', 3, 4, function ($data) {
            return $data;
        });

        $upcaster
            ->registerUpcaster($v1ToV2)
            ->registerUpcaster($v3ToV4);

        // Try to upcast from v1, but v2→v3 is missing
        $upcaster->upcastEvent('AccountCreated', 1, ['amount' => 1000]);
    }

    #[Test]
    public function it_propagates_upcaster_exceptions(): void
    {
        $this->expectException(UpcasterFailedException::class);

        $upcaster = new DefaultEventUpcaster();

        $failingUpcaster = $this->createUpcaster('AccountCreated', 1, 2, function () {
            throw new \RuntimeException('Transformation failed');
        });

        $upcaster->registerUpcaster($failingUpcaster);

        $upcaster->upcastEvent('AccountCreated', 1, ['amount' => 1000]);
    }

    #[Test]
    public function it_gets_upcasters_for_event_type(): void
    {
        $upcaster = new DefaultEventUpcaster();

        $accountV1ToV2 = $this->createUpcaster('AccountCreated', 1, 2, fn($d) => $d);
        $accountV2ToV3 = $this->createUpcaster('AccountCreated', 2, 3, fn($d) => $d);
        $customerV1ToV2 = $this->createUpcaster('CustomerCreated', 1, 2, fn($d) => $d);

        $upcaster
            ->registerUpcaster($accountV1ToV2)
            ->registerUpcaster($accountV2ToV3)
            ->registerUpcaster($customerV1ToV2);

        $accountUpcasters = $upcaster->getUpcastersForEventType('AccountCreated');

        $this->assertCount(2, $accountUpcasters);
        $this->assertSame($accountV1ToV2, $accountUpcasters[0]);
        $this->assertSame($accountV2ToV3, $accountUpcasters[1]);
    }

    #[Test]
    public function it_gets_latest_version_for_event_type(): void
    {
        $upcaster = new DefaultEventUpcaster();

        $v1ToV2 = $this->createUpcaster('AccountCreated', 1, 2, fn($d) => $d);
        $v2ToV3 = $this->createUpcaster('AccountCreated', 2, 3, fn($d) => $d);

        $upcaster
            ->registerUpcaster($v1ToV2)
            ->registerUpcaster($v2ToV3);

        $latestVersion = $upcaster->getLatestVersion('AccountCreated');

        $this->assertSame(3, $latestVersion);
    }

    #[Test]
    public function it_returns_null_for_unknown_event_type(): void
    {
        $upcaster = new DefaultEventUpcaster();

        $latestVersion = $upcaster->getLatestVersion('UnknownEvent');

        $this->assertNull($latestVersion);
    }

    #[Test]
    public function it_supports_fluent_interface(): void
    {
        $upcaster = new DefaultEventUpcaster();
        $mockUpcaster = $this->createUpcaster('Test', 1, 2, fn($d) => $d);

        $result = $upcaster->registerUpcaster($mockUpcaster);

        $this->assertSame($upcaster, $result);
    }

    #[Test]
    public function it_handles_complex_data_transformations(): void
    {
        $upcaster = new DefaultEventUpcaster();

        $v1ToV2 = $this->createUpcaster('OrderCreated', 1, 2, function ($data) {
            // Transform flat structure to nested
            return [
                'order_id' => $data['id'],
                'customer' => [
                    'name' => $data['customer_name'],
                    'email' => $data['customer_email'],
                ],
                'items' => $data['items'],
            ];
        });

        $upcaster->registerUpcaster($v1ToV2);

        $v1Data = [
            'id' => 'ORD-001',
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'items' => [['sku' => 'SKU-001', 'qty' => 5]],
        ];

        $result = $upcaster->upcastEvent('OrderCreated', 1, $v1Data);

        $this->assertSame(2, $result['version']);
        $this->assertSame([
            'order_id' => 'ORD-001',
            'customer' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
            'items' => [['sku' => 'SKU-001', 'qty' => 5]],
        ], $result['data']);
    }

    /**
     * Helper to create a mock upcaster
     */
    private function createUpcaster(
        string $eventType,
        int $fromVersion,
        int $toVersion,
        callable $transformer
    ): UpcasterInterface {
        $upcaster = $this->createMock(UpcasterInterface::class);
        
        $upcaster->method('supports')
            ->willReturnCallback(fn($type, $version) => $type === $eventType && $version === $fromVersion);
        
        $upcaster->method('getTargetVersion')
            ->willReturn($toVersion);
        
        $upcaster->method('upcast')
            ->willReturnCallback($transformer);

        return $upcaster;
    }
}

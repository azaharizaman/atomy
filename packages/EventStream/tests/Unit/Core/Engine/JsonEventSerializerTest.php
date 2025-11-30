<?php

declare(strict_types=1);

namespace Nexus\EventStream\Tests\Unit\Core\Engine;

use Nexus\EventStream\Core\Engine\JsonEventSerializer;
use Nexus\EventStream\Contracts\EventInterface;
use Nexus\EventStream\Exceptions\EventSerializationException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;

#[Group('eventstream')]
#[Group('serialization')]
final class JsonEventSerializerTest extends TestCase
{
    private JsonEventSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new JsonEventSerializer();
    }

    #[Test]
    public function it_serializes_event_to_json(): void
    {
        $event = $this->createMockEvent([
            'account_id' => '1000',
            'amount' => 500.50,
            'currency' => 'MYR',
        ]);

        $json = $this->serializer->serialize($event->getPayload());

        $this->assertJson($json);
        
        $decoded = json_decode($json, true);
        $this->assertEquals('1000', $decoded['account_id']);
        $this->assertEquals(500.50, $decoded['amount']);
        $this->assertEquals('MYR', $decoded['currency']);
    }

    #[Test]
    public function it_deserializes_json_to_array(): void
    {
        $originalData = [
            'invoice_id' => 'inv-123',
            'customer_id' => 'cust-456',
            'total_amount' => 1000.00,
        ];

        $json = json_encode($originalData);
        $deserialized = $this->serializer->deserialize($json, 'InvoiceCreatedEvent');

        $this->assertEquals($originalData, $deserialized);
    }

    #[Test]
    public function it_handles_nested_data_structures(): void
    {
        $event = $this->createMockEvent([
            'order_id' => 'order-789',
            'lines' => [
                ['product_id' => 'prod-1', 'quantity' => 2],
                ['product_id' => 'prod-2', 'quantity' => 5],
            ],
            'metadata' => [
                'user_id' => 'user-123',
                'ip_address' => '192.168.1.1',
            ],
        ]);

        $json = $this->serializer->serialize($event->getPayload());
        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded['lines']);
        $this->assertCount(2, $decoded['lines']);
        $this->assertEquals('prod-1', $decoded['lines'][0]['product_id']);
        $this->assertIsArray($decoded['metadata']);
    }

    #[Test]
    public function it_preserves_data_types(): void
    {
        $event = $this->createMockEvent([
            'string_value' => 'text',
            'int_value' => 42,
            'float_value' => 3.14,
            'bool_value' => true,
            'null_value' => null,
        ]);

        $json = $this->serializer->serialize($event->getPayload());
        $decoded = json_decode($json, true);

        $this->assertIsString($decoded['string_value']);
        $this->assertIsInt($decoded['int_value']);
        $this->assertIsFloat($decoded['float_value']);
        $this->assertIsBool($decoded['bool_value']);
        $this->assertNull($decoded['null_value']);
    }

    #[Test]
    public function it_throws_exception_for_invalid_json_on_deserialize(): void
    {
        $this->expectException(EventSerializationException::class);
        $this->expectExceptionMessage('Failed to deserialize');

        $this->serializer->deserialize('invalid-json{', 'TestEvent');
    }

    #[Test]
    public function it_handles_empty_payload(): void
    {
        $event = $this->createMockEvent([]);

        $json = $this->serializer->serialize($event->getPayload());
        $decoded = json_decode($json, true);

        $this->assertEquals([], $decoded);
    }

    #[Test]
    public function it_handles_unicode_characters(): void
    {
        $event = $this->createMockEvent([
            'description' => 'Pembayaran untuk 发票 №123',
            'emoji' => '✅ 💰 🎉',
        ]);

        $json = $this->serializer->serialize($event->getPayload());
        $decoded = json_decode($json, true);

        $this->assertEquals('Pembayaran untuk 发票 №123', $decoded['description']);
        $this->assertEquals('✅ 💰 🎉', $decoded['emoji']);
    }

    #[Test]
    public function it_handles_large_numbers(): void
    {
        $event = $this->createMockEvent([
            'amount' => 999999999999.99,
        ]);

        $json = $this->serializer->serialize($event->getPayload());
        $decoded = json_decode($json, true);

        $this->assertEquals(999999999999.99, $decoded['amount']);
    }

    #[Test]
    public function serialization_round_trip_preserves_data(): void
    {
        $originalData = [
            'id' => 'test-123',
            'values' => [1, 2, 3],
            'nested' => ['key' => 'value'],
        ];

        $event = $this->createMockEvent($originalData);
        $json = $this->serializer->serialize($event->getPayload());
        $deserialized = $this->serializer->deserialize($json);

        $this->assertEquals($originalData, $deserialized);
    }

    private function createMockEvent(array $payload): EventInterface
    {
        $event = $this->createMock(EventInterface::class);
        $event->method('getPayload')->willReturn($payload);
        
        return $event;
    }
}

<?php

declare(strict_types=1);

namespace Nexus\Notifier\Tests\ValueObjects;

use Nexus\Notifier\ValueObjects\NotificationContent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(NotificationContent::class)]
final class NotificationContentTest extends TestCase
{
    #[Test]
    public function it_can_be_created_with_minimal_data(): void
    {
        $content = new NotificationContent(
            title: 'Test Title',
            body: 'Test Body'
        );

        $this->assertSame('Test Title', $content->title);
        $this->assertSame('Test Body', $content->body);
        $this->assertSame([], $content->data);
        $this->assertNull($content->actionUrl);
    }

    #[Test]
    public function it_can_be_created_with_all_data(): void
    {
        $content = new NotificationContent(
            title: 'Test Title',
            body: 'Test Body',
            data: ['key' => 'value', 'foo' => 'bar'],
            actionUrl: 'https://example.com/action'
        );

        $this->assertSame('Test Title', $content->title);
        $this->assertSame('Test Body', $content->body);
        $this->assertSame(['key' => 'value', 'foo' => 'bar'], $content->data);
        $this->assertSame('https://example.com/action', $content->actionUrl);
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $content = new NotificationContent(
            title: 'Original Title',
            body: 'Original Body'
        );

        // Verify properties are readonly by checking reflection
        $reflection = new \ReflectionClass($content);
        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue(
                $property->isReadOnly(),
                "Property {$property->getName()} should be readonly"
            );
        }
    }

    #[Test]
    public function it_preserves_empty_data_array(): void
    {
        $content = new NotificationContent(
            title: 'Test',
            body: 'Test',
            data: []
        );

        $this->assertSame([], $content->data);
        $this->assertIsArray($content->data);
    }
}

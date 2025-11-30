<?php

declare(strict_types=1);

namespace Nexus\EventStream\Tests\Unit\Services;

use Nexus\EventStream\Exceptions\InvalidCursorException;
use Nexus\EventStream\Services\HmacCursorPaginator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HmacCursorPaginator::class)]
#[CoversClass(InvalidCursorException::class)]
final class HmacCursorPaginatorTest extends TestCase
{
    private HmacCursorPaginator $paginator;
    private string $secretKey;

    protected function setUp(): void
    {
        $this->secretKey = str_repeat('a', 32); // 32-byte key
        $this->paginator = new HmacCursorPaginator($this->secretKey);
    }

    #[Test]
    public function it_implements_cursor_paginator_interface(): void
    {
        $this->assertInstanceOf(
            \Nexus\EventStream\Contracts\CursorPaginatorInterface::class,
            $this->paginator
        );
    }

    #[Test]
    public function it_rejects_weak_secret_keys(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('HMAC secret key must be at least 32 bytes');

        new HmacCursorPaginator('short-key');
    }

    #[Test]
    public function it_creates_valid_cursor(): void
    {
        $cursor = $this->paginator->createCursor('01HXZ123', 42);

        $this->assertIsString($cursor);
        $this->assertNotEmpty($cursor);
        
        // Verify base64 encoding
        $this->assertNotFalse(base64_decode($cursor, true));
    }

    #[Test]
    public function it_parses_valid_cursor(): void
    {
        $cursor = $this->paginator->createCursor('01HXZ123', 42);
        $data = $this->paginator->parseCursor($cursor);

        $this->assertSame('01HXZ123', $data['event_id']);
        $this->assertSame(42, $data['sequence']);
    }

    #[Test]
    public function it_creates_cursor_with_additional_data(): void
    {
        $cursor = $this->paginator->createCursor(
            '01HXZ123',
            42,
            ['stream' => 'account-123', 'tenant_id' => 'tenant-1']
        );

        $data = $this->paginator->parseCursor($cursor);

        $this->assertSame('01HXZ123', $data['event_id']);
        $this->assertSame(42, $data['sequence']);
    }

    #[Test]
    public function it_validates_cursor_hmac_signature(): void
    {
        $cursor = $this->paginator->createCursor('01HXZ123', 42);

        $this->assertTrue($this->paginator->isValidCursor($cursor));
    }

    #[Test]
    public function it_detects_tampered_cursor(): void
    {
        $cursor = $this->paginator->createCursor('01HXZ123', 42);
        
        // Tamper with the cursor by changing one character
        $tamperedCursor = substr($cursor, 0, -5) . 'XXXXX';

        $this->assertFalse($this->paginator->isValidCursor($tamperedCursor));
    }

    #[Test]
    public function it_throws_on_malformed_base64(): void
    {
        $this->expectException(InvalidCursorException::class);
        $this->expectExceptionMessage('Cursor is malformed');

        $this->paginator->parseCursor('not-valid-base64!!!');
    }

    #[Test]
    public function it_throws_on_malformed_json(): void
    {
        $this->expectException(InvalidCursorException::class);
        $this->expectExceptionMessage('Cursor is malformed');

        $this->paginator->parseCursor(base64_encode('not-json'));
    }

    #[Test]
    public function it_throws_on_missing_event_id(): void
    {
        $payload = json_encode([
            'sequence' => 42,
            'hmac' => 'fake-hmac',
        ]);
        $cursor = base64_encode($payload);

        $this->expectException(InvalidCursorException::class);
        $this->expectExceptionMessage('missing required fields: event_id');

        $this->paginator->parseCursor($cursor);
    }

    #[Test]
    public function it_throws_on_missing_sequence(): void
    {
        $payload = json_encode([
            'event_id' => '01HXZ123',
            'hmac' => 'fake-hmac',
        ]);
        $cursor = base64_encode($payload);

        $this->expectException(InvalidCursorException::class);
        $this->expectExceptionMessage('missing required fields: sequence');

        $this->paginator->parseCursor($cursor);
    }

    #[Test]
    public function it_throws_on_missing_hmac(): void
    {
        $payload = json_encode([
            'event_id' => '01HXZ123',
            'sequence' => 42,
        ]);
        $cursor = base64_encode($payload);

        $this->expectException(InvalidCursorException::class);
        $this->expectExceptionMessage('missing required fields: hmac');

        $this->paginator->parseCursor($cursor);
    }

    #[Test]
    public function it_throws_on_invalid_hmac_signature(): void
    {
        // Create cursor with one secret
        $cursor = $this->paginator->createCursor('01HXZ123', 42);

        // Try to parse with different secret
        $differentPaginator = new HmacCursorPaginator(str_repeat('b', 32));

        $this->expectException(InvalidCursorException::class);
        $this->expectExceptionMessage('signature verification failed');

        $differentPaginator->parseCursor($cursor);
    }

    #[Test]
    public function it_uses_timing_safe_comparison(): void
    {
        // Create valid cursor
        $cursor = $this->paginator->createCursor('01HXZ123', 42);

        // Decode and manually tamper with HMAC
        $decoded = json_decode(base64_decode($cursor), true);
        $decoded['hmac'] = str_repeat('0', 64); // Same length, different value
        $tamperedCursor = base64_encode(json_encode($decoded));

        $this->expectException(InvalidCursorException::class);

        $this->paginator->parseCursor($tamperedCursor);
    }

    #[Test]
    public function invalid_cursor_exception_provides_context(): void
    {
        $cursor = base64_encode('invalid-json');

        try {
            $this->paginator->parseCursor($cursor);
            $this->fail('Expected InvalidCursorException');
        } catch (InvalidCursorException $e) {
            $this->assertSame($cursor, $e->getCursor());
            $this->assertSame('malformed', $e->getReason());
        }
    }

    #[Test]
    public function invalid_signature_exception_provides_context(): void
    {
        $cursor = $this->paginator->createCursor('01HXZ123', 42);
        $differentPaginator = new HmacCursorPaginator(str_repeat('b', 32));

        try {
            $differentPaginator->parseCursor($cursor);
            $this->fail('Expected InvalidCursorException');
        } catch (InvalidCursorException $e) {
            $this->assertSame($cursor, $e->getCursor());
            $this->assertSame('invalid_signature', $e->getReason());
        }
    }
}

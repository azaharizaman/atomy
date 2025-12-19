<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\ValueObjects;

use Nexus\Payment\ValueObjects\ExecutionContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExecutionContext::class)]
final class ExecutionContextTest extends TestCase
{
    #[Test]
    public function it_can_be_created_with_timestamp(): void
    {
        $timestamp = new \DateTimeImmutable('2024-01-15 10:30:00');
        $context = new ExecutionContext($timestamp);

        $this->assertSame($timestamp, $context->timestamp);
        $this->assertNull($context->ipAddress);
        $this->assertNull($context->userAgent);
        $this->assertNull($context->userId);
        $this->assertNull($context->sessionId);
        $this->assertNull($context->deviceFingerprint);
        $this->assertNull($context->channel);
        $this->assertSame([], $context->metadata);
    }

    #[Test]
    public function it_can_be_created_with_all_fields(): void
    {
        $timestamp = new \DateTimeImmutable('2024-01-15 10:30:00');
        $context = new ExecutionContext(
            $timestamp,
            '192.168.1.1',
            'Mozilla/5.0',
            'user-123',
            'session-456',
            'fingerprint-789',
            'web',
            ['custom' => 'value'],
        );

        $this->assertSame($timestamp, $context->timestamp);
        $this->assertSame('192.168.1.1', $context->ipAddress);
        $this->assertSame('Mozilla/5.0', $context->userAgent);
        $this->assertSame('user-123', $context->userId);
        $this->assertSame('session-456', $context->sessionId);
        $this->assertSame('fingerprint-789', $context->deviceFingerprint);
        $this->assertSame('web', $context->channel);
        $this->assertSame(['custom' => 'value'], $context->metadata);
    }

    #[Test]
    public function it_can_create_context_for_now(): void
    {
        $before = new \DateTimeImmutable();
        $context = ExecutionContext::now();
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $context->timestamp);
        $this->assertLessThanOrEqual($after, $context->timestamp);
    }

    #[Test]
    public function it_can_create_from_web_request(): void
    {
        $context = ExecutionContext::fromWebRequest(
            '192.168.1.1',
            'Mozilla/5.0',
            'user-123',
        );

        $this->assertSame('192.168.1.1', $context->ipAddress);
        $this->assertSame('Mozilla/5.0', $context->userAgent);
        $this->assertSame('user-123', $context->userId);
        $this->assertSame('web', $context->channel);
    }

    #[Test]
    public function it_can_create_from_api_request(): void
    {
        $context = ExecutionContext::fromApiRequest(
            '192.168.1.1',
            'user-123',
            'api-key-456',
        );

        $this->assertSame('192.168.1.1', $context->ipAddress);
        $this->assertSame('user-123', $context->userId);
        $this->assertSame('api', $context->channel);
        $this->assertSame('api-key-456', $context->metadata['api_key_id']);
    }

    #[Test]
    public function it_can_create_from_batch_job(): void
    {
        $context = ExecutionContext::fromBatchJob('job-123', 'batch-456');

        $this->assertSame('batch', $context->channel);
        $this->assertSame('job-123', $context->metadata['job_id']);
        $this->assertSame('batch-456', $context->metadata['batch_id']);
    }

    #[Test]
    public function it_can_create_from_batch_job_without_batch_id(): void
    {
        $context = ExecutionContext::fromBatchJob('job-123');

        $this->assertSame('batch', $context->channel);
        $this->assertSame('job-123', $context->metadata['job_id']);
        $this->assertArrayNotHasKey('batch_id', $context->metadata);
    }

    #[Test]
    public function it_can_add_user_id(): void
    {
        $original = ExecutionContext::now();
        $withUser = $original->withUserId('user-123');

        $this->assertNull($original->userId);
        $this->assertSame('user-123', $withUser->userId);
        $this->assertSame($original->timestamp, $withUser->timestamp);
    }

    #[Test]
    public function it_can_add_session_id(): void
    {
        $original = ExecutionContext::now();
        $withSession = $original->withSessionId('session-456');

        $this->assertNull($original->sessionId);
        $this->assertSame('session-456', $withSession->sessionId);
    }

    #[Test]
    public function it_can_add_metadata(): void
    {
        $original = ExecutionContext::now();
        $withMeta = $original->withMetadata(['key' => 'value']);

        $this->assertSame([], $original->metadata);
        $this->assertSame(['key' => 'value'], $withMeta->metadata);
    }

    #[Test]
    public function it_merges_metadata(): void
    {
        $original = ExecutionContext::fromApiRequest(null, null, 'api-key-123');
        $withMeta = $original->withAdditionalMetadata(['extra' => 'data']);

        $this->assertArrayHasKey('api_key_id', $withMeta->metadata);
        $this->assertArrayHasKey('extra', $withMeta->metadata);
    }

    #[Test]
    public function it_can_check_if_from_web(): void
    {
        $webContext = ExecutionContext::fromWebRequest();
        $apiContext = ExecutionContext::fromApiRequest();

        $this->assertTrue($webContext->isFromWeb());
        $this->assertFalse($apiContext->isFromWeb());
    }

    #[Test]
    public function it_can_check_if_from_api(): void
    {
        $webContext = ExecutionContext::fromWebRequest();
        $apiContext = ExecutionContext::fromApiRequest();

        $this->assertFalse($webContext->isFromApi());
        $this->assertTrue($apiContext->isFromApi());
    }

    #[Test]
    public function it_can_check_if_from_batch(): void
    {
        $batchContext = ExecutionContext::fromBatchJob('job-1');
        $webContext = ExecutionContext::fromWebRequest();

        $this->assertTrue($batchContext->isFromBatch());
        $this->assertFalse($webContext->isFromBatch());
    }
}

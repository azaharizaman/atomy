<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Tests\Unit\Exceptions;

use Nexus\AmlCompliance\Exceptions\AmlException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AmlException::class)]
final class AmlExceptionTest extends TestCase
{
    public function test_constructor_sets_message(): void
    {
        $exception = new AmlException('Test message');
        $this->assertSame('Test message', $exception->getMessage());
    }

    public function test_constructor_sets_code(): void
    {
        $exception = new AmlException('Test', 123);
        $this->assertSame(123, $exception->getCode());
    }

    public function test_constructor_sets_context(): void
    {
        $exception = new AmlException('Test', 0, null, ['key' => 'value']);
        $this->assertSame(['key' => 'value'], $exception->context);
    }

    public function test_constructor_sets_previous_exception(): void
    {
        $previous = new \RuntimeException('Previous');
        $exception = new AmlException('Test', 0, $previous);
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function test_configuration_error_factory(): void
    {
        $exception = AmlException::configurationError('threshold', 'must be positive');

        $this->assertStringContainsString('threshold', $exception->getMessage());
        $this->assertStringContainsString('must be positive', $exception->getMessage());
        $this->assertSame(1001, $exception->getCode());
        $this->assertArrayHasKey('setting', $exception->context);
    }

    public function test_invalid_risk_level_factory(): void
    {
        $exception = AmlException::invalidRiskLevel(150);

        $this->assertStringContainsString('150', $exception->getMessage());
        $this->assertSame(1002, $exception->getCode());
        $this->assertArrayHasKey('score', $exception->context);
        $this->assertSame(150, $exception->context['score']);
    }

    public function test_jurisdiction_not_found_factory(): void
    {
        $exception = AmlException::jurisdictionNotFound('XX');

        $this->assertStringContainsString('XX', $exception->getMessage());
        $this->assertSame(1003, $exception->getCode());
        $this->assertArrayHasKey('country_code', $exception->context);
    }

    public function test_party_not_found_factory(): void
    {
        $exception = AmlException::partyNotFound('party-123');

        $this->assertStringContainsString('party-123', $exception->getMessage());
        $this->assertSame(1004, $exception->getCode());
        $this->assertArrayHasKey('party_id', $exception->context);
    }

    public function test_operation_not_allowed_factory(): void
    {
        $exception = AmlException::operationNotAllowed('delete', 'record is locked');

        $this->assertStringContainsString('delete', $exception->getMessage());
        $this->assertStringContainsString('record is locked', $exception->getMessage());
        $this->assertSame(1005, $exception->getCode());
    }

    public function test_external_service_error_factory(): void
    {
        $exception = AmlException::externalServiceError('SanctionsAPI', 'timeout');

        $this->assertStringContainsString('SanctionsAPI', $exception->getMessage());
        $this->assertStringContainsString('timeout', $exception->getMessage());
        $this->assertSame(1006, $exception->getCode());
    }

    public function test_to_array_returns_structured_data(): void
    {
        $exception = new AmlException('Test', 123, null, ['foo' => 'bar']);

        $array = $exception->toArray();

        $this->assertArrayHasKey('error_type', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('code', $array);
        $this->assertArrayHasKey('context', $array);
        $this->assertSame('AmlException', $array['error_type']);
    }
}

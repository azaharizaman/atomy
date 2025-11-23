<?php

declare(strict_types=1);

namespace Nexus\SSO\Tests\Unit\Exceptions;

use PHPUnit\Framework\TestCase;
use Nexus\SSO\Exceptions\SsoException;
use Nexus\SSO\Exceptions\SsoProviderNotFoundException;
use Nexus\SSO\Exceptions\InvalidCallbackStateException;
use Nexus\SSO\Exceptions\AttributeMappingException;

/**
 * Test for SSO exceptions
 * 
 * TDD Cycle 5: RED phase
 */
final class ExceptionsTest extends TestCase
{
    public function test_sso_exception_is_base_exception(): void
    {
        $exception = new SsoException('Base SSO error');

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertSame('Base SSO error', $exception->getMessage());
    }

    public function test_sso_provider_not_found_exception(): void
    {
        $exception = new SsoProviderNotFoundException('azure');

        $this->assertInstanceOf(SsoException::class, $exception);
        $this->assertStringContainsString('azure', $exception->getMessage());
        $this->assertStringContainsString('not found', $exception->getMessage());
    }

    public function test_invalid_callback_state_exception(): void
    {
        $exception = new InvalidCallbackStateException('State token expired');

        $this->assertInstanceOf(SsoException::class, $exception);
        $this->assertSame('State token expired', $exception->getMessage());
    }

    public function test_attribute_mapping_exception(): void
    {
        $exception = new AttributeMappingException('Missing required field: email');

        $this->assertInstanceOf(SsoException::class, $exception);
        $this->assertSame('Missing required field: email', $exception->getMessage());
    }
}

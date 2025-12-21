<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\Enums;

use Nexus\PaymentGateway\Enums\AuthorizationType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AuthorizationType::class)]
final class AuthorizationTypeTest extends TestCase
{
    #[Test]
    public function it_has_all_expected_types(): void
    {
        $expectedTypes = [
            'PREAUTH',
            'AUTH_CAPTURE',
            'DELAYED_CAPTURE',
            'INCREMENTAL',
        ];

        $actualTypes = array_map(
            fn (AuthorizationType $type) => $type->name,
            AuthorizationType::cases()
        );

        $this->assertSame($expectedTypes, $actualTypes);
    }

    #[Test]
    #[DataProvider('typeLabelProvider')]
    public function it_returns_correct_labels(AuthorizationType $type, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, $type->label());
    }

    public static function typeLabelProvider(): array
    {
        return [
            'preauth' => [AuthorizationType::PREAUTH, 'Pre-Authorization'],
            'auth_capture' => [AuthorizationType::AUTH_CAPTURE, 'Authorize & Capture'],
            'delayed_capture' => [AuthorizationType::DELAYED_CAPTURE, 'Delayed Capture'],
            'incremental' => [AuthorizationType::INCREMENTAL, 'Incremental Authorization'],
        ];
    }

    #[Test]
    public function only_auth_capture_is_auto_capture(): void
    {
        $this->assertFalse(AuthorizationType::PREAUTH->isAutoCapture());
        $this->assertTrue(AuthorizationType::AUTH_CAPTURE->isAutoCapture());
        $this->assertFalse(AuthorizationType::DELAYED_CAPTURE->isAutoCapture());
        $this->assertFalse(AuthorizationType::INCREMENTAL->isAutoCapture());
    }

    #[Test]
    #[DataProvider('typeValueProvider')]
    public function it_has_correct_backing_values(AuthorizationType $type, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $type->value);
    }

    public static function typeValueProvider(): array
    {
        return [
            'preauth' => [AuthorizationType::PREAUTH, 'preauth'],
            'auth_capture' => [AuthorizationType::AUTH_CAPTURE, 'auth_capture'],
            'delayed_capture' => [AuthorizationType::DELAYED_CAPTURE, 'delayed_capture'],
            'incremental' => [AuthorizationType::INCREMENTAL, 'incremental'],
        ];
    }
}

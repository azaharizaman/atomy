<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Tests\ValueObjects;

use Nexus\Domain\Identity\ValueObjects\MfaMethod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MfaMethod::class)]
#[Group('identity')]
#[Group('mfa')]
#[Group('value-objects')]
final class MfaMethodTest extends TestCase
{
    #[Test]
    public function it_has_correct_enum_cases(): void
    {
        $this->assertSame('passkey', MfaMethod::PASSKEY->value);
        $this->assertSame('totp', MfaMethod::TOTP->value);
        $this->assertSame('sms', MfaMethod::SMS->value);
        $this->assertSame('email', MfaMethod::EMAIL->value);
        $this->assertSame('backup_codes', MfaMethod::BACKUP_CODES->value);
    }

    #[Test]
    #[DataProvider('labelProvider')]
    public function it_returns_correct_label(MfaMethod $method, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, $method->label());
    }

    public static function labelProvider(): array
    {
        return [
            'Passkey has biometric label' => [MfaMethod::PASSKEY, 'Passkey (Biometric)'],
            'TOTP has authenticator app label' => [MfaMethod::TOTP, 'Authenticator App (TOTP)'],
            'SMS has SMS label' => [MfaMethod::SMS, 'SMS'],
            'Email has Email label' => [MfaMethod::EMAIL, 'Email'],
            'Backup codes has backup codes label' => [MfaMethod::BACKUP_CODES, 'Backup Codes'],
        ];
    }

    #[Test]
    #[DataProvider('iconProvider')]
    public function it_returns_correct_icon(MfaMethod $method, string $expectedIcon): void
    {
        $this->assertSame($expectedIcon, $method->icon());
    }

    public static function iconProvider(): array
    {
        return [
            'Passkey has fingerprint icon' => [MfaMethod::PASSKEY, 'fingerprint'],
            'TOTP has smartphone icon' => [MfaMethod::TOTP, 'smartphone'],
            'SMS has message icon' => [MfaMethod::SMS, 'message'],
            'Email has mail icon' => [MfaMethod::EMAIL, 'mail'],
            'Backup codes has key icon' => [MfaMethod::BACKUP_CODES, 'key'],
        ];
    }

    #[Test]
    #[DataProvider('requiresEnrollmentProvider')]
    public function it_identifies_enrollment_requirement(MfaMethod $method, bool $expected): void
    {
        $this->assertSame($expected, $method->requiresEnrollment());
    }

    public static function requiresEnrollmentProvider(): array
    {
        return [
            'Passkey requires enrollment' => [MfaMethod::PASSKEY, true],
            'TOTP requires enrollment' => [MfaMethod::TOTP, true],
            'SMS requires enrollment' => [MfaMethod::SMS, true],
            'Email requires enrollment' => [MfaMethod::EMAIL, true],
            'Backup codes require enrollment' => [MfaMethod::BACKUP_CODES, true],
        ];
    }

    #[Test]
    #[DataProvider('canBePrimaryProvider')]
    public function it_identifies_primary_capability(MfaMethod $method, bool $expected): void
    {
        $this->assertSame($expected, $method->canBePrimary());
    }

    public static function canBePrimaryProvider(): array
    {
        return [
            'Passkey can be primary (passwordless)' => [MfaMethod::PASSKEY, true],
            'TOTP can be primary' => [MfaMethod::TOTP, true],
            'SMS cannot be primary' => [MfaMethod::SMS, false],
            'Email cannot be primary' => [MfaMethod::EMAIL, false],
            'Backup codes cannot be primary' => [MfaMethod::BACKUP_CODES, false],
        ];
    }

    #[Test]
    #[DataProvider('isPasswordlessProvider')]
    public function it_identifies_passwordless_capability(MfaMethod $method, bool $expected): void
    {
        $this->assertSame($expected, $method->isPasswordless());
    }

    public static function isPasswordlessProvider(): array
    {
        return [
            'Passkey is passwordless' => [MfaMethod::PASSKEY, true],
            'TOTP is not passwordless' => [MfaMethod::TOTP, false],
            'SMS is not passwordless' => [MfaMethod::SMS, false],
            'Email is not passwordless' => [MfaMethod::EMAIL, false],
            'Backup codes are not passwordless' => [MfaMethod::BACKUP_CODES, false],
        ];
    }

    #[Test]
    public function it_can_be_instantiated_from_string(): void
    {
        $method = MfaMethod::from('passkey');
        
        $this->assertInstanceOf(MfaMethod::class, $method);
        $this->assertSame(MfaMethod::PASSKEY, $method);
    }

    #[Test]
    public function it_provides_all_cases(): void
    {
        $cases = MfaMethod::cases();
        
        $this->assertCount(5, $cases);
        $this->assertContains(MfaMethod::PASSKEY, $cases);
        $this->assertContains(MfaMethod::TOTP, $cases);
        $this->assertContains(MfaMethod::SMS, $cases);
        $this->assertContains(MfaMethod::EMAIL, $cases);
        $this->assertContains(MfaMethod::BACKUP_CODES, $cases);
    }
}

<?php

declare(strict_types=1);

namespace Nexus\Identity\Tests\ValueObjects;

use Nexus\Identity\ValueObjects\AuthenticatorAttachment;
use Nexus\Identity\ValueObjects\AuthenticatorSelection;
use Nexus\Identity\ValueObjects\UserVerificationRequirement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AuthenticatorSelection::class)]
final class AuthenticatorSelectionTest extends TestCase
{
    #[Test]
    public function it_can_be_created_with_defaults(): void
    {
        $selection = new AuthenticatorSelection();

        $this->assertNull($selection->authenticatorAttachment);
        $this->assertFalse($selection->requireResidentKey);
        $this->assertSame(UserVerificationRequirement::PREFERRED, $selection->userVerification);
    }

    #[Test]
    public function it_can_be_created_with_custom_values(): void
    {
        $selection = new AuthenticatorSelection(
            authenticatorAttachment: AuthenticatorAttachment::PLATFORM,
            requireResidentKey: true,
            userVerification: UserVerificationRequirement::REQUIRED
        );

        $this->assertSame(AuthenticatorAttachment::PLATFORM, $selection->authenticatorAttachment);
        $this->assertTrue($selection->requireResidentKey);
        $this->assertSame(UserVerificationRequirement::REQUIRED, $selection->userVerification);
    }

    #[Test]
    public function it_creates_platform_selection(): void
    {
        $selection = AuthenticatorSelection::platform();

        $this->assertSame(AuthenticatorAttachment::PLATFORM, $selection->authenticatorAttachment);
        $this->assertTrue($selection->requireResidentKey);
        $this->assertSame(UserVerificationRequirement::REQUIRED, $selection->userVerification);
    }

    #[Test]
    public function it_creates_platform_selection_with_custom_values(): void
    {
        $selection = AuthenticatorSelection::platform(
            requireResidentKey: false,
            userVerification: UserVerificationRequirement::PREFERRED
        );

        $this->assertSame(AuthenticatorAttachment::PLATFORM, $selection->authenticatorAttachment);
        $this->assertFalse($selection->requireResidentKey);
        $this->assertSame(UserVerificationRequirement::PREFERRED, $selection->userVerification);
    }

    #[Test]
    public function it_creates_cross_platform_selection(): void
    {
        $selection = AuthenticatorSelection::crossPlatform();

        $this->assertSame(AuthenticatorAttachment::CROSS_PLATFORM, $selection->authenticatorAttachment);
        $this->assertFalse($selection->requireResidentKey);
        $this->assertSame(UserVerificationRequirement::PREFERRED, $selection->userVerification);
    }

    #[Test]
    public function it_creates_cross_platform_selection_with_custom_values(): void
    {
        $selection = AuthenticatorSelection::crossPlatform(
            requireResidentKey: true,
            userVerification: UserVerificationRequirement::REQUIRED
        );

        $this->assertSame(AuthenticatorAttachment::CROSS_PLATFORM, $selection->authenticatorAttachment);
        $this->assertTrue($selection->requireResidentKey);
        $this->assertSame(UserVerificationRequirement::REQUIRED, $selection->userVerification);
    }

    #[Test]
    public function it_creates_any_selection(): void
    {
        $selection = AuthenticatorSelection::any();

        $this->assertNull($selection->authenticatorAttachment);
        $this->assertFalse($selection->requireResidentKey);
        $this->assertSame(UserVerificationRequirement::PREFERRED, $selection->userVerification);
    }

    #[Test]
    public function it_creates_any_selection_with_custom_values(): void
    {
        $selection = AuthenticatorSelection::any(
            requireResidentKey: true,
            userVerification: UserVerificationRequirement::REQUIRED
        );

        $this->assertNull($selection->authenticatorAttachment);
        $this->assertTrue($selection->requireResidentKey);
        $this->assertSame(UserVerificationRequirement::REQUIRED, $selection->userVerification);
    }

    #[Test]
    public function it_converts_to_array_with_all_fields(): void
    {
        $selection = AuthenticatorSelection::platform();

        $array = $selection->toArray();

        $this->assertSame([
            'authenticatorAttachment' => 'platform',
            'residentKey' => 'required',
            'requireResidentKey' => true,
            'userVerification' => 'required',
        ], $array);
    }

    #[Test]
    public function it_converts_to_array_without_authenticator_attachment(): void
    {
        $selection = AuthenticatorSelection::any();

        $array = $selection->toArray();

        $this->assertArrayNotHasKey('authenticatorAttachment', $array);
        $this->assertSame('preferred', $array['residentKey']);
        $this->assertFalse($array['requireResidentKey']);
        $this->assertSame('preferred', $array['userVerification']);
    }

    #[Test]
    public function it_uses_preferred_resident_key_when_not_required(): void
    {
        $selection = new AuthenticatorSelection(requireResidentKey: false);

        $array = $selection->toArray();

        $this->assertSame('preferred', $array['residentKey']);
        $this->assertFalse($array['requireResidentKey']);
    }

    #[Test]
    public function it_uses_required_resident_key_when_required(): void
    {
        $selection = new AuthenticatorSelection(requireResidentKey: true);

        $array = $selection->toArray();

        $this->assertSame('required', $array['residentKey']);
        $this->assertTrue($array['requireResidentKey']);
    }

    #[Test]
    public function it_checks_if_requires_passkey(): void
    {
        $withPasskey = new AuthenticatorSelection(requireResidentKey: true);
        $withoutPasskey = new AuthenticatorSelection(requireResidentKey: false);

        $this->assertTrue($withPasskey->requiresPasskey());
        $this->assertFalse($withoutPasskey->requiresPasskey());
    }

    #[Test]
    public function it_checks_if_allows_any_authenticator(): void
    {
        $anyAuth = AuthenticatorSelection::any();
        $platformOnly = AuthenticatorSelection::platform();

        $this->assertTrue($anyAuth->allowsAnyAuthenticator());
        $this->assertFalse($platformOnly->allowsAnyAuthenticator());
    }

    #[Test]
    public function it_checks_if_is_passwordless(): void
    {
        $passwordless = new AuthenticatorSelection(
            requireResidentKey: true,
            userVerification: UserVerificationRequirement::REQUIRED
        );

        $notPasswordless1 = new AuthenticatorSelection(
            requireResidentKey: false,
            userVerification: UserVerificationRequirement::REQUIRED
        );

        $notPasswordless2 = new AuthenticatorSelection(
            requireResidentKey: true,
            userVerification: UserVerificationRequirement::PREFERRED
        );

        $this->assertTrue($passwordless->isPasswordless());
        $this->assertFalse($notPasswordless1->isPasswordless());
        $this->assertFalse($notPasswordless2->isPasswordless());
    }

    #[Test]
    public function platform_factory_creates_passwordless_selection(): void
    {
        $selection = AuthenticatorSelection::platform();

        $this->assertTrue($selection->isPasswordless());
    }

    #[Test]
    public function cross_platform_factory_creates_non_passwordless_selection(): void
    {
        $selection = AuthenticatorSelection::crossPlatform();

        $this->assertFalse($selection->isPasswordless());
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $selection = AuthenticatorSelection::platform();

        $this->assertSame(AuthenticatorAttachment::PLATFORM, $selection->authenticatorAttachment);
        $this->assertTrue($selection->requireResidentKey);
    }
}

<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Tests\ValueObjects;

use InvalidArgumentException;
use Nexus\Domain\Identity\ValueObjects\AttestationConveyancePreference;
use Nexus\Domain\Identity\ValueObjects\AuthenticatorAttachment;
use Nexus\Domain\Identity\ValueObjects\AuthenticatorSelection;
use Nexus\Domain\Identity\ValueObjects\PublicKeyCredentialDescriptor;
use Nexus\Domain\Identity\ValueObjects\UserVerificationRequirement;
use Nexus\Domain\Identity\ValueObjects\WebAuthnRegistrationOptions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(WebAuthnRegistrationOptions::class)]
final class WebAuthnRegistrationOptionsTest extends TestCase
{
    private function validChallenge(): string
    {
        return base64_encode(random_bytes(32)); // 32 bytes = 256 bits
    }

    #[Test]
    public function it_can_be_created_with_minimal_data(): void
    {
        $challenge = $this->validChallenge();
        
        $options = new WebAuthnRegistrationOptions(
            challenge: $challenge,
            rpId: 'example.com',
            rpName: 'Example Corp',
            userId: base64_encode('user-123'),
            userName: 'user@example.com',
            userDisplayName: 'John Doe',
            pubKeyCredParams: WebAuthnRegistrationOptions::defaultAlgorithms()
        );

        $this->assertSame($challenge, $options->challenge);
        $this->assertSame('example.com', $options->rpId);
        $this->assertSame('Example Corp', $options->rpName);
        $this->assertSame(60000, $options->timeout);
        $this->assertNull($options->authenticatorSelection);
        $this->assertSame(AttestationConveyancePreference::NONE, $options->attestation);
        $this->assertEmpty($options->excludeCredentials);
    }

    #[Test]
    public function it_throws_exception_for_empty_challenge(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Challenge cannot be empty');

        new WebAuthnRegistrationOptions(
            challenge: '',
            rpId: 'example.com',
            rpName: 'Example',
            userId: 'user-123',
            userName: 'user@example.com',
            userDisplayName: 'John',
            pubKeyCredParams: WebAuthnRegistrationOptions::defaultAlgorithms()
        );
    }

    #[Test]
    public function it_throws_exception_for_short_challenge(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Challenge must be at least 16 bytes');

        $shortChallenge = base64_encode(random_bytes(8)); // Only 8 bytes

        new WebAuthnRegistrationOptions(
            challenge: $shortChallenge,
            rpId: 'example.com',
            rpName: 'Example',
            userId: 'user-123',
            userName: 'user@example.com',
            userDisplayName: 'John',
            pubKeyCredParams: WebAuthnRegistrationOptions::defaultAlgorithms()
        );
    }

    #[Test]
    public function it_throws_exception_for_empty_rp_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Relying Party ID cannot be empty');

        new WebAuthnRegistrationOptions(
            challenge: $this->validChallenge(),
            rpId: '',
            rpName: 'Example',
            userId: 'user-123',
            userName: 'user@example.com',
            userDisplayName: 'John',
            pubKeyCredParams: WebAuthnRegistrationOptions::defaultAlgorithms()
        );
    }

    #[Test]
    public function it_throws_exception_for_empty_rp_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Relying Party name cannot be empty');

        new WebAuthnRegistrationOptions(
            challenge: $this->validChallenge(),
            rpId: 'example.com',
            rpName: '',
            userId: 'user-123',
            userName: 'user@example.com',
            userDisplayName: 'John',
            pubKeyCredParams: WebAuthnRegistrationOptions::defaultAlgorithms()
        );
    }

    #[Test]
    public function it_throws_exception_for_empty_user_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID cannot be empty');

        new WebAuthnRegistrationOptions(
            challenge: $this->validChallenge(),
            rpId: 'example.com',
            rpName: 'Example',
            userId: '',
            userName: 'user@example.com',
            userDisplayName: 'John',
            pubKeyCredParams: WebAuthnRegistrationOptions::defaultAlgorithms()
        );
    }

    #[Test]
    public function it_throws_exception_for_empty_user_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User name cannot be empty');

        new WebAuthnRegistrationOptions(
            challenge: $this->validChallenge(),
            rpId: 'example.com',
            rpName: 'Example',
            userId: 'user-123',
            userName: '',
            userDisplayName: 'John',
            pubKeyCredParams: WebAuthnRegistrationOptions::defaultAlgorithms()
        );
    }

    #[Test]
    public function it_throws_exception_for_empty_user_display_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User display name cannot be empty');

        new WebAuthnRegistrationOptions(
            challenge: $this->validChallenge(),
            rpId: 'example.com',
            rpName: 'Example',
            userId: 'user-123',
            userName: 'user@example.com',
            userDisplayName: '',
            pubKeyCredParams: WebAuthnRegistrationOptions::defaultAlgorithms()
        );
    }

    #[Test]
    public function it_throws_exception_for_empty_pubkey_cred_params(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one public key credential parameter is required');

        new WebAuthnRegistrationOptions(
            challenge: $this->validChallenge(),
            rpId: 'example.com',
            rpName: 'Example',
            userId: 'user-123',
            userName: 'user@example.com',
            userDisplayName: 'John',
            pubKeyCredParams: []
        );
    }

    #[Test]
    public function it_throws_exception_for_invalid_pubkey_cred_param(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Public key credential parameter must have alg and type');

        new WebAuthnRegistrationOptions(
            challenge: $this->validChallenge(),
            rpId: 'example.com',
            rpName: 'Example',
            userId: 'user-123',
            userName: 'user@example.com',
            userDisplayName: 'John',
            pubKeyCredParams: [['alg' => -7]] // Missing 'type'
        );
    }

    #[Test]
    public function it_throws_exception_for_too_short_timeout(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Timeout must be at least 30000ms (30 seconds)');

        new WebAuthnRegistrationOptions(
            challenge: $this->validChallenge(),
            rpId: 'example.com',
            rpName: 'Example',
            userId: 'user-123',
            userName: 'user@example.com',
            userDisplayName: 'John',
            pubKeyCredParams: WebAuthnRegistrationOptions::defaultAlgorithms(),
            timeout: 15000
        );
    }

    #[Test]
    public function it_throws_exception_for_too_long_timeout(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Timeout must not exceed 600000ms (10 minutes)');

        new WebAuthnRegistrationOptions(
            challenge: $this->validChallenge(),
            rpId: 'example.com',
            rpName: 'Example',
            userId: 'user-123',
            userName: 'user@example.com',
            userDisplayName: 'John',
            pubKeyCredParams: WebAuthnRegistrationOptions::defaultAlgorithms(),
            timeout: 700000
        );
    }

    #[Test]
    public function it_throws_exception_for_invalid_exclude_credentials(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Exclude credentials must be PublicKeyCredentialDescriptor instances');

        new WebAuthnRegistrationOptions(
            challenge: $this->validChallenge(),
            rpId: 'example.com',
            rpName: 'Example',
            userId: 'user-123',
            userName: 'user@example.com',
            userDisplayName: 'John',
            pubKeyCredParams: WebAuthnRegistrationOptions::defaultAlgorithms(),
            excludeCredentials: ['invalid']
        );
    }

    #[Test]
    public function it_can_be_created_via_factory_method(): void
    {
        $challenge = $this->validChallenge();
        
        $options = WebAuthnRegistrationOptions::create(
            challenge: $challenge,
            rpId: 'example.com',
            rpName: 'Example Corp',
            userId: base64_encode('user-123'),
            userName: 'user@example.com',
            userDisplayName: 'John Doe'
        );

        $this->assertSame($challenge, $options->challenge);
        $this->assertSame('example.com', $options->rpId);
        $this->assertCount(3, $options->pubKeyCredParams);
    }

    #[Test]
    public function default_algorithms_includes_es256_rs256_eddsa(): void
    {
        $algorithms = WebAuthnRegistrationOptions::defaultAlgorithms();

        $this->assertCount(3, $algorithms);
        $this->assertSame(-7, $algorithms[0]['alg']); // ES256
        $this->assertSame(-257, $algorithms[1]['alg']); // RS256
        $this->assertSame(-8, $algorithms[2]['alg']); // EdDSA
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $challenge = $this->validChallenge();
        $selection = AuthenticatorSelection::platform();
        
        $options = new WebAuthnRegistrationOptions(
            challenge: $challenge,
            rpId: 'example.com',
            rpName: 'Example Corp',
            userId: base64_encode('user-123'),
            userName: 'user@example.com',
            userDisplayName: 'John Doe',
            pubKeyCredParams: WebAuthnRegistrationOptions::defaultAlgorithms(),
            authenticatorSelection: $selection,
            attestation: AttestationConveyancePreference::DIRECT
        );

        $array = $options->toArray();

        $this->assertSame($challenge, $array['challenge']);
        $this->assertSame('example.com', $array['rp']['id']);
        $this->assertSame('Example Corp', $array['rp']['name']);
        $this->assertSame('user@example.com', $array['user']['name']);
        $this->assertSame('John Doe', $array['user']['displayName']);
        $this->assertCount(3, $array['pubKeyCredParams']);
        $this->assertSame(60000, $array['timeout']);
        $this->assertSame('direct', $array['attestation']);
        $this->assertIsArray($array['authenticatorSelection']);
    }

    #[Test]
    public function it_converts_to_array_with_exclude_credentials(): void
    {
        $credential = PublicKeyCredentialDescriptor::create('cred-123', ['usb']);
        
        $options = new WebAuthnRegistrationOptions(
            challenge: $this->validChallenge(),
            rpId: 'example.com',
            rpName: 'Example',
            userId: 'user-123',
            userName: 'user@example.com',
            userDisplayName: 'John',
            pubKeyCredParams: WebAuthnRegistrationOptions::defaultAlgorithms(),
            excludeCredentials: [$credential]
        );

        $array = $options->toArray();

        $this->assertArrayHasKey('excludeCredentials', $array);
        $this->assertCount(1, $array['excludeCredentials']);
        $this->assertSame('cred-123', $array['excludeCredentials'][0]['id']);
    }

    #[Test]
    public function it_checks_if_is_passwordless(): void
    {
        $passwordless = new WebAuthnRegistrationOptions(
            challenge: $this->validChallenge(),
            rpId: 'example.com',
            rpName: 'Example',
            userId: 'user-123',
            userName: 'user@example.com',
            userDisplayName: 'John',
            pubKeyCredParams: WebAuthnRegistrationOptions::defaultAlgorithms(),
            authenticatorSelection: AuthenticatorSelection::platform()
        );

        $notPasswordless = new WebAuthnRegistrationOptions(
            challenge: $this->validChallenge(),
            rpId: 'example.com',
            rpName: 'Example',
            userId: 'user-123',
            userName: 'user@example.com',
            userDisplayName: 'John',
            pubKeyCredParams: WebAuthnRegistrationOptions::defaultAlgorithms(),
            authenticatorSelection: AuthenticatorSelection::crossPlatform()
        );

        $this->assertTrue($passwordless->isPasswordless());
        $this->assertFalse($notPasswordless->isPasswordless());
    }

    #[Test]
    public function it_checks_if_requires_platform_authenticator(): void
    {
        $platform = new WebAuthnRegistrationOptions(
            challenge: $this->validChallenge(),
            rpId: 'example.com',
            rpName: 'Example',
            userId: 'user-123',
            userName: 'user@example.com',
            userDisplayName: 'John',
            pubKeyCredParams: WebAuthnRegistrationOptions::defaultAlgorithms(),
            authenticatorSelection: AuthenticatorSelection::platform()
        );

        $crossPlatform = new WebAuthnRegistrationOptions(
            challenge: $this->validChallenge(),
            rpId: 'example.com',
            rpName: 'Example',
            userId: 'user-123',
            userName: 'user@example.com',
            userDisplayName: 'John',
            pubKeyCredParams: WebAuthnRegistrationOptions::defaultAlgorithms(),
            authenticatorSelection: AuthenticatorSelection::crossPlatform()
        );

        $this->assertTrue($platform->requiresPlatformAuthenticator());
        $this->assertFalse($crossPlatform->requiresPlatformAuthenticator());
    }

    #[Test]
    public function it_checks_if_requires_attestation_validation(): void
    {
        $withAttestation = new WebAuthnRegistrationOptions(
            challenge: $this->validChallenge(),
            rpId: 'example.com',
            rpName: 'Example',
            userId: 'user-123',
            userName: 'user@example.com',
            userDisplayName: 'John',
            pubKeyCredParams: WebAuthnRegistrationOptions::defaultAlgorithms(),
            attestation: AttestationConveyancePreference::DIRECT
        );

        $withoutAttestation = new WebAuthnRegistrationOptions(
            challenge: $this->validChallenge(),
            rpId: 'example.com',
            rpName: 'Example',
            userId: 'user-123',
            userName: 'user@example.com',
            userDisplayName: 'John',
            pubKeyCredParams: WebAuthnRegistrationOptions::defaultAlgorithms(),
            attestation: AttestationConveyancePreference::NONE
        );

        $this->assertTrue($withAttestation->requiresAttestationValidation());
        $this->assertFalse($withoutAttestation->requiresAttestationValidation());
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $options = WebAuthnRegistrationOptions::create(
            challenge: $this->validChallenge(),
            rpId: 'example.com',
            rpName: 'Example',
            userId: 'user-123',
            userName: 'user@example.com',
            userDisplayName: 'John'
        );

        $this->assertSame('example.com', $options->rpId);
        $this->assertSame('Example', $options->rpName);
    }
}

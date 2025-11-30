<?php

declare(strict_types=1);

namespace Nexus\Identity\Tests\Services;

use Nexus\Identity\Services\WebAuthnManager;
use Nexus\Identity\ValueObjects\UserVerificationRequirement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(WebAuthnManager::class)]
final class WebAuthnManagerTest extends TestCase
{
    private WebAuthnManager $manager;

    protected function setUp(): void
    {
        $this->manager = new WebAuthnManager(
            rpId: 'example.com',
            rpName: 'Example Corp'
        );
    }

    #[Test]
    public function it_generates_registration_options_with_default_settings(): void
    {
        $options = $this->manager->generateRegistrationOptions(
            userId: 'user-123',
            userName: 'john@example.com',
            userDisplayName: 'John Doe'
        );

        $this->assertNotEmpty($options->challenge);
        $this->assertSame('example.com', $options->rpId);
        $this->assertSame('Example Corp', $options->rpName);
        $this->assertSame('john@example.com', $options->userName);
        $this->assertSame('John Doe', $options->userDisplayName);
        $this->assertCount(3, $options->pubKeyCredParams); // ES256, RS256, EdDSA
        $this->assertEmpty($options->excludeCredentials);
        $this->assertFalse($options->isPasswordless());
    }

    #[Test]
    public function it_generates_registration_options_for_platform_authenticator(): void
    {
        $options = $this->manager->generateRegistrationOptions(
            userId: 'user-123',
            userName: 'john@example.com',
            userDisplayName: 'John Doe',
            requirePlatformAuthenticator: true
        );

        $this->assertTrue($options->requiresPlatformAuthenticator());
        $this->assertFalse($options->isPasswordless()); // Resident key not required
    }

    #[Test]
    public function it_generates_registration_options_for_passwordless(): void
    {
        $options = $this->manager->generateRegistrationOptions(
            userId: 'user-123',
            userName: 'john@example.com',
            userDisplayName: 'John Doe',
            requireResidentKey: true,
            requirePlatformAuthenticator: true
        );

        $this->assertTrue($options->isPasswordless());
        $this->assertTrue($options->requiresPlatformAuthenticator());
    }

    #[Test]
    public function it_generates_registration_options_with_excluded_credentials(): void
    {
        $options = $this->manager->generateRegistrationOptions(
            userId: 'user-123',
            userName: 'john@example.com',
            userDisplayName: 'John Doe',
            excludeCredentialIds: ['cred-1', 'cred-2']
        );

        $this->assertCount(2, $options->excludeCredentials);
    }

    #[Test]
    public function it_generates_unique_challenges_for_each_registration(): void
    {
        $options1 = $this->manager->generateRegistrationOptions(
            userId: 'user-123',
            userName: 'john@example.com',
            userDisplayName: 'John Doe'
        );

        $options2 = $this->manager->generateRegistrationOptions(
            userId: 'user-123',
            userName: 'john@example.com',
            userDisplayName: 'John Doe'
        );

        $this->assertNotSame($options1->challenge, $options2->challenge);
    }

    #[Test]
    public function it_generates_authentication_options_for_specific_user(): void
    {
        $options = $this->manager->generateAuthenticationOptions(
            allowCredentialIds: ['cred-1', 'cred-2']
        );

        $this->assertNotEmpty($options->challenge);
        $this->assertSame('example.com', $options->rpId);
        $this->assertCount(2, $options->allowCredentials);
        $this->assertFalse($options->isUsernameless());
        $this->assertSame(UserVerificationRequirement::PREFERRED, $options->userVerification);
    }

    #[Test]
    public function it_generates_authentication_options_for_usernameless(): void
    {
        $options = $this->manager->generateAuthenticationOptions(
            allowCredentialIds: []
        );

        $this->assertNotEmpty($options->challenge);
        $this->assertTrue($options->isUsernameless());
        $this->assertEmpty($options->allowCredentials);
    }

    #[Test]
    public function it_generates_authentication_options_with_required_user_verification(): void
    {
        $options = $this->manager->generateAuthenticationOptions(
            allowCredentialIds: ['cred-1'],
            requireUserVerification: true
        );

        $this->assertTrue($options->requiresUserVerification());
        $this->assertSame(UserVerificationRequirement::REQUIRED, $options->userVerification);
    }

    #[Test]
    public function it_generates_unique_challenges_for_each_authentication(): void
    {
        $options1 = $this->manager->generateAuthenticationOptions(
            allowCredentialIds: ['cred-1']
        );

        $options2 = $this->manager->generateAuthenticationOptions(
            allowCredentialIds: ['cred-1']
        );

        $this->assertNotSame($options1->challenge, $options2->challenge);
    }

    #[Test]
    public function registration_options_use_base64_encoded_user_id(): void
    {
        $options = $this->manager->generateRegistrationOptions(
            userId: 'user-123',
            userName: 'john@example.com',
            userDisplayName: 'John Doe'
        );

        // Verify userId is base64-encoded
        $decoded = base64_decode($options->userId, true);
        $this->assertNotFalse($decoded);
        $this->assertSame('user-123', $decoded);
    }

    #[Test]
    public function registration_options_have_valid_challenge_length(): void
    {
        $options = $this->manager->generateRegistrationOptions(
            userId: 'user-123',
            userName: 'john@example.com',
            userDisplayName: 'John Doe'
        );

        // Challenge should be at least 16 bytes (128 bits)
        $decoded = base64_decode($options->challenge, true);
        $this->assertGreaterThanOrEqual(16, strlen($decoded));
    }

    #[Test]
    public function authentication_options_have_valid_challenge_length(): void
    {
        $options = $this->manager->generateAuthenticationOptions();

        $decoded = base64_decode($options->challenge, true);
        $this->assertGreaterThanOrEqual(16, strlen($decoded));
    }

    #[Test]
    public function registration_options_convert_to_valid_array_format(): void
    {
        $options = $this->manager->generateRegistrationOptions(
            userId: 'user-123',
            userName: 'john@example.com',
            userDisplayName: 'John Doe',
            requirePlatformAuthenticator: true
        );

        $array = $options->toArray();

        $this->assertArrayHasKey('challenge', $array);
        $this->assertArrayHasKey('rp', $array);
        $this->assertArrayHasKey('user', $array);
        $this->assertArrayHasKey('pubKeyCredParams', $array);
        $this->assertArrayHasKey('timeout', $array);
        $this->assertArrayHasKey('attestation', $array);
        $this->assertArrayHasKey('authenticatorSelection', $array);
    }

    #[Test]
    public function authentication_options_convert_to_valid_array_format(): void
    {
        $options = $this->manager->generateAuthenticationOptions(
            allowCredentialIds: ['cred-1']
        );

        $array = $options->toArray();

        $this->assertArrayHasKey('challenge', $array);
        $this->assertArrayHasKey('timeout', $array);
        $this->assertArrayHasKey('userVerification', $array);
        $this->assertArrayHasKey('rpId', $array);
        $this->assertArrayHasKey('allowCredentials', $array);
    }
}

<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Tests\ValueObjects;

use InvalidArgumentException;
use Nexus\Domain\Identity\ValueObjects\PublicKeyCredentialDescriptor;
use Nexus\Domain\Identity\ValueObjects\UserVerificationRequirement;
use Nexus\Domain\Identity\ValueObjects\WebAuthnAuthenticationOptions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(WebAuthnAuthenticationOptions::class)]
final class WebAuthnAuthenticationOptionsTest extends TestCase
{
    private function validChallenge(): string
    {
        return base64_encode(random_bytes(32));
    }

    #[Test]
    public function it_can_be_created_with_minimal_data(): void
    {
        $challenge = $this->validChallenge();
        
        $options = new WebAuthnAuthenticationOptions(
            challenge: $challenge
        );

        $this->assertSame($challenge, $options->challenge);
        $this->assertSame(60000, $options->timeout);
        $this->assertNull($options->rpId);
        $this->assertEmpty($options->allowCredentials);
        $this->assertSame(UserVerificationRequirement::PREFERRED, $options->userVerification);
    }

    #[Test]
    public function it_throws_exception_for_empty_challenge(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Challenge cannot be empty');

        new WebAuthnAuthenticationOptions(challenge: '');
    }

    #[Test]
    public function it_throws_exception_for_short_challenge(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Challenge must be at least 16 bytes');

        $shortChallenge = base64_encode(random_bytes(8));

        new WebAuthnAuthenticationOptions(challenge: $shortChallenge);
    }

    #[Test]
    public function it_throws_exception_for_too_short_timeout(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Timeout must be at least 30000ms (30 seconds)');

        new WebAuthnAuthenticationOptions(
            challenge: $this->validChallenge(),
            timeout: 15000
        );
    }

    #[Test]
    public function it_throws_exception_for_too_long_timeout(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Timeout must not exceed 600000ms (10 minutes)');

        new WebAuthnAuthenticationOptions(
            challenge: $this->validChallenge(),
            timeout: 700000
        );
    }

    #[Test]
    public function it_throws_exception_for_invalid_allow_credentials(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Allow credentials must be PublicKeyCredentialDescriptor instances');

        new WebAuthnAuthenticationOptions(
            challenge: $this->validChallenge(),
            allowCredentials: ['invalid']
        );
    }

    #[Test]
    public function it_can_be_created_via_for_user_factory(): void
    {
        $challenge = $this->validChallenge();
        $credentials = [
            PublicKeyCredentialDescriptor::create('cred-1'),
            PublicKeyCredentialDescriptor::create('cred-2'),
        ];
        
        $options = WebAuthnAuthenticationOptions::forUser(
            challenge: $challenge,
            allowCredentials: $credentials
        );

        $this->assertSame($challenge, $options->challenge);
        $this->assertCount(2, $options->allowCredentials);
    }

    #[Test]
    public function for_user_factory_throws_exception_for_empty_credentials(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Allow credentials cannot be empty for user authentication');

        WebAuthnAuthenticationOptions::forUser(
            challenge: $this->validChallenge(),
            allowCredentials: []
        );
    }

    #[Test]
    public function it_can_be_created_via_usernameless_factory(): void
    {
        $challenge = $this->validChallenge();
        
        $options = WebAuthnAuthenticationOptions::usernameless(
            challenge: $challenge,
            rpId: 'example.com'
        );

        $this->assertSame($challenge, $options->challenge);
        $this->assertSame('example.com', $options->rpId);
        $this->assertEmpty($options->allowCredentials);
        $this->assertSame(UserVerificationRequirement::REQUIRED, $options->userVerification);
    }

    #[Test]
    public function it_converts_to_array_minimal(): void
    {
        $challenge = $this->validChallenge();
        
        $options = new WebAuthnAuthenticationOptions(
            challenge: $challenge
        );

        $array = $options->toArray();

        $this->assertSame($challenge, $array['challenge']);
        $this->assertSame(60000, $array['timeout']);
        $this->assertSame('preferred', $array['userVerification']);
        $this->assertArrayNotHasKey('rpId', $array);
        $this->assertArrayNotHasKey('allowCredentials', $array);
    }

    #[Test]
    public function it_converts_to_array_with_rp_id(): void
    {
        $options = new WebAuthnAuthenticationOptions(
            challenge: $this->validChallenge(),
            rpId: 'example.com'
        );

        $array = $options->toArray();

        $this->assertSame('example.com', $array['rpId']);
    }

    #[Test]
    public function it_converts_to_array_with_allow_credentials(): void
    {
        $credentials = [
            PublicKeyCredentialDescriptor::create('cred-1', ['usb']),
            PublicKeyCredentialDescriptor::create('cred-2', ['nfc']),
        ];
        
        $options = new WebAuthnAuthenticationOptions(
            challenge: $this->validChallenge(),
            allowCredentials: $credentials
        );

        $array = $options->toArray();

        $this->assertArrayHasKey('allowCredentials', $array);
        $this->assertCount(2, $array['allowCredentials']);
        $this->assertSame('cred-1', $array['allowCredentials'][0]['id']);
        $this->assertSame('cred-2', $array['allowCredentials'][1]['id']);
    }

    #[Test]
    public function it_checks_if_is_usernameless(): void
    {
        $usernameless = new WebAuthnAuthenticationOptions(
            challenge: $this->validChallenge(),
            allowCredentials: []
        );

        $notUsernameless = WebAuthnAuthenticationOptions::forUser(
            challenge: $this->validChallenge(),
            allowCredentials: [PublicKeyCredentialDescriptor::create('cred-1')]
        );

        $this->assertTrue($usernameless->isUsernameless());
        $this->assertFalse($notUsernameless->isUsernameless());
    }

    #[Test]
    public function it_checks_if_requires_user_verification(): void
    {
        $required = new WebAuthnAuthenticationOptions(
            challenge: $this->validChallenge(),
            userVerification: UserVerificationRequirement::REQUIRED
        );

        $preferred = new WebAuthnAuthenticationOptions(
            challenge: $this->validChallenge(),
            userVerification: UserVerificationRequirement::PREFERRED
        );

        $this->assertTrue($required->requiresUserVerification());
        $this->assertFalse($preferred->requiresUserVerification());
    }

    #[Test]
    public function it_gets_allowed_credential_count(): void
    {
        $noCredentials = new WebAuthnAuthenticationOptions(
            challenge: $this->validChallenge()
        );

        $twoCredentials = WebAuthnAuthenticationOptions::forUser(
            challenge: $this->validChallenge(),
            allowCredentials: [
                PublicKeyCredentialDescriptor::create('cred-1'),
                PublicKeyCredentialDescriptor::create('cred-2'),
            ]
        );

        $this->assertSame(0, $noCredentials->getAllowedCredentialCount());
        $this->assertSame(2, $twoCredentials->getAllowedCredentialCount());
    }

    #[Test]
    public function usernameless_factory_requires_user_verification(): void
    {
        $options = WebAuthnAuthenticationOptions::usernameless(
            challenge: $this->validChallenge()
        );

        $this->assertTrue($options->requiresUserVerification());
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $options = new WebAuthnAuthenticationOptions(
            challenge: $this->validChallenge(),
            rpId: 'example.com'
        );

        $this->assertSame('example.com', $options->rpId);
    }
}

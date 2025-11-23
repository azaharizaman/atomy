<?php

declare(strict_types=1);

namespace Nexus\Identity\Services;

use Nexus\Identity\Contracts\WebAuthnManagerInterface;
use Nexus\Identity\Exceptions\SignCountRollbackException;
use Nexus\Identity\Exceptions\WebAuthnVerificationException;
use Nexus\Identity\ValueObjects\AttestationConveyancePreference;
use Nexus\Identity\ValueObjects\AuthenticatorSelection;
use Nexus\Identity\ValueObjects\PublicKeyCredentialDescriptor;
use Nexus\Identity\ValueObjects\UserVerificationRequirement;
use Nexus\Identity\ValueObjects\WebAuthnAuthenticationOptions;
use Nexus\Identity\ValueObjects\WebAuthnCredential;
use Nexus\Identity\ValueObjects\WebAuthnRegistrationOptions;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * WebAuthn Manager
 *
 * Handles WebAuthn/FIDO2 registration and authentication using web-auth/webauthn-lib.
 */
final readonly class WebAuthnManager implements WebAuthnManagerInterface
{
    private AttestationStatementSupportManager $attestationSupportManager;
    private AttestationObjectLoader $attestationObjectLoader;
    private PublicKeyCredentialLoader $publicKeyCredentialLoader;
    private AuthenticatorAttestationResponseValidator $attestationValidator;
    private AuthenticatorAssertionResponseValidator $assertionValidator;

    public function __construct(
        private string $rpId,
        private string $rpName,
        private string $rpIcon = ''
    ) {
        // Initialize attestation support (none format for privacy)
        $this->attestationSupportManager = AttestationStatementSupportManager::create();
        $this->attestationSupportManager->add(NoneAttestationStatementSupport::create());

        // Initialize attestation object loader
        $this->attestationObjectLoader = AttestationObjectLoader::create(
            $this->attestationSupportManager
        );

        // Initialize public key credential loader
        $this->publicKeyCredentialLoader = PublicKeyCredentialLoader::create(
            $this->attestationObjectLoader
        );

        // Initialize validators
        $extensionChecker = ExtensionOutputCheckerHandler::create();
        
        $this->attestationValidator = AuthenticatorAttestationResponseValidator::create(
            $this->attestationSupportManager,
            null, // Public key credential source repository (not needed for single verification)
            null, // Token binding handler
            $extensionChecker
        );

        $this->assertionValidator = AuthenticatorAssertionResponseValidator::create(
            null, // Public key credential source repository
            null, // Token binding handler
            $extensionChecker
        );
    }

    public function generateRegistrationOptions(
        string $userId,
        string $userName,
        string $userDisplayName,
        array $excludeCredentialIds = [],
        bool $requireResidentKey = false,
        bool $requirePlatformAuthenticator = false
    ): WebAuthnRegistrationOptions {
        // Generate cryptographic challenge (32 bytes = 256 bits)
        $challenge = base64_encode(random_bytes(32));

        // Determine authenticator selection
        $authenticatorSelection = match (true) {
            $requirePlatformAuthenticator => AuthenticatorSelection::platform(
                requireResidentKey: $requireResidentKey
            ),
            $requireResidentKey => AuthenticatorSelection::any(
                requireResidentKey: true,
                userVerification: UserVerificationRequirement::REQUIRED
            ),
            default => AuthenticatorSelection::any()
        };

        // Build exclude credentials
        $excludeCredentials = array_map(
            fn(string $credId) => PublicKeyCredentialDescriptor::create($credId),
            $excludeCredentialIds
        );

        return new WebAuthnRegistrationOptions(
            challenge: $challenge,
            rpId: $this->rpId,
            rpName: $this->rpName,
            userId: base64_encode($userId),
            userName: $userName,
            userDisplayName: $userDisplayName,
            pubKeyCredParams: WebAuthnRegistrationOptions::defaultAlgorithms(),
            authenticatorSelection: $authenticatorSelection,
            excludeCredentials: $excludeCredentials
        );
    }

    public function verifyRegistration(
        string $credentialJson,
        string $expectedChallenge,
        string $expectedOrigin
    ): WebAuthnCredential {
        try {
            // Load and parse the credential
            $publicKeyCredential = $this->publicKeyCredentialLoader->load($credentialJson);
            
            $response = $publicKeyCredential->response;
            if (!$response instanceof AuthenticatorAttestationResponse) {
                throw WebAuthnVerificationException::invalidCredentialFormat(
                    'Response is not an AuthenticatorAttestationResponse'
                );
            }

            // Create RP entity for verification
            $rpEntity = PublicKeyCredentialRpEntity::create(
                $this->rpName,
                $this->rpId,
                $this->rpIcon
            );

            // Verify the attestation response
            $publicKeyCredentialSource = $this->attestationValidator->check(
                $response,
                $publicKeyCredential->rawId,
                $expectedChallenge,
                $rpEntity,
                [$expectedOrigin]
            );

            // Extract credential data
            return new WebAuthnCredential(
                credentialId: base64_encode($publicKeyCredential->rawId),
                publicKey: base64_encode($publicKeyCredentialSource->publicKey),
                signCount: $publicKeyCredentialSource->counter,
                aaguid: bin2hex($publicKeyCredentialSource->aaguid),
                transports: $response->transports
            );
        } catch (WebAuthnVerificationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw WebAuthnVerificationException::invalidCredentialFormat($e->getMessage());
        }
    }

    public function generateAuthenticationOptions(
        array $allowCredentialIds = [],
        bool $requireUserVerification = false
    ): WebAuthnAuthenticationOptions {
        // Generate cryptographic challenge
        $challenge = base64_encode(random_bytes(32));

        $userVerification = $requireUserVerification
            ? UserVerificationRequirement::REQUIRED
            : UserVerificationRequirement::PREFERRED;

        // Usernameless authentication (empty allowCredentials)
        if (empty($allowCredentialIds)) {
            return WebAuthnAuthenticationOptions::usernameless(
                challenge: $challenge,
                rpId: $this->rpId,
                userVerification: $userVerification
            );
        }

        // User-specific authentication
        $allowCredentials = array_map(
            fn(string $credId) => PublicKeyCredentialDescriptor::create($credId),
            $allowCredentialIds
        );

        return WebAuthnAuthenticationOptions::forUser(
            challenge: $challenge,
            allowCredentials: $allowCredentials,
            rpId: $this->rpId,
            userVerification: $userVerification
        );
    }

    public function verifyAuthentication(
        string $assertionJson,
        string $expectedChallenge,
        string $expectedOrigin,
        WebAuthnCredential $storedCredential
    ): array {
        try {
            // Load and parse the assertion
            $publicKeyCredential = $this->publicKeyCredentialLoader->load($assertionJson);
            
            $response = $publicKeyCredential->response;
            if (!$response instanceof AuthenticatorAssertionResponse) {
                throw WebAuthnVerificationException::invalidCredentialFormat(
                    'Response is not an AuthenticatorAssertionResponse'
                );
            }

            // Create credential source from stored credential
            $credentialSource = PublicKeyCredentialSource::create(
                publicKeyCredentialId: base64_decode($storedCredential->credentialId),
                type: 'public-key',
                transports: $storedCredential->transports,
                attestationType: 'none',
                trustPath: [],
                aaguid: hex2bin($storedCredential->aaguid),
                credentialPublicKey: base64_decode($storedCredential->publicKey),
                userHandle: '',
                counter: $storedCredential->signCount
            );

            // Create request options for verification
            $requestOptions = PublicKeyCredentialRequestOptions::create(
                $expectedChallenge
            )->allowCredential(
                \Webauthn\PublicKeyCredentialDescriptor::create(
                    'public-key',
                    base64_decode($storedCredential->credentialId)
                )
            );

            // Verify the assertion
            $updatedSource = $this->assertionValidator->check(
                $credentialSource,
                $response,
                $requestOptions,
                $expectedOrigin,
                null // User handle (for usernameless, will be in response)
            );

            // Check for sign count rollback (cloning detection)
            if ($updatedSource->counter > 0 && $updatedSource->counter <= $storedCredential->signCount) {
                throw SignCountRollbackException::detected(
                    $storedCredential->signCount,
                    $updatedSource->counter
                );
            }

            return [
                'credentialId' => $storedCredential->credentialId,
                'newSignCount' => $updatedSource->counter,
                'userHandle' => $updatedSource->userHandle !== '' ? $updatedSource->userHandle : null,
            ];
        } catch (SignCountRollbackException $e) {
            throw $e;
        } catch (WebAuthnVerificationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw WebAuthnVerificationException::invalidSignature();
        }
    }
}

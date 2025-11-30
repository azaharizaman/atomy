<?php

declare(strict_types=1);

/**
 * Advanced Usage Example: Identity Package
 * 
 * Demonstrates:
 * 1. WebAuthn/Passkey registration
 * 2. Passwordless authentication
 * 3. Backup code generation and usage
 */

use Nexus\Domain\Identity\Services\{MfaEnrollmentService, MfaVerificationService};

// Example 1: WebAuthn Registration
$options = $mfaService->generateWebAuthnRegistrationOptions(
    userId: $userId,
    userName: 'user@example.com',
    userDisplayName: 'John Doe',
    requireResidentKey: true  // For passwordless
);

// Example 2: WebAuthn Authentication
$authOptions = $mfaVerificationService->generateWebAuthnAuthenticationOptions($userId);

// Example 3: Backup Codes
$backupCodeSet = $mfaService->generateBackupCodes($userId, count: 10);

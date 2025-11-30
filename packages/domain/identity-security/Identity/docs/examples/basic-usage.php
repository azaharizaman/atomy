<?php

declare(strict_types=1);

/**
 * Basic Usage Example: Identity Package
 * 
 * Demonstrates:
 * 1. User authentication (login)
 * 2. Permission checking
 * 3. TOTP MFA enrollment
 */

use Nexus\Identity\Services\{AuthenticationService, PermissionChecker, MfaEnrollmentService};
use Nexus\Identity\ValueObjects\Credentials;

// Example 1: User Login
$credentials = new Credentials('user@example.com', 'password123');
$result = $authService->authenticate($credentials);
// Returns: ['session_token' => 'abc...', 'user' => UserInterface]

// Example 2: Permission Check
$canCreate = $permissionChecker->can($userId, 'invoices.create');

// Example 3: TOTP MFA Enrollment
$totpData = $mfaService->enrollTotp($userId, 'Nexus ERP', 'user@example.com');
// Returns: ['qrCodeDataUrl' => 'data:image/png;base64,...', 'secret' => 'ABC...']

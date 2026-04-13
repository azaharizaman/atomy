# Atomy-Q Identity Gap 7 MFA and Audit Extension Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Finish the remaining Gap 7 alpha identity work by implementing the MFA verification bridge and persisting identity audit logs.

**Architecture:** Keep the already-completed RBAC/session/lockout implementation intact. Add a small app-local MFA challenge store to bridge `challenge_id` to the existing MFA verifier, and replace the no-op audit repository binding with a DB-backed Laravel adapter. The auth controller remains the public entry point; the orchestrator and adapters provide the token/session/audit mechanics behind it.

**Tech Stack:** PHP 8.3, Laravel 12, Nexus Identity, Nexus IdentityOperations, Nexus AuditLogger, Eloquent, PHPUnit.

**Execution status:** Completed (2026-04-08). This extension to `2026-04-07-alpha-progress-gap-7-identity-session-rbac-implementation-plan.md` closed the two remaining alpha gaps: MFA verify completion and persisted audit logging.

---

### Task 1: Add MFA challenge persistence and login handoff

**Files:**
- Create: `apps/atomy-q/API/database/migrations/2026_04_08_000006_create_identity_mfa_challenges_table.php`
- Create: `apps/atomy-q/API/app/Models/MfaChallenge.php`
- Create: `apps/atomy-q/API/app/Services/Identity/AtomyMfaChallengeStore.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/AuthController.php`
- Modify: `orchestrators/IdentityOperations/src/Services/UserAuthenticationService.php`
- Modify: `orchestrators/IdentityOperations/src/Contracts/UserAuthenticationCoordinatorInterface.php`
- Modify: `orchestrators/IdentityOperations/src/Coordinators/UserAuthenticationCoordinator.php`
- Modify: `apps/atomy-q/API/tests/Feature/Api/AuthTest.php`

- [ ] **Step 1: Add a failing MFA challenge flow test**

```php
public function test_login_returns_mfa_required_challenge_for_mfa_users(): void
{
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'mfa-user@example.com',
        'password' => 'correct-password',
    ]);

    $response->assertStatus(401);
    $response->assertJsonFragment(['message' => 'Multi-factor authentication required']);
    $this->assertNotNull($response->json('challenge_id'));
}
```

- [ ] **Step 2: Create the MFA challenge table**

```php
Schema::create('mfa_challenges', function (Blueprint $table): void {
    $table->string('id')->primary();
    $table->ulid('user_id')->index();
    $table->ulid('tenant_id')->index();
    $table->string('method');
    $table->timestamp('expires_at')->index();
    $table->timestamp('consumed_at')->nullable()->index();
    $table->integer('attempt_count')->default(0);
    $table->timestamps();
});
```

- [ ] **Step 3: Implement the MFA challenge store**

```php
final readonly class AtomyMfaChallengeStore
{
    public function create(string $userId, string $tenantId, string $method): string;
    public function find(string $challengeId): ?MfaChallenge;
    public function consume(string $challengeId): void;
    public function incrementAttempts(string $challengeId): void;
}
```

- [ ] **Step 4: Stop token issuance when MFA is required**

Update `UserAuthenticationService::authenticate()` so it checks the user’s MFA state before it generates access/refresh tokens or creates a session. If MFA is required, throw `Nexus\Identity\Exceptions\MfaRequiredException` and do not mint tokens yet.

- [ ] **Step 5: Return the MFA challenge from the controller**

Update `AuthController::login()` so it catches `MfaRequiredException`, creates a challenge through `AtomyMfaChallengeStore`, and returns:

```php
return response()->json([
    'message' => 'Multi-factor authentication required',
    'challenge_id' => $challengeId,
], 401);
```

- [ ] **Step 6: Wire the coordinator contract for final MFA completion**

Extend the authentication coordinator contract with a completion method that accepts the verified user context from the challenge store and returns the final session-backed `UserContext` with access and refresh tokens. Keep the existing login/logout methods intact so the current session revocation tests continue to pass.

- [ ] **Step 7: Implement the MFA verify endpoint**

Update `AuthController::mfaVerify()` to:
- load the challenge by `challenge_id`
- reject expired or consumed challenges
- call the MFA coordinator/service with the challenge user ID, method, and OTP
- consume the challenge on success
- call the new coordinator completion method to mint the final session-backed tokens and return the authenticated response

- [ ] **Step 8: Add regression coverage**

Extend `apps/atomy-q/API/tests/Feature/IdentityGap7Test.php` with:
- MFA-required login returns a challenge
- valid `mfaVerify` returns final tokens
- expired or consumed challenge fails
- wrong tenant challenge fails

- [ ] **Step 9: Commit**

```bash
git add apps/atomy-q/API/database/migrations/2026_04_08_000006_create_identity_mfa_challenges_table.php apps/atomy-q/API/app/Models/MfaChallenge.php apps/atomy-q/API/app/Services/Identity/AtomyMfaChallengeStore.php apps/atomy-q/API/app/Http/Controllers/Api/V1/AuthController.php orchestrators/IdentityOperations/src/Services/UserAuthenticationService.php orchestrators/IdentityOperations/src/Contracts/UserAuthenticationCoordinatorInterface.php orchestrators/IdentityOperations/src/Coordinators/UserAuthenticationCoordinator.php apps/atomy-q/API/tests/Feature/Api/AuthTest.php apps/atomy-q/API/tests/Feature/IdentityGap7Test.php
git commit -m "feat(identity): complete MFA login challenge flow"
```

### Task 2: Persist identity audit logs

**Files:**
- Create: `apps/atomy-q/API/database/migrations/2026_04_08_000007_create_audit_logs_table.php`
- Create: `apps/atomy-q/API/app/Models/AuditLog.php`
- Modify: `adapters/Laravel/AuditLogger/src/Adapters/AuditLogRepositoryAdapter.php`
- Modify: `apps/atomy-q/API/app/Providers/AppServiceProvider.php`
- Modify: `adapters/Laravel/AuditLogger/src/Providers/AuditLoggerAdapterServiceProvider.php`
- Modify: `apps/atomy-q/API/app/Services/Identity/AtomyNoopAuditLogRepository.php`
- Modify: `apps/atomy-q/API/tests/Feature/IdentityGap7Test.php`

- [ ] **Step 1: Add a failing persistence test**

```php
public function test_login_and_logout_write_audit_logs(): void
{
    $this->postJson('/api/v1/auth/login', [
        'email' => 'audit-user@example.com',
        'password' => 'correct-password',
    ])->assertOk();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'user.authenticated',
    ]);
}
```

- [ ] **Step 2: Create the audit log table**

```php
Schema::create('audit_logs', function (Blueprint $table): void {
    $table->ulid('id')->primary();
    $table->string('event')->index();
    $table->string('subject_type')->nullable()->index();
    $table->string('subject_id')->nullable()->index();
    $table->string('causer_type')->nullable()->index();
    $table->string('causer_id')->nullable()->index();
    $table->ulid('tenant_id')->nullable()->index();
    $table->integer('level')->default(1)->index();
    $table->string('batch_uuid')->nullable()->index();
    $table->json('properties');
    $table->timestamps();
});
```

- [ ] **Step 3: Implement the audit log model and adapter persistence**

Replace the current `throw new RuntimeException(...)` path in `AuditLogRepositoryAdapter::create()` with Eloquent persistence to the new `AuditLog` model and return the created record as an `AuditLogInterface`.

- [ ] **Step 4: Bind the persisted repository instead of the no-op repository**

Update `AppServiceProvider.php` so `AuditLogRepositoryInterface` resolves to the persisted adapter, not `AtomyNoopAuditLogRepository`.

- [ ] **Step 5: Remove the no-op audit sink from the runtime path**

Keep `AtomyNoopAuditLogRepository.php` only if it is still needed for tests or legacy compatibility. The runtime binding must not resolve to it after this task.

- [ ] **Step 6: Add audit event assertions**

Extend the identity integration tests so they assert persisted audit rows for:
- login success
- login failure
- MFA challenge issuance
- MFA verification success
- logout

- [ ] **Step 7: Commit**

```bash
git add apps/atomy-q/API/database/migrations/2026_04_08_000007_create_audit_logs_table.php apps/atomy-q/API/app/Models/AuditLog.php adapters/Laravel/AuditLogger/src/Adapters/AuditLogRepositoryAdapter.php adapters/Laravel/AuditLogger/src/Providers/AuditLoggerAdapterServiceProvider.php apps/atomy-q/API/app/Providers/AppServiceProvider.php apps/atomy-q/API/app/Services/Identity/AtomyNoopAuditLogRepository.php apps/atomy-q/API/tests/Feature/IdentityGap7Test.php
git commit -m "feat(identity): persist audit logs for gap 7"
```

### Task 3: Verify the extension and update docs

**Files:**
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- Modify: `docs/superpowers/plans/2026-04-07-alpha-progress-gap-7-identity-session-rbac-implementation-plan.md`
- Modify: `docs/superpowers/specs/2026-04-07-identity-gap-7-closure-design.md`

- [ ] **Step 1: Run the targeted tests**

Run:

```bash
php artisan test tests/Feature/Api/AuthTest.php tests/Feature/IdentityGap7Test.php --stop-on-failure
```

Expected: PASS, including MFA challenge coverage and persisted audit assertions.

- [ ] **Step 2: Update the implementation summary**

Document that the extension now covers MFA verification and persisted audit logging, and note any remaining follow-up only if the tests expose a real gap.

- [ ] **Step 3: Annotate the base plan and spec**

Add a short note that the original gap-7 plan was extended to cover the remaining MFA and audit work, so future readers know why the base plan stops at a partial Task 10.

- [ ] **Step 4: Commit**

```bash
git add apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md docs/superpowers/plans/2026-04-07-alpha-progress-gap-7-identity-session-rbac-implementation-plan.md docs/superpowers/specs/2026-04-07-identity-gap-7-closure-design.md
git commit -m "docs(identity): record gap 7 MFA and audit extension"
```

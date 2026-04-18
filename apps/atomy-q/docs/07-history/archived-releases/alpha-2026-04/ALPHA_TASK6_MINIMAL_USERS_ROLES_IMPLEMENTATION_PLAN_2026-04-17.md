# Alpha Task 6 Minimal Users & Roles Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the stubbed Users & Roles boundary with a real minimal tenant-scoped alpha admin surface: live `/users` and `/roles` API behavior, honest deferred responses for unsupported `/users` routes, a functioning `Settings > Users & Roles` page, and verification that tenant-isolation and user lifecycle behavior are preserved.

**Architecture:** Keep Task 6 narrow and boundary-focused. The Laravel API remains the system of record for tenant-scoped user listing, invite creation, suspend/reactivate, and role listing through the existing identity query/persist interfaces. The WEB app should consume those live routes through one dedicated users hook, render explicit loading/empty/error states, and avoid introducing a broader RBAC editing surface.

**Tech Stack:** Laravel 12, PHP 8.3, PHPUnit feature tests, Next.js 16, React 19, TypeScript, TanStack Query, Axios, Vitest, Playwright.

---

## File Map

- `apps/atomy-q/API/app/Http/Controllers/Api/V1/UserController.php`
  - Replace synthetic live-route payloads with real tenant-scoped behavior and convert deferred routes to honest deferred responses.
- `apps/atomy-q/API/tests/Feature/IdentityGap7Test.php`
  - Add feature coverage for users list/show/invite/suspend/reactivate/roles and tenant-safe `404` behavior.
- `apps/atomy-q/WEB/src/hooks/use-users.ts`
  - New focused WEB hook for users list, roles list, invite, suspend, and reactivate behavior.
- `apps/atomy-q/WEB/src/hooks/use-users.test.tsx`
  - Unit coverage for response normalization and mutation invalidation/error handling.
- `apps/atomy-q/WEB/src/app/(dashboard)/settings/users/page.tsx`
  - Replace the placeholder screen with a live tenant-admin page.
- `apps/atomy-q/WEB/src/app/(dashboard)/settings/users/page.test.tsx`
  - Lock loading, empty, populated, error, invite, suspend, and reactivate behavior.
- `apps/atomy-q/WEB/tests/dashboard-nav.spec.ts`
  - Keep Users & Roles reachable in the shell and assert the real page instead of placeholder copy.
- `apps/atomy-q/WEB/tests/screen-smoke.spec.ts`
  - Keep the screen smoke assertion aligned with the live page heading.
- `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
  - Record shipped API-side Task 6 behavior.
- `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
  - Record shipped WEB-side Task 6 behavior.
- `apps/atomy-q/docs/ALPHA_RELEASE_PLAN_2026-04-15.md`
  - Add the Task 6 implementation-plan link under Section 9 if that section is being kept current.

## Task 1: Add The Failing API Feature Coverage

**Files:**
- Modify: `apps/atomy-q/API/tests/Feature/IdentityGap7Test.php`

- [ ] **Step 1: Add tenant-scoped users list coverage**

Add a feature test that creates two tenants, seeds users into both, authenticates as one tenant, and proves only same-tenant users are returned.

```php
public function test_users_index_returns_only_current_tenant_users(): void
{
    $tenantA = Tenant::query()->create([
        'id' => (string) Str::ulid(),
        'code' => 'tenant-users-a',
        'name' => 'Tenant Users A',
        'email' => 'tenant-users-a@example.com',
        'status' => 'active',
        'timezone' => 'UTC',
        'locale' => 'en',
        'currency' => 'USD',
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i',
    ]);
    $tenantB = Tenant::query()->create([
        'id' => (string) Str::ulid(),
        'code' => 'tenant-users-b',
        'name' => 'Tenant Users B',
        'email' => 'tenant-users-b@example.com',
        'status' => 'active',
        'timezone' => 'UTC',
        'locale' => 'en',
        'currency' => 'USD',
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i',
    ]);

    $adminA = User::factory()->create([
        'tenant_id' => $tenantA->id,
        'email' => 'admin-a@atomy.test',
        'status' => 'active',
        'role' => 'admin',
    ]);
    $memberA = User::factory()->create([
        'tenant_id' => $tenantA->id,
        'email' => 'member-a@atomy.test',
        'status' => 'active',
        'role' => 'user',
    ]);
    User::factory()->create([
        'tenant_id' => $tenantB->id,
        'email' => 'member-b@atomy.test',
        'status' => 'active',
        'role' => 'user',
    ]);

    $response = $this->getJson(
        '/api/v1/users',
        $this->authHeaders((string) $tenantA->id, (string) $adminA->id),
    );

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
    $response->assertJsonFragment(['email' => 'admin-a@atomy.test']);
    $response->assertJsonFragment(['email' => 'member-a@atomy.test']);
    $response->assertJsonMissing(['email' => 'member-b@atomy.test']);
}
```

- [ ] **Step 2: Add show/invite/suspend/reactivate/roles coverage**

Add focused tests for:

- `GET /users/{id}` returns same-tenant record
- `GET /users/{id}` returns `404` for wrong tenant
- `POST /users/invite` creates a persisted pending or inactive record
- `POST /users/invite` returns `409` on duplicate email
- `POST /users/{id}/suspend` updates the stored status
- `POST /users/{id}/reactivate` updates the stored status
- `GET /roles` returns real role data

Use real persisted assertions, for example:

```php
$this->assertDatabaseHas('users', [
    'tenant_id' => $tenant->id,
    'email' => 'invitee@atomy.test',
    'status' => 'pending_activation',
]);
```

If the implementation chooses `inactive` instead of `pending_activation`, update the assertion and keep that status consistent across the controller, WEB copy, and tests.

- [ ] **Step 3: Add deferred-route assertions**

Add one test proving the unsupported routes no longer return fake `data` payloads. A single route-level assertion is enough to lock the behavior:

```php
public function test_user_delegation_rules_endpoint_returns_honest_deferred_response(): void
{
    $tenant = Tenant::query()->create([
        'id' => (string) Str::ulid(),
        'code' => 'tenant-deferred',
        'name' => 'Tenant Deferred',
        'email' => 'tenant-deferred@example.com',
        'status' => 'active',
        'timezone' => 'UTC',
        'locale' => 'en',
        'currency' => 'USD',
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i',
    ]);
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'deferred-admin@atomy.test',
        'status' => 'active',
    ]);

    $response = $this->getJson(
        '/api/v1/users/' . $user->id . '/delegation-rules',
        $this->authHeaders((string) $tenant->id, (string) $user->id),
    );

    $response->assertStatus(501);
    $response->assertJsonPath('error', 'Not implemented');
}
```

- [ ] **Step 4: Run the focused API suite to confirm the new coverage fails**

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter IdentityGap7Test
```

Expected: FAIL because `UserController` still returns synthetic list/show/invite/status payloads and deferred routes still return fake `data`.

- [ ] **Step 5: Commit after the task is implemented**

```bash
git add apps/atomy-q/API/tests/Feature/IdentityGap7Test.php
git commit -m "test(api): lock users roles alpha behavior"
```

## Task 2: Implement The Real API User And Role Behavior

**Files:**
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/UserController.php`
- Test: `apps/atomy-q/API/tests/Feature/IdentityGap7Test.php`

- [ ] **Step 1: Add interface-first controller dependencies**

Inject the live identity contracts into `UserController` and keep the class using `ExtractsAuthContext`.

Use constructor injection with the app’s existing bindings:

```php
use Nexus\Identity\Contracts\PasswordHasherInterface;
use Nexus\Identity\Contracts\RoleQueryInterface;
use Nexus\Identity\Contracts\UserPersistInterface;
use Nexus\Identity\Contracts\UserQueryInterface;
use Nexus\Identity\Exceptions\UserNotFoundException;

final class UserController extends Controller
{
    use ExtractsAuthContext;

    public function __construct(
        private readonly UserQueryInterface $users,
        private readonly UserPersistInterface $userPersist,
        private readonly RoleQueryInterface $roles,
        private readonly PasswordHasherInterface $passwordHasher,
    ) {
    }
```

- [ ] **Step 2: Implement `index()` with tenant-scoped real data**

Replace the synthetic empty payload with a search call scoped to the authenticated tenant and map the returned `UserInterface` rows into the page shape the WEB needs.

Sketch:

```php
public function index(Request $request): JsonResponse
{
    $tenantId = $this->tenantId($request);
    $params = $this->paginationParams($request);

    $rows = array_map(
        fn ($user): array => $this->serializeUser($user, $tenantId),
        $this->users->search([
            'tenant_id' => $tenantId,
            'status' => is_string($request->query('status')) ? (string) $request->query('status') : null,
        ]),
    );

    $total = count($rows);
    $offset = ($params['page'] - 1) * $params['per_page'];
    $paged = array_slice($rows, $offset, $params['per_page']);

    return response()->json([
        'data' => $paged,
        'meta' => [
            'current_page' => $params['page'],
            'per_page' => $params['per_page'],
            'total' => $total,
        ],
    ]);
}
```

Keep the first implementation simple. Do not introduce a new repository just to paginate server-side unless the in-memory slice proves too weak for current alpha scale.

- [ ] **Step 3: Implement `show()` with tenant-safe `404` behavior**

Use the tenant-aware identity lookup and normalize wrong-tenant access into `404`.

```php
public function show(Request $request, string $id): JsonResponse
{
    try {
        $user = $this->users->findById($id, $this->tenantId($request));
    } catch (UserNotFoundException) {
        abort(404);
    }

    return response()->json([
        'data' => $this->serializeUser($user, $this->tenantId($request)),
    ]);
}
```

- [ ] **Step 4: Implement `invite()` as honest pending-account creation**

Validate the request, reject duplicates against the app’s current global email uniqueness rule, and create a real pending/inactive account using the existing persist interface.

```php
public function invite(Request $request): JsonResponse
{
    $tenantId = $this->tenantId($request);
    $validated = $request->validate([
        'email' => ['required', 'email:rfc'],
        'name' => ['required', 'string', 'max:255'],
    ]);

    $email = strtolower(trim((string) $validated['email']));

    if ($this->users->emailExists($email)) {
        return response()->json([
            'message' => 'A user with that email already exists.',
        ], 409);
    }

    $created = $this->userPersist->create([
        'tenant_id' => $tenantId,
        'email' => $email,
        'password_hash' => $this->passwordHasher->hash(Str::random(32)),
        'name' => trim((string) $validated['name']),
        'role' => 'user',
        'status' => 'pending_activation',
        'email_verified_at' => null,
    ]);

    return response()->json([
        'data' => $this->serializeUser($created, $tenantId),
    ], 201);
}
```

If `pending_activation` is not accepted by the current app/user-status rules, switch to `inactive` and keep the tests/doc copy aligned.

- [ ] **Step 5: Implement `suspend()` and `reactivate()` with persisted state changes**

Resolve within the current tenant first, then perform the state change through `update()` so the controller returns real stored state.

```php
public function suspend(Request $request, string $id): JsonResponse
{
    $tenantId = $this->tenantId($request);
    $this->assertTenantUserExists($id, $tenantId);
    $user = $this->userPersist->update($id, ['status' => 'suspended']);

    return response()->json([
        'data' => $this->serializeUser($user, $tenantId),
    ]);
}

public function reactivate(Request $request, string $id): JsonResponse
{
    $tenantId = $this->tenantId($request);
    $this->assertTenantUserExists($id, $tenantId);
    $user = $this->userPersist->update($id, ['status' => 'active']);

    return response()->json([
        'data' => $this->serializeUser($user, $tenantId),
    ]);
}
```

- [ ] **Step 6: Implement `roles()` as a read-only list**

Use the live role query and map to a small, stable payload.

```php
public function roles(Request $request): JsonResponse
{
    $tenantId = $this->tenantId($request);
    $rows = array_map(
        static fn ($role): array => [
            'id' => $role->getId(),
            'name' => $role->getName(),
            'description' => $role->getDescription(),
            'tenant_id' => $role->getTenantId(),
            'is_system_role' => $role->isSystemRole(),
        ],
        $this->roles->getAll($tenantId),
    );

    return response()->json(['data' => $rows]);
}
```

- [ ] **Step 7: Convert unsupported methods to explicit deferred responses**

Add one small helper in the controller and use it for:

- `update()`
- `delegationRules()`
- `updateDelegationRules()`
- `updateAuthorityLimits()`

```php
private function deferred(string $operation, Request $request, array $context = []): JsonResponse
{
    return response()->json([
        'error' => 'Not implemented',
        'message' => $operation . ' is not implemented in alpha.',
        'context' => array_merge(['tenant_id' => $this->tenantId($request)], $context),
    ], 501);
}
```

- [ ] **Step 8: Add private serializer and tenant-user assertion helpers**

Keep the page shape centralized in the controller.

```php
private function assertTenantUserExists(string $id, string $tenantId): void
{
    try {
        $this->users->findById($id, $tenantId);
    } catch (UserNotFoundException) {
        abort(404);
    }
}

private function serializeUser(object $user, string $tenantId): array
{
    $roles = $this->users->getUserRoles($user->getId(), $tenantId);
    $primaryRole = $roles[0]->getName() ?? 'user';

    return [
        'id' => $user->getId(),
        'name' => $user->getName(),
        'email' => $user->getEmail(),
        'status' => $user->getStatus(),
        'role' => $primaryRole,
        'tenant_id' => $user->getTenantId(),
        'created_at' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
        'last_login_at' => $user->getMetadata()['last_login_at'] ?? null,
    ];
}
```

If `last_login_at` is not available from `UserInterface`, it is acceptable to return `null` for now rather than reaching around the contract in a way that breaks layering.

- [ ] **Step 9: Run the focused API suite**

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter IdentityGap7Test
```

Expected: PASS.

- [ ] **Step 10: Commit**

```bash
git add apps/atomy-q/API/app/Http/Controllers/Api/V1/UserController.php apps/atomy-q/API/tests/Feature/IdentityGap7Test.php
git commit -m "feat(api): productionize alpha users roles routes"
```

## Task 3: Add The WEB Users Hook

**Files:**
- Create: `apps/atomy-q/WEB/src/hooks/use-users.ts`
- Create: `apps/atomy-q/WEB/src/hooks/use-users.test.tsx`

- [ ] **Step 1: Write the failing hook tests first**

Add tests that lock:

- valid user envelope normalization
- malformed user envelope rejection
- valid roles envelope normalization
- invite mutation invalidates the users query
- suspend/reactivate mutation invalidates the users query

Use the generated SDK and existing query patterns as the reference.

Example normalization test:

```tsx
it('normalizes the users index payload', async () => {
  server.use(
    http.get('http://localhost:8001/api/v1/users', () =>
      HttpResponse.json({
        data: [
          {
            id: 'user-1',
            name: 'Alpha Admin',
            email: 'admin@atomy.test',
            status: 'active',
            role: 'admin',
            created_at: '2026-04-17T00:00:00Z',
            last_login_at: null,
          },
        ],
        meta: { current_page: 1, per_page: 20, total: 1 },
      }),
    ),
  );

  const { result } = renderHook(() => useUsers(), { wrapper: TestQueryClientProvider });

  await waitFor(() => expect(result.current.data?.items).toHaveLength(1));
  expect(result.current.data?.items[0]?.email).toBe('admin@atomy.test');
});
```

- [ ] **Step 2: Create one focused hook file**

Implement `useUsers()` and keep related mutations in the same file for this slice:

```ts
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { userIndex, userInvite, userRoles, userSuspend, userReactivate } from '@/generated/api/sdk.gen';

export interface SettingsUserRow {
  id: string;
  name: string | null;
  email: string;
  status: string;
  role: string;
  createdAt: string | null;
  lastLoginAt: string | null;
}

export interface SettingsUsersResult {
  items: SettingsUserRow[];
  meta: {
    currentPage: number;
    perPage: number;
    total: number;
  };
}
```

Expose:

- `useUsers()`
- `useUserRoles()`
- `useInviteUser()`
- `useSuspendUser()`
- `useReactivateUser()`

- [ ] **Step 3: Make normalization strict**

Follow the fail-loud pattern already used in `use-award.ts` and the other live hooks:

- reject non-object envelopes
- reject missing `data` arrays
- reject malformed rows missing `id`, `email`, `status`, or `role`
- normalize optional strings to `null`

Example helper:

```ts
function requireText(value: unknown, field: string, index: number): string {
  if (value === null || value === undefined || String(value).trim() === '') {
    throw new Error(`Invalid user row at index ${index}: missing ${field}`);
  }

  return String(value).trim();
}
```

- [ ] **Step 4: Invalidate the users query after each mutation**

All three mutations should invalidate the users list key on success:

```ts
const queryClient = useQueryClient();

return useMutation({
  mutationFn: async (payload: InviteUserPayload) => {
    const response = await userInvite({ body: payload });
    return normalizeSingleUserResponse(response.data);
  },
  onSuccess: async () => {
    await queryClient.invalidateQueries({ queryKey: ['settings-users'] });
  },
});
```

- [ ] **Step 5: Run the hook tests**

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run src/hooks/use-users.test.tsx
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add apps/atomy-q/WEB/src/hooks/use-users.ts apps/atomy-q/WEB/src/hooks/use-users.test.tsx
git commit -m "feat(web): add users roles hook"
```

## Task 4: Rebuild The Users & Roles Page

**Files:**
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/settings/users/page.tsx`
- Create or Modify: `apps/atomy-q/WEB/src/app/(dashboard)/settings/users/page.test.tsx`
- Test: `apps/atomy-q/WEB/src/hooks/use-users.ts`

- [ ] **Step 1: Write the failing page tests**

Lock the page behavior before changing the component:

- loading state
- empty state
- populated table/list
- error state
- invite action
- suspend action button
- reactivate action button

Example test sketch:

```tsx
it('renders the populated users list and invite action', () => {
  vi.mocked(useUsers).mockReturnValue({
    data: {
      items: [
        {
          id: 'user-1',
          name: 'Alpha Admin',
          email: 'admin@atomy.test',
          status: 'active',
          role: 'admin',
          createdAt: '2026-04-17T00:00:00Z',
          lastLoginAt: null,
        },
      ],
      meta: { currentPage: 1, perPage: 20, total: 1 },
    },
    isLoading: false,
    isError: false,
    error: null,
  } as unknown as ReturnType<typeof useUsers>);

  renderWithProviders(<SettingsUsersPage />);

  expect(screen.getByText('Alpha Admin')).toBeInTheDocument();
  expect(screen.getByRole('button', { name: /invite user/i })).toBeEnabled();
});
```

- [ ] **Step 2: Replace the placeholder UI with live state handling**

Reshape the page into:

- `PageHeader`
- invite form or compact invite panel
- user table/list
- explicit loading state
- explicit empty state
- explicit error state

Keep the design system consistent with the rest of the settings pages. Do not introduce a full modal workflow unless the current page architecture clearly benefits from it.

Suggested component structure:

```tsx
const usersQuery = useUsers();
const rolesQuery = useUserRoles();
const inviteMutation = useInviteUser();
const suspendMutation = useSuspendUser();
const reactivateMutation = useReactivateUser();
```

Render logic:

- if `usersQuery.isLoading`, show a shell-safe loading card
- if `usersQuery.isError`, show a recoverable error card
- if `items.length === 0`, show an explicit empty state
- otherwise show rows with per-user action buttons

- [ ] **Step 3: Keep invite semantics honest in the UI**

The page copy must describe the action as creating a pending user record, not as guaranteeing outbound invitation delivery.

Use copy along these lines:

```tsx
subtitle="Create and manage tenant users for the alpha workspace."
```

For the invite success state, prefer text like:

```tsx
'User added to the workspace and marked pending activation.'
```

Avoid:

- “Invitation sent”
- “Email delivered”
- “User can finish setup from their inbox”

- [ ] **Step 4: Keep role presentation read-only**

Show the current role as text or badge only.

Do not add:

- role picker
- role mutation controls
- permission editor

- [ ] **Step 5: Wire suspend/reactivate actions**

For each row:

- if status is `active`, show `Suspend`
- if status is `suspended` or `inactive`, show `Reactivate`

Use mutation callbacks and surface mutation errors in-page rather than swallowing them.

- [ ] **Step 6: Run the page tests**

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run 'src/app/(dashboard)/settings/users/page.test.tsx'
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add apps/atomy-q/WEB/src/app/'(dashboard)'/settings/users/page.tsx apps/atomy-q/WEB/src/app/'(dashboard)'/settings/users/page.test.tsx
git commit -m "feat(web): build alpha users roles page"
```

## Task 5: Refresh Navigation Smoke Coverage And Summaries

**Files:**
- Modify: `apps/atomy-q/WEB/tests/dashboard-nav.spec.ts`
- Modify: `apps/atomy-q/WEB/tests/screen-smoke.spec.ts`
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/docs/ALPHA_RELEASE_PLAN_2026-04-15.md`

- [ ] **Step 1: Update the Playwright nav test to assert the real Users & Roles page**

Replace the old placeholder copy assertion with an assertion against the live page shell.

Update the relevant part of `dashboard-nav.spec.ts` to assert:

```ts
await page.getByRole('link', { name: 'Users & Roles', exact: true }).first().click();
await expect(page).toHaveURL(/\/settings\/users/);
await expect(page.getByRole('heading', { name: 'Users & Roles', exact: true })).toBeVisible();
await expect(page.getByRole('button', { name: /invite user/i })).toBeVisible();
```

- [ ] **Step 2: Keep the screen smoke assertion minimal**

`screen-smoke.spec.ts` only needs to keep asserting that the route renders the page heading. Do not make the smoke test responsible for detailed CRUD behavior.

- [ ] **Step 3: Update both implementation summaries**

Add short Task 6 entries describing:

- API: live `/users` and `/roles`, honest deferred responses for unsupported `/users` routes, tenant-safe `404` behavior
- WEB: live `Settings > Users & Roles` page with loading/empty/error states and invite/suspend/reactivate actions

- [ ] **Step 4: Link the implementation plan from the alpha release plan**

Add the plan reference under Task 6 in `apps/atomy-q/docs/ALPHA_RELEASE_PLAN_2026-04-15.md` to keep the task docs symmetric with Tasks 1-5.

- [ ] **Step 5: Run the focused verification set**

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter IdentityGap7Test
cd apps/atomy-q/WEB && npx vitest run src/hooks/use-users.test.tsx 'src/app/(dashboard)/settings/users/page.test.tsx'
cd apps/atomy-q/WEB && npx playwright test tests/dashboard-nav.spec.ts tests/screen-smoke.spec.ts
```

Expected:

- API feature suite PASS
- WEB unit tests PASS
- Playwright nav/smoke PASS

- [ ] **Step 6: Commit**

```bash
git add apps/atomy-q/WEB/tests/dashboard-nav.spec.ts apps/atomy-q/WEB/tests/screen-smoke.spec.ts apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md apps/atomy-q/docs/ALPHA_RELEASE_PLAN_2026-04-15.md
git commit -m "docs: record alpha task 6 users roles delivery"
```

## Self-Review

### Spec coverage

The plan covers:

- real tenant-scoped `/users` and `/roles` API behavior
- honest deferred behavior for unsupported `/users` routes
- tenant-safe `404` behavior
- honest invite semantics under the current global email uniqueness rule
- a live WEB page with loading/empty/error states and invite/suspend/reactivate actions
- API, WEB, and smoke verification

### Placeholder scan

The plan avoids `TODO`/`TBD` placeholders and names the expected files, tests, routes, and helper shapes explicitly. The only implementation choice intentionally left open is whether the invite-created user status becomes `pending_activation` or `inactive`; the plan calls that out and requires the tests and UI copy to stay aligned with whichever existing app rule is actually accepted.

### Type consistency

The plan keeps one WEB hook boundary (`use-users.ts`) for this slice, one controller boundary (`UserController.php`), and one test anchor (`IdentityGap7Test.php`). The payload field names are consistent across API and WEB tasks:

- `id`
- `name`
- `email`
- `status`
- `role`
- `created_at` / `createdAt`
- `last_login_at` / `lastLoginAt`


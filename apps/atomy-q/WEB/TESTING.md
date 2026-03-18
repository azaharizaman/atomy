# WEB Testing

This app uses a **two-layer test strategy**:

- **Unit/Integration**: Vitest + React Testing Library (fast, runs on every PR)
- **E2E**: Playwright (slower, runs on-demand/scheduled; defaults to mock/seed mode)

## Commands

From `apps/atomy-q/WEB`:

- `npm run lint`
- `npm run test:unit` (CI)
- `npm run test:unit:watch`
- `npm run test:e2e` (Playwright)

## Where tests live

- Unit tests: `src/**/*.test.ts` and `src/**/*.test.tsx`
- Playwright tests: `tests/**` (configured in `playwright.config.ts`)

## Unit test conventions

### React Query hooks

Use the shared test harness in `src/test/utils.tsx`:

- `createTestWrapper()` for `renderHook(...)`
- `renderWithProviders(...)` for component tests

This ensures queries don’t retry and failures surface deterministically.

### Mocking `api` (Axios)

For most hook/component tests, mock `@/lib/api`:

```ts
vi.mock('@/lib/api', () => ({ api: { get: vi.fn(), post: vi.fn() } }));
```

Keep unit tests small: validate params passed to `api.get/post`, and validate mapping/filtering logic.

### When to prefer Playwright

Use Playwright when you want to validate:

- Page navigation + routing
- Cross-component interactions (tables, drawers, filters)
- “Real browser” behavior (focus, keyboard, accessibility affordances)

By default, Playwright runs with `NEXT_PUBLIC_USE_MOCKS=true` (see `playwright.config.ts`), so tests can be deterministic without the API.

## CI gating

PRs touching `apps/atomy-q/WEB/**` run:

- `npm ci`
- `npm run lint`
- `npm run test:unit`

Playwright e2e is intentionally not required on every PR (cost/time), but can be added later as a small smoke suite if desired.


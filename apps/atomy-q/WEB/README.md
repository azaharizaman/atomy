# Atomy-Q Frontend (WEB)

This is the Next.js frontend for the Atomy-Q Quote Comparison & Procurement platform.

## Tech Stack
- **Framework**: Next.js 16 (App Router)
- **Styling**: Tailwind CSS v4
- **State Management**: Zustand (Auth), TanStack Query (Server State)
- **Forms**: React Hook Form + Zod
- **UI Components**: Shadcn/ui (Radix + Tailwind) + Custom Design System tokens
- **Icons**: Lucide React

## Getting Started

1.  **Install dependencies**:
    ```bash
    npm install
    ```

2.  **Environment Setup**:
    Copy `.env.example` to `.env.local` (create one if needed):
    ```env
    NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1
    ```

3.  **Run Development Server**:
    ```bash
    npm run dev
    ```

## Environment Variables

Required:
- `NEXT_PUBLIC_API_URL`: Base API URL (default used by the app: `http://localhost:8000/api/v1`).

Optional:
- `NEXT_PUBLIC_USE_MOCKS=true`: Use mocked dashboard data for KPI/activity widgets.
- `PLAYWRIGHT_BASE_URL`: Override Playwright base URL (default: `http://localhost:3000`).
- `PLAYWRIGHT_WEB_SERVER_COMMAND`: Override the Playwright web server command.

## Running The App

Development (hot reload):
```bash
npm run dev
```

Production build + start:
```bash
npm run build
npm run start
```

## Running Tests

E2E (Playwright):
```bash
# one-time browser install
npm run test:e2e:install

# run tests (starts Next dev server automatically)
npm run test:e2e
```

By default, the auth test uses a **mocked** API. To run the **login test against the real API** (catches "Invalid credentials" and redirect):

1. **API** must be running with **`JWT_SECRET`** set in `.env` (see `apps/atomy-q/API/.env.example`). Without it, login returns 500.
2. Start the API: `cd apps/atomy-q/API && php artisan serve --port=8000`
3. Start the WEB: `npm run dev` (or use existing server).
4. Run: `E2E_USE_REAL_API=1 PLAYWRIGHT_USE_EXISTING_SERVER=1 npm run test:e2e -- tests/auth.spec.ts -g "real API"`

If login still fails in the browser, ensure the API `config/cors.php` allows your WEB origin (e.g. `http://localhost:3000`) and `supports_credentials` is `true` when the WEB sends `withCredentials: true`.

CI-friendly run:
```bash
PLAYWRIGHT_WEB_SERVER_COMMAND="npm run build && npm run start -- --port 3000" npm run test:e2e:ci
```

## Test Data And Mocks

Playwright tests:
- Stub `/api/v1/auth/login` and `/api/v1/me` with Playwright route handlers.
- The dashboard uses `NEXT_PUBLIC_USE_MOCKS=true` (set in `playwright.config.ts`) to avoid API calls for KPIs/activity.

Local manual testing:
- If the backend is running at `NEXT_PUBLIC_API_URL`, the login page will call `/auth/login` and `/me` against the API.
- If you want to run the UI without a backend, set `NEXT_PUBLIC_USE_MOCKS=true` for the dashboard, and use Playwright to stub auth during E2E runs.

## Local Backend Setup

This WEB app expects the backend to expose:
- `POST /api/v1/auth/login` → `{ access_token, user }`
- `GET /api/v1/me` → user payload
- Optional: `GET /api/v1/dashboard/kpis`, `GET /api/v1/dashboard/recent-activity`

To run against a local API:
1.  Start the backend service (API app).
2.  Set `NEXT_PUBLIC_API_URL` in `.env.local`.
3.  Start the WEB app with `npm run dev`.

## Project Structure
- `src/app`: Next.js App Router pages and layouts.
- `src/components`: UI components (layout, design system, shadcn).
- `src/hooks`: Custom React hooks (React Query wrappers).
- `src/lib`: Utilities (API client, tokens, class mergers).
- `src/store`: Global client state (Zustand).
- `src/providers`: Context providers (Query, Auth).

## Authentication
The app uses JWT authentication. The access token is stored in memory (`useAuthStore`) and the refresh token is expected to be handled via `httpOnly` cookies by the backend.
On app load, `AuthProvider` attempts to refresh the session.

## API Integration
The API client is located at `src/lib/api.ts`. It includes interceptors for attaching the Bearer token and handling 401 token refreshes automatically.

## Contributing

1.  Create a branch for your work.
2.  Keep changes scoped to the WEB app in `apps/atomy-q/WEB`.
3.  Update or add tests for new behavior. Run `npm run test:e2e` when applicable.
4.  Update `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md` after functional changes.
5.  Open a PR with a clear summary and screenshots for UI changes.

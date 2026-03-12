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

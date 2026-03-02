# Changelog

All notable changes to the Canary Atomy Next.js app will be documented in this file.

## [2026-03-01] - Identity Operations Integration

### Added
- **Authentication System**:
  - Implemented `AuthContext` and `AuthProvider` for centralized auth state management.
  - Added `useAuth` hook for easy access to authentication state and methods.
  - Created a modern `LoginModal` with support for email, password, and tenant ID.
  - Integrated JWT Bearer token support in the API client (`fetchApi`).
  - Added Sign In/Sign Out capabilities to the Sidebar.
- **User Lifecycle Actions**:
  - Enhanced Users page with real-time Suspend and Activate actions.
  - Added visual feedback (loaders) for user lifecycle transitions.
  - Actions are protected and only visible when an active identity session is present.
- **Persistent Sessions**:
  - Implemented `localStorage` persistence for authentication tokens and user context.
  - Automatic session restoration on app load.

### Changed
- Updated `Sidebar` to reflect current tenant and user information when logged in.
- Converted Users page to a Client Component to support interactive lifecycle management.
- Enhanced API client with full support for new `/auth/*` and `/users/*` endpoints.

### Technical
- Integrated `lucide-react` icons for new auth-related UI elements.
- Applied consistent Tailwind CSS styling for new components (Modals, Buttons, Alerts).

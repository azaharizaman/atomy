# quote-comparison-web

Dedicated frontend app for the Atomy-Q SaaS quotation comparison product.

## Goals
- Keep this app isolated from existing canary frontend apps.
- Establish robust reusable component architecture for shell, modal, data views, and finder flows.
- Support configuration-driven screen composition for future feature expansion.

## Architecture

```text
src/
  app/                     # Next.js app router entry
  config/                  # App configuration (nav, feature toggles, etc)
  components/
    ui/                    # Primitive reusable UI components
    shell/                 # Reusable layout shell (sidebar/header/footer)
    modal/                 # Modal layouts and wrappers
    data-view/             # Generic table/card collection rendering
    record-finder/         # Reusable finder/search/filter component
  features/
    quotes/                # Domain-specific frontend feature modules
  lib/                     # Shared helpers/utilities
  types/                   # Shared TypeScript contracts
```

## Reusable Foundations Included
- `AppShell` with config-driven `AppSidebar` and configurable quick actions.
- `AppModalLayout` wrapper for consistent modal structure.
- `RecordFinder` with pluggable filters and callback contract.
- `RecordCollectionView` that switches between reusable table and card modes.

## Scripts
- `npm run dev`
- `npm run build`
- `npm run start`
- `npm run lint`
- `npm run typecheck`

## Next Steps
- Add role-aware route guards and permission matrix.
- Add OpenAPI-generated API client from canary API spec.
- Add TanStack Query data layer and optimistic/non-optimistic mutation policies.
- Add full modal registry and keyboard accessibility checks.

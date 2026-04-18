# System Overview

Atomy-Q is a SaaS application composed of two primary applications and a shared Nexus dependency stack.

## Applications

- `apps/atomy-q/API`
  Laravel 12 API providing auth, tenant onboarding, RFQ lifecycle, quote intake, normalization, comparison, awards, decision trail, and minimal users/roles.
- `apps/atomy-q/WEB`
  Next.js 16 frontend using React 19, TanStack Query, Axios, Zustand, and Tailwind CSS v4 to deliver the buyer-side application.

## Architectural Shape

- Layer 1:
  pure domain packages under `packages/`
- Layer 2:
  workflow orchestration under `orchestrators/`
- Layer 3:
  Laravel adapters under `adapters/Laravel/` plus app-local integration in `apps/atomy-q/API/app`

## Operating Rule

Atomy-Q should consume Nexus boundaries honestly:
- Layer 1 for domain contracts and value objects
- Layer 2 for workflow orchestration boundaries
- Layer 3 for persistence and framework integration

The WEB app must treat live-mode failures honestly and never fabricate alpha success through seed fallback when `NEXT_PUBLIC_USE_MOCKS=false`.

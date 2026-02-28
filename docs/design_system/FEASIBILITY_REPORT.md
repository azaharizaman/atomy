# Feasibility Study: Nexus Design System (NDS)

**Date:** 2026-03-01
**Target:** Enterprise Resource Planning (ERP) UI / Canary Atomy Next.js
**Author:** Gemini CLI (Architecture Team)

## 1. Executive Summary

This document analyzes the feasibility of establishing a proprietary, high-fidelity design system for the Nexus ERP ecosystem. Given the specific requirements of ERP software—high data density, complex forms, keyboard navigation, and accessibility—standard "marketing-focused" UI libraries often fall short.

**Recommendation:** We should **not** build a design system from scratch (too resource-intensive), nor should we adopt a heavy, opinionated library like MUI or AntD (too rigid).

Instead, we recommend adopting a **"Headless + Tailwind"** architecture (the Shadcn/ui model). This allows us to own the code, customize the density for ERP use cases, and maintain high developer velocity while leveraging battle-tested accessibility primitives.

## 2. Current State Analysis

### 2.1. Existing Frontend Landscape
- **`apps/canary-atomy-nextjs`**: Uses bespoke, hand-rolled components (Sidebar, ContentCard) styled with Tailwind CSS v4. Lacks a coherent system for inputs, dialogs, or data tables.
- **`apps/laravel-nexus-saas`**: Uses a Vue.js port of shadcn/ui. This creates a visual disconnect between the Next.js and Laravel apps.

### 2.2. The ERP Challenge
ERP users differ from standard B2C users. They require:
- **Information Density:** Seeing more data on the screen without scrolling.
- **Speed:** Keyboard shortcuts, rapid data entry, and fast loading.
- **Complexity:** Nested forms, multi-step wizards, and complex filtering.

## 3. Strategic Options

| Option | Description | Pros | Cons | Verdict |
| :--- | :--- | :--- | :--- | :--- |
| **1. Build from Scratch** | CSS Modules/Styled Components + Custom React logic. | 100% control. | Extremely slow. High maintenance. Accessibility risks. | **REJECTED** |
| **2. Heavy Component Lib** | Material UI (MUI), Ant Design, Mantine. | "Enterprise" look out of the box. Lots of components. | Bundle bloat. Hard to override styles. "Generic" look. | **REJECTED** |
| **3. Headless + Utility** | Radix UI (Primitives) + Tailwind CSS (Styling). | Accessible. Copy-paste ownership. Infinite customization. Zero runtime overhead. | Initial setup time. Need to define "tokens". | **RECOMMENDED** |

## 4. Feasibility Verdict

Establishing a "Nexus Design System" based on the **Headless + Utility** model is **Highly Feasible** and recommended.

### Why it works for Nexus:
1.  **Ownership:** We own the component code (it lives in `src/components/ui`), allowing us to patch bugs without waiting for upstream PRs.
2.  **Tailwind v4 Synergy:** The project already uses Tailwind v4. This architecture leverages the new CSS-first configuration perfectly.
3.  **Backend Agnostic:** The UI layer is purely presentational. It consumes data from `canary-atomy-api` via standard props, making it compatible with any backend logic.
4.  **Consistency:** We can align the React implementation with the existing Vue implementation (in Laravel) by sharing the same design tokens (colors, radius, spacing) defined in CSS variables.

## 5. Risk Assessment

*   **Risk:** Tailwind v4 is new; some ecosystem tools (like `shadcn` CLI) might need manual configuration adjustments.
    *   *Mitigation:* We will document the manual installation steps for v4 compatibility.
*   **Risk:** "Copy-paste" architecture can lead to drift if we have multiple React apps.
    *   *Mitigation:* Start monorepo-local in `canary-atomy-nextjs`. Extract to a workspace package (`packages/ui`) only when a second React app is introduced.

## 6. Conclusion

The "Nexus Design System" will be a **code-first, Tailwind-native system** optimized for ERP density. It will prioritize Developer Experience (DX) by providing copy-paste accessible components that developers can fully control.

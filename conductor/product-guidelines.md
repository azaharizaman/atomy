# Product Guidelines: Nexus (Atomy)

## Prose Style
- **Technical & Concise:** All documentation should be written for senior developers and architects. Use precise terminology (e.g., "Inversion of Control," "Separation of Concerns").
- **Direct & Active Voice:** Avoid fluff. State requirements and instructions clearly (e.g., "Implement the interface" instead of "It would be good if the interface was implemented").
- **Self-Documenting Code:** Prioritize expressive naming and strict typing over extensive inline comments.

## Developer Experience (DX) Principles
- **Contract-First Development:** Interfaces MUST be defined in `src/Contracts/` before implementation. This ensures clear boundaries and easier mocking in tests.
- **Atomic Reliability:** Every package must be independently testable and fulfill its defined contract without side effects.
- **Zero-Friction Scaffolding:** New packages or orchestrators should follow established templates and naming conventions to minimize boilerplate overhead.
- **Error Transparency:** Provide clear, actionable exceptions. Avoid generic catch-all errors; use domain-specific exceptions to help developers trace issues.
- **Strict Typing:** Leverage PHP 8.3+ features like `readonly` classes and strict type declarations to catch errors early.

## Branding & Visual Style (Applicable to Apps)
- **Minimalist ERP:** The user interface (Inertia/React) should favor data-density and efficiency over decorative elements.
- **Color Palette:** Professional and neutral tones (slate, gray, deep blue).
- **Typography:** Clear, sans-serif fonts (e.g., Inter, SF Pro) for high readability in complex data tables.
- **Interactive Feedback:** Consistent use of subtle transitions, loaders, and toast notifications for a responsive feel.

## UX Principles (Applicable to Apps)
- **Efficiency First:** Design workflows that minimize clicks and cognitive load for power users who handle large volumes of enterprise data.
- **Consistent Layouts:** Standardize page structures across all modules (e.g., search/filter on top, data table in center, actions in consistent locations).
- **Accessibility:** Adhere to WCAG 2.1 Level AA standards to ensure inclusivity in the workplace.

## AI Collaboration Guidelines
- **Context-Rich Instructions:** When tasking AI agents, provide clear links to architectural references (`ARCHITECTURE.md`).
- **Standardized Commit Messages:** Use conventional commits to ensure a clear, automated history of changes.
- **Modular Refactoring:** Encourage small, atomic changes to preserve the integrity of the three-layer architecture.

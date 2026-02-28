# Tech Stack Proposal: Nexus Design System

**Target Application:** `apps/canary-atomy-nextjs`
**Framework:** Next.js 16 (React 19)

## 1. Core Architecture

The system follows a **Component Composition** pattern. We provide "dumb" UI components that are composed by "smart" features.

### 1.1. Primitives & Accessibility (The "Headless" Layer)
*   **Library:** `Radix UI` (via `shadcn/ui` base)
*   **Why:** Industry standard for accessible, unstyled primitives. Handles complex WAI-ARIA patterns (Dialogs, Popovers, Tabs) that are hard to get right manually.
*   **Alternative considered:** `React Aria` (Adobe). *Decision:* Radix is chosen for its tighter integration with the Tailwind ecosystem and the `shadcn` community.

### 1.2. Styling Engine
*   **Library:** `Tailwind CSS v4`
*   **Configuration:** CSS-first configuration (using `@theme` blocks in `globals.css`) instead of `tailwind.config.ts`.
*   **Utility Helper:** `clsx` + `tailwind-merge` (standard `cn()` utility) for safe class overriding.

### 1.3. Icons
*   **Library:** `Lucide React`
*   **Why:** Already in use. Consistent 2px stroke weight matches the "clean ERP" aesthetic.
*   **Standard:** All icons must be imported from `lucide-react`.

## 2. Specialized ERP Components

An ERP requires more than just buttons and inputs. The following libraries are mandated for complex data handling.

### 2.1. Data Grids (The "Excel" Replacements)
*   **Library:** `@tanstack/react-table` (v8)
*   **Why:** Headless table logic. Supports sorting, filtering, pagination, row selection, and virtualization. Essential for high-density data views.
*   **Integration:** We will wrap this in a `<DataTable />` component that exposes these features via simple props.

### 2.2. Forms & Validation
*   **State Management:** `react-hook-form`
*   **Validation:** `zod` (Schema validation)
*   **Why:** Typescript-first validation. Zod schemas can be inferred from API Platform's Hydra/OpenAPI specs (via potential future codegen).

### 2.3. Data Visualization
*   **Library:** `Recharts`
*   **Why:** React-native, reliable, and highly customizable via Tailwind classes.
*   **Usage:** Dashboards, analytics, reporting.

### 2.4. Date & Calendar
*   **Library:** `date-fns` (Logic) + `react-day-picker` (UI)
*   **Why:** `date-fns` is lightweight and modular.

## 3. Integration with Canary Atomy API

The Design System remains "dumb" (presentational). Integration happens at the **Page** or **Feature** level.

*   **Pattern:** `Server Component` fetches data -> Passes props to `Client Component` -> Client Component renders `DS Primitives`.
*   **Type Safety:**
    *   API responses are typed via interfaces in `src/lib/api.ts`.
    *   DS components use generic types (e.g., `<DataTable<User> data={users} columns={columns} />`).

## 4. Stack Summary

| Category | Technology | Version |
| :--- | :--- | :--- |
| **Framework** | Next.js | 16.x |
| **UI Library** | React | 19.x |
| **Styling** | Tailwind CSS | 4.x |
| **Primitives** | Radix UI | Latest |
| **Icons** | Lucide React | 0.x |
| **Tables** | TanStack Table | v8 |
| **Forms** | React Hook Form | Latest |
| **Schema** | Zod | 3.x |
| **Charts** | Recharts | 2.x |
| **Utils** | clsx, tailwind-merge | Latest |

## 5. Next Steps

1.  Initialize standard `shadcn` components in `src/components/ui`.
2.  Configure `tailwind.css` to define the "Nexus" design tokens (colors, radius).
3.  Build the `<DataTable />` organism using TanStack Table.

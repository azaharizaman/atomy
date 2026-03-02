# Implementation Guide: Nexus Design System

This guide details the steps to initialize and utilize the Nexus Design System within `apps/canary-atomy-nextjs`.

## 1. Initialization (Manual Setup for Tailwind v4)

Since we are using Tailwind v4, the standard `shadcn` init command might need manual adjustments.

### 1.1. Install Dependencies
```bash
# 1. Base utils
pnpm add clsx tailwind-merge class-variance-authority

# 2. Iconography
pnpm add lucide-react

# 3. Animation
pnpm add tailwindcss-animate
```

### 1.2. Create Utility Helper
Create `src/lib/utils.ts`:
```typescript
import { type ClassValue, clsx } from "clsx"
import { twMerge } from "tailwind-merge"

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}
```

### 1.3. Define Design Tokens (CSS Variables)
Update `src/app/globals.css`. We use CSS variables for themeing, which allows runtime switching (e.g., dark mode or tenant-specific branding).

```css
@import "tailwindcss";

@plugin "tailwindcss-animate";

:root {
  --background: 0 0% 100%;
  --foreground: 222.2 84% 4.9%;
  
  --card: 0 0% 100%;
  --card-foreground: 222.2 84% 4.9%;
 
  --popover: 0 0% 100%;
  --popover-foreground: 222.2 84% 4.9%;
 
  --primary: 222.2 47.4% 11.2%;
  --primary-foreground: 210 40% 98%;
 
  --secondary: 210 40% 96.1%;
  --secondary-foreground: 222.2 47.4% 11.2%;
 
  --muted: 210 40% 96.1%;
  --muted-foreground: 215.4 16.3% 46.9%;
 
  --accent: 210 40% 96.1%;
  --accent-foreground: 222.2 47.4% 11.2%;
 
  --destructive: 0 84.2% 60.2%;
  --destructive-foreground: 210 40% 98%;

  --border: 214.3 31.8% 91.4%;
  --input: 214.3 31.8% 91.4%;
  --ring: 222.2 84% 4.9%;
 
  --radius: 0.5rem;
}

@theme inline {
  --color-background: hsl(var(--background));
  --color-foreground: hsl(var(--foreground));
  --color-card: hsl(var(--card));
  --color-card-foreground: hsl(var(--card-foreground));
  --color-popover: hsl(var(--popover));
  --color-popover-foreground: hsl(var(--popover-foreground));
  --color-primary: hsl(var(--primary));
  --color-primary-foreground: hsl(var(--primary-foreground));
  --color-secondary: hsl(var(--secondary));
  --color-secondary-foreground: hsl(var(--secondary-foreground));
  --color-muted: hsl(var(--muted));
  --color-muted-foreground: hsl(var(--muted-foreground));
  --color-accent: hsl(var(--accent));
  --color-accent-foreground: hsl(var(--accent-foreground));
  --color-destructive: hsl(var(--destructive));
  --color-destructive-foreground: hsl(var(--destructive-foreground));
  --color-border: hsl(var(--border));
  --color-input: hsl(var(--input));
  --color-ring: hsl(var(--ring));
  
  --radius-lg: var(--radius);
  --radius-md: calc(var(--radius) - 2px);
  --radius-sm: calc(var(--radius) - 4px);
}
```

## 2. Component Architecture

### 2.1. Directory Structure
```
src/
  components/
    ui/           # Dumb, copy-pasted primitives (Button, Input, Table)
      button.tsx
      input.tsx
      table.tsx
    shared/       # Application-specific molecules
      user-nav.tsx
      sidebar.tsx
    features/     # Complex organisms
      users/
        users-data-table.tsx
        users-columns.tsx
```

### 2.2. Adding a Component
We use the "copy-paste" philosophy. Do not install components as npm packages.
Use the `shadcn` CLI (once configured) or manually copy the code from ui.shadcn.com.

## 3. Building an ERP Feature (Example: Users Table)

### Step 1: Define the Data Shape
Ensure `src/lib/api.ts` has the correct interface.
```typescript
export interface User {
  id: string;
  email: string;
  role: string;
  status: 'active' | 'suspended';
}
```

### Step 2: Define Columns
Create `users-columns.tsx` using TanStack Table definitions.
```typescript
import { ColumnDef } from "@tanstack/react-table"
import { User } from "@/lib/api"

export const columns: ColumnDef<User>[] = [
  {
    accessorKey: "email",
    header: "Email",
  },
  {
    accessorKey: "status",
    header: "Status",
  },
]
```

### Step 3: Render the Data Table
```typescript
// page.tsx
import { getUsers } from "@/lib/api"
import { DataTable } from "@/components/ui/data-table"
import { columns } from "./users-columns"

export default async function UsersPage() {
  const data = await getUsers()
  return <DataTable columns={columns} data={data} />
}
```

## 4. Best Practices for ERP

1.  **Compact Mode:** For data-heavy screens, use `h-8` instead of `h-10` for inputs and buttons.
2.  **Keyboard Nav:** Ensure all interactive elements are reachable via Tab.
3.  **Loading States:** Use `<Skeleton />` for table rows while fetching data.
4.  **Error Handling:** Wrap tables in `ErrorBoundary` to prevent full page crashes.

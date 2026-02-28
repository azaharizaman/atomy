# Nexus Design System (Canary)

This document outlines the design system implemented in `apps/canary-atomy-nextjs`. It follows the Tech Stack Proposal (Next.js 16, Tailwind 4, Radix UI).

## 1. Core Principles

- **Utility First:** Use Tailwind CSS 4 utility classes for almost all styling.
- **Composition over Inheritance:** Build complex components by composing simpler primitives.
- **Headless Accessibility:** Use Radix UI primitives for complex interactive components.
- **ERP Aesthetic:** Clean, high-density, high-contrast, 2px stroke weight (Lucide icons).

## 2. Styling (Tailwind 4)

We use the new CSS-first configuration in `src/app/globals.css` via the `@theme` block.

### 2.1. Colors
The system uses HSL variables for color tokens.
- `primary`: The main brand color (Purple/Indigo).
- `secondary`: Subdued background/border color.
- `accent`: Used for highlights and interactions.
- `destructive`: Used for errors and dangerous actions.
- `muted`: Low-contrast text and backgrounds.
- `card`: Background for container components.
- `sidebar-*`: Specialized tokens for the ERP sidebar.

### 2.2. Usage
Always use the functional color classes:
```tsx
<div className="bg-primary text-primary-foreground">...</div>
<div className="border-border bg-card text-card-foreground">...</div>
<div className="text-muted-foreground">...</div>
```

## 3. UI Components (`src/components/ui`)

Standardized "dumb" components based on Shadcn/Radix patterns.

- **`Button`**: Various variants (`default`, `outline`, `ghost`, `destructive`).
- **`Card`**: Basic container for content.
- **`Input` / `Label` / `Form`**: Integrated with `react-hook-form` and `zod`.
- **`Dialog`**: Modal system for complex interactions.
- **`Tabs`**: Navigation within pages.
- **`Avatar`**: User profile representation.
- **`Badge`**: Status indicators.
- **`Table`**: Low-level table primitives.

## 4. Organisms (`src/components`)

Higher-level components that combine multiple UI primitives.

- **`DataTable`**: A powerful wrapper around `@tanstack/react-table`. Supports:
    - Sorting
    - Filtering (search key)
    - Pagination
- **`ContentHeader`**: Page header with titles, avatars, actions, and tabs.
- **`ContentCard`**: Standardized card for dashboard/grid views.
- **`Sidebar`**: Multi-tenant aware navigation with collapse support.
- **`LoginModal`**: Authentication interface using `Form` and `Dialog`.

## 5. Implementation Guidelines

### 5.1. Creating a New Page
Every page should follow this structural pattern:
```tsx
<div className="flex flex-col">
  <div className="border-b bg-card px-8 py-6">
    <ContentHeader title="Page Title" ... />
  </div>
  <div className="flex-1 px-8 py-6">
    {/* Content goes here */}
  </div>
</div>
```

### 5.2. Working with Forms
Use the `Form` components with `zod` for validation:
```tsx
const formSchema = z.object({ ... });
const form = useForm({ resolver: zodResolver(formSchema) });
// ...
<Form {...form}>
  <form onSubmit={form.handleSubmit(onSubmit)}>
    <FormField name="email" render={({ field }) => (
      <FormItem>
        <FormLabel>Email</FormLabel>
        <FormControl><Input {...field} /></FormControl>
        <FormMessage />
      </FormItem>
    )} />
  </form>
</Form>
```

### 5.3. Data Grids
Prefer `DataTable` for list views:
```tsx
<DataTable columns={columns} data={data} searchKey="name" />
```
Define `columns` using `@tanstack/react-table`'s `ColumnDef`.

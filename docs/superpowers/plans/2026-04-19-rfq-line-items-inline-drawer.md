# RFQ Line Items Inline Drawer Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a visible RFQ line-item creation entry point and inline drawer so users can create line items from the line-items screen without leaving the current RFQ.

**Architecture:** Keep the page-level state in the line-items page, move the create form into a dedicated drawer component, and isolate the live mutation into a small hook. The page should own the open/close state and query refresh logic; the drawer should own field capture, validation, and submission UI. Mock mode should remain read-friendly, but create submissions must be blocked with a clear message instead of pretending to persist.

**Tech Stack:** Next.js App Router, React, TypeScript, react-hook-form, zod, TanStack Query, generated Atomy Q API client, Tailwind CSS, Vitest, Testing Library.

---

### Task 1: Create the line-item mutation hook

**Files:**
- Create: `apps/atomy-q/WEB/src/hooks/use-create-rfq-line-item.ts`
- Test: `apps/atomy-q/WEB/src/hooks/use-create-rfq-line-item.test.ts`

**Purpose:** Provide a focused live create mutation for RFQ line items so the drawer does not call the generated client directly.

- [ ] **Step 1: Write the failing test**

```ts
import { describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { useCreateRfqLineItem } from './use-create-rfq-line-item';

const mockRfQStoreLineItem = vi.fn();

vi.mock('@/generated/api', () => ({
  rfqStoreLineItem: (...args: unknown[]) => mockRfQStoreLineItem(...args),
}));

describe('useCreateRfqLineItem', () => {
  it('calls the generated create mutation with the rfq id and payload', async () => {
    mockRfQStoreLineItem.mockResolvedValue({
      data: { id: 'li-1', rfq_id: 'rfq-1' },
    });

    const queryClient = new QueryClient();
    const wrapper = ({ children }: { children: React.ReactNode }) => (
      <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
    );

    const { result } = renderHook(() => useCreateRfqLineItem('rfq-1'), { wrapper });

    await result.current.mutateAsync({
      description: 'Nitrogen compressor',
      quantity: 2,
      uom: 'ea',
      unit_price: 1200,
      currency: 'USD',
      specifications: 'Spare set',
    });

    await waitFor(() => expect(mockRfQStoreLineItem).toHaveBeenCalled());
    expect(mockRfQStoreLineItem).toHaveBeenCalledWith(
      expect.objectContaining({
        path: { rfqId: 'rfq-1' },
        body: expect.objectContaining({
          description: 'Nitrogen compressor',
          quantity: 2,
          uom: 'ea',
          unit_price: 1200,
          currency: 'USD',
          specifications: 'Spare set',
        }),
      }),
    );
  });
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `cd apps/atomy-q/WEB && npx vitest run src/hooks/use-create-rfq-line-item.test.ts`

Expected: fail because the hook file does not exist yet.

- [ ] **Step 3: Write the minimal implementation**

```ts
'use client';

import { useMutation } from '@tanstack/react-query';

import { rfqStoreLineItem } from '@/generated/api';

export interface CreateRfqLineItemInput {
  description: string;
  quantity: number;
  uom: string;
  unit_price: number;
  currency: string;
  specifications?: string | null;
}

export function useCreateRfqLineItem(rfqId: string) {
  return useMutation({
    mutationFn: async (input: CreateRfqLineItemInput) => {
      return rfqStoreLineItem({
        path: { rfqId },
        body: {
          description: input.description,
          quantity: input.quantity,
          uom: input.uom,
          unit_price: input.unit_price,
          currency: input.currency,
          specifications: input.specifications ?? null,
        },
      });
    },
  });
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `cd apps/atomy-q/WEB && npx vitest run src/hooks/use-create-rfq-line-item.test.ts`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add apps/atomy-q/WEB/src/hooks/use-create-rfq-line-item.ts apps/atomy-q/WEB/src/hooks/use-create-rfq-line-item.test.ts
git commit -m "feat: add rfq line item create hook"
```

### Task 2: Build the inline line-item drawer

**Files:**
- Create: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/line-items/line-item-drawer.tsx`
- Test: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/line-items/line-item-drawer.test.tsx`

**Purpose:** Encapsulate the create form, drawer overlay, and accessibility behavior so the page only manages visibility and refresh.

- [ ] **Step 1: Write the failing test**

```ts
import React from 'react';
import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen } from '@testing-library/react';
import { renderWithProviders } from '@/test/utils';

const mockMutateAsync = vi.fn();

vi.mock('@/hooks/use-create-rfq-line-item', () => ({
  useCreateRfqLineItem: () => ({
    mutateAsync: mockMutateAsync,
    isPending: false,
    error: null,
  }),
}));

import { LineItemDrawer } from './line-item-drawer';

describe('LineItemDrawer', () => {
  it('submits the create form and closes on success', async () => {
    mockMutateAsync.mockResolvedValueOnce({ data: { id: 'li-1' } });
    const onClose = vi.fn();
    const onCreated = vi.fn();

    renderWithProviders(<LineItemDrawer rfqId="rfq-1" onClose={onClose} onCreated={onCreated} isWritable open />);

    fireEvent.change(screen.getByLabelText(/description/i), { target: { value: 'Nitrogen compressor' } });
    fireEvent.change(screen.getByLabelText(/quantity/i), { target: { value: '2' } });
    fireEvent.change(screen.getByLabelText(/uom/i), { target: { value: 'ea' } });
    fireEvent.change(screen.getByLabelText(/unit price/i), { target: { value: '1200' } });
    fireEvent.change(screen.getByLabelText(/currency/i), { target: { value: 'USD' } });
    fireEvent.click(screen.getByRole('button', { name: /save line item/i }));

    expect(mockMutateAsync).toHaveBeenCalledWith(
      expect.objectContaining({
        description: 'Nitrogen compressor',
        quantity: 2,
        uom: 'ea',
        unit_price: 1200,
        currency: 'USD',
      }),
    );
    expect(onCreated).toHaveBeenCalled();
    expect(onClose).toHaveBeenCalled();
  });
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `cd apps/atomy-q/WEB && npx vitest run src/app/(dashboard)/rfqs/[rfqId]/line-items/line-item-drawer.test.tsx`

Expected: fail because the drawer component does not exist yet.

- [ ] **Step 3: Write the minimal implementation**

```tsx
'use client';

import React from 'react';
import { X } from 'lucide-react';

import { Button } from '@/components/ds/Button';
import { TextAreaInput, TextInput } from '@/components/ds/Input';
import { useCreateRfqLineItem } from '@/hooks/use-create-rfq-line-item';

export function LineItemDrawer({
  rfqId,
  open,
  onClose,
  onCreated,
  isWritable,
}: {
  rfqId: string;
  open: boolean;
  onClose: () => void;
  onCreated?: () => Promise<void> | void;
  isWritable: boolean;
}) {
  const createLineItem = useCreateRfqLineItem(rfqId);
  const [description, setDescription] = React.useState('');
  const [quantity, setQuantity] = React.useState('1');
  const [uom, setUom] = React.useState('');
  const [unitPrice, setUnitPrice] = React.useState('0');
  const [currency, setCurrency] = React.useState('USD');
  const [specifications, setSpecifications] = React.useState('');
  const [error, setError] = React.useState<string | null>(null);

  React.useEffect(() => {
    if (open) {
      setDescription('');
      setQuantity('1');
      setUom('');
      setUnitPrice('0');
      setCurrency('USD');
      setSpecifications('');
      setError(null);
    }
  }, [open]);

  if (!open) return null;

  const submit = async (event: React.FormEvent) => {
    event.preventDefault();
    setError(null);

    if (!isWritable) {
      setError('Turn off NEXT_PUBLIC_USE_MOCKS to save line items.');
      return;
    }

    try {
      await createLineItem.mutateAsync({
        description: description.trim(),
        quantity: Number(quantity),
        uom: uom.trim(),
        unit_price: Number(unitPrice),
        currency: currency.trim().toUpperCase(),
        specifications: specifications.trim() ? specifications.trim() : null,
      });
      await onCreated?.();
      onClose();
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Could not create line item.');
    }
  };

  return (
    <>
      <div
        className="fixed inset-0 bg-black/20 z-20"
        role="button"
        tabIndex={0}
        aria-label="Close line item drawer"
        onClick={onClose}
        onKeyDown={(e) => {
          if (e.key === 'Enter' || e.key === ' ' || e.key === 'Escape') {
            e.preventDefault();
            onClose();
          }
        }}
      />
      <div className="fixed inset-y-0 right-0 w-full max-w-md bg-white border-l border-slate-200 shadow-lg z-30 flex flex-col">
        <div className="flex items-center justify-between p-4 border-b border-slate-200">
          <h2 className="text-sm font-semibold text-slate-900">Add line item</h2>
          <Button size="sm" variant="secondary" onClick={onClose}>
            <X size={14} className="mr-1.5" />
            Close
          </Button>
        </div>
        <form onSubmit={submit} className="flex-1 overflow-y-auto p-4 space-y-4">
          {!isWritable && (
            <div className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
              Mock mode is read-only for line-item creation. Turn off NEXT_PUBLIC_USE_MOCKS to save.
            </div>
          )}
          {error && <div className="text-sm text-red-600">{error}</div>}
          <TextAreaInput id="line-item-description" label="Description" value={description} onChange={(e) => setDescription(e.target.value)} />
          <div className="grid grid-cols-2 gap-3">
            <TextInput id="line-item-quantity" label="Quantity" type="number" min="0" step="1" value={quantity} onChange={(e) => setQuantity(e.target.value)} />
            <TextInput id="line-item-uom" label="UOM" value={uom} onChange={(e) => setUom(e.target.value)} />
            <TextInput id="line-item-unit-price" label="Unit price" type="number" min="0" step="0.01" value={unitPrice} onChange={(e) => setUnitPrice(e.target.value)} />
            <TextInput id="line-item-currency" label="Currency" value={currency} onChange={(e) => setCurrency(e.target.value)} />
          </div>
          <TextAreaInput id="line-item-specifications" label="Specifications" value={specifications} onChange={(e) => setSpecifications(e.target.value)} />
          <div className="flex items-center justify-end gap-2 pt-2">
            <Button type="button" variant="ghost" onClick={onClose} disabled={createLineItem.isPending}>
              Cancel
            </Button>
            <Button type="submit" variant="primary" loading={createLineItem.isPending} disabled={!isWritable}>
              Save line item
            </Button>
          </div>
        </form>
      </div>
    </>
  );
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `cd apps/atomy-q/WEB && npx vitest run src/app/(dashboard)/rfqs/[rfqId]/line-items/line-item-drawer.test.tsx`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/line-items/line-item-drawer.tsx apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/line-items/line-item-drawer.test.tsx
git commit -m "feat: add rfq line item drawer"
```

### Task 3: Wire the page actions and refresh behavior

**Files:**
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/line-items/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/line-items/page.test.tsx`

**Purpose:** Add the CTA to the header and empty state, open the drawer, and refresh the list after a successful save.

- [ ] **Step 1: Write the failing test**

```ts
import React from 'react';
import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen } from '@testing-library/react';
import { renderWithProviders } from '@/test/utils';

const mockUseRfq = vi.fn();
const mockUseRfqLineItems = vi.fn();

vi.mock('@/hooks/use-rfq', () => ({
  useRfq: (...args: unknown[]) => mockUseRfq(...args),
}));

vi.mock('@/hooks/use-rfq-line-items', () => ({
  useRfqLineItems: (...args: unknown[]) => mockUseRfqLineItems(...args),
}));

import { RfqLineItemsPageContent } from './page';

describe('RfqLineItemsPage', () => {
  it('shows add line item actions for draft RFQs and opens the drawer', () => {
    mockUseRfq.mockReturnValue({
      data: { title: 'New Requisition', status: 'draft' },
      isLoading: false,
      isError: false,
      error: null,
    });
    mockUseRfqLineItems.mockReturnValue({
      data: [],
      isLoading: false,
      isError: false,
      error: null,
    });

    renderWithProviders(<RfqLineItemsPageContent rfqId="rfq-new-1" />);

    fireEvent.click(screen.getByRole('button', { name: /add line item/i }));
    expect(screen.getByRole('heading', { name: /add line item/i })).toBeInTheDocument();
  });
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `cd apps/atomy-q/WEB && npx vitest run src/app/(dashboard)/rfqs/[rfqId]/line-items/page.test.tsx`

Expected: fail because the CTA and drawer are not wired yet.

- [ ] **Step 3: Write the minimal implementation**

```tsx
'use client';

import React from 'react';
import { Plus, LayoutGrid, Package, Table2 } from 'lucide-react';
import { useQueryClient } from '@tanstack/react-query';

import { Button } from '@/components/ds/Button';
import { EmptyState, SectionCard, Card } from '@/components/ds/Card';
import { DataTable, type ColumnDef } from '@/components/ds/DataTable';
import { PageHeader } from '@/components/ds/FilterBar';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { useRfq } from '@/hooks/use-rfq';
import { useRfqLineItems, type RfqLineItemRow } from '@/hooks/use-rfq-line-items';
import { LineItemDrawer } from './line-item-drawer';

export function RfqLineItemsPageContent({ rfqId }: { rfqId: string }) {
  const queryClient = useQueryClient();
  const { data: rfq, isError: rfqIsError, error: rfqError } = useRfq(rfqId);
  const { data: lineItems = [], isLoading: lineItemsIsLoading, isError: lineItemsIsError, error: lineItemsError } = useRfqLineItems(rfqId);
  const [viewMode, setViewMode] = React.useState<'table' | 'grid'>('table');
  const [drawerOpen, setDrawerOpen] = React.useState(false);
  const canCreate = rfq?.status === 'draft';
  const isWritable = process.env.NEXT_PUBLIC_USE_MOCKS !== 'true';

  const closeDrawer = React.useCallback(() => setDrawerOpen(false), []);

  const handleCreated = React.useCallback(async () => {
    await queryClient.invalidateQueries({ queryKey: ['rfqs', rfqId, 'line-items'] });
  }, [queryClient, rfqId]);

  const viewToggle = (
    <div className="flex items-center gap-0.5 rounded-lg border border-slate-200 p-0.5 bg-slate-50/80">
      <button
        type="button"
        onClick={() => setViewMode('table')}
        title="Table view"
        className={['rounded-md p-1.5 transition-colors', viewMode === 'table' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-100'].join(' ')}
      >
        <Table2 size={16} />
      </button>
      <button
        type="button"
        onClick={() => setViewMode('grid')}
        title="Grid view"
        className={['rounded-md p-1.5 transition-colors', viewMode === 'grid' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-100'].join(' ')}
      >
        <LayoutGrid size={16} />
      </button>
    </div>
  );

  const addLineItemAction = (
    <Button size="sm" variant="primary" onClick={() => setDrawerOpen(true)}>
      <Plus size={14} className="mr-1.5" />
      Add line item
    </Button>
  );

  const emptyState = canCreate ? (
    <EmptyState
      icon={<Package size={20} />}
      title="No line items"
      description="Add line items to define the scope of this RFQ."
      action={addLineItemAction}
    />
  ) : (
    <EmptyState icon={<Package size={20} />} title="No line items" description="Add line items to define the scope of this RFQ." />
  );

  return (
    <div className="space-y-5">
      <WorkspaceBreadcrumbs
        items={[
          { label: 'RFQs', href: '/rfqs' },
          { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
          { label: 'Line Items' },
        ]}
      />
      <PageHeader
        title="Line items"
        subtitle={rfq?.status === 'draft' ? 'Editable because the RFQ is still in draft' : 'Read-only operational view of target line items'}
        actions={canCreate ? (
          <div className="flex items-center gap-2">
            {addLineItemAction}
            {viewToggle}
          </div>
        ) : (
          viewToggle
        )}
      />

      <SectionCard title="Requested items" subtitle="Structured evaluation baseline">
        {lineItemsIsLoading ? (
          <div className="flex items-center justify-center py-8 text-slate-400 text-sm">Loading line items...</div>
        ) : viewMode === 'table' ? (
          <DataTable
            columns={columns}
            rows={lineItems}
            emptyState={emptyState}
          />
        ) : lineItems.length === 0 ? (
          emptyState
        ) : (
          <div className="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4">
            {lineItems.map((item) => (
              item.rowType === 'heading' ? (
                <Card key={item.id} padding="sm" className="col-span-full border-slate-200 bg-slate-50/90">
                  <div className="flex items-center justify-between gap-3">
                    <div>
                      <p className="text-sm font-semibold text-slate-800">{item.section ?? item.description}</p>
                      <p className="text-xs text-slate-500">Section {item.sort_order}</p>
                    </div>
                    <span className="rounded-full bg-white px-2 py-0.5 text-[10px] font-medium text-slate-500 border border-slate-200">Section</span>
                  </div>
                </Card>
              ) : (
                <Card key={item.id} padding="md" className="border border-slate-200">
                  <div className="space-y-1">
                    <div className="flex items-start justify-between gap-2">
                      <p className="text-sm font-semibold text-slate-800 leading-tight">{item.description}</p>
                      <span className="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-500">{item.sort_order}</span>
                    </div>
                    {item.specifications ? <p className="text-xs text-slate-500">{item.specifications}</p> : null}
                  </div>

                  <div className="grid grid-cols-2 gap-2 mt-3">
                    <div className="rounded border border-slate-200 px-2 py-1">
                      <p className="text-[10px] uppercase text-slate-400">Qty</p>
                      <p className="text-xs text-slate-700 tabular-nums">{item.quantity}</p>
                    </div>
                    <div className="rounded border border-slate-200 px-2 py-1">
                      <p className="text-[10px] uppercase text-slate-400">Unit</p>
                      <p className="text-xs text-slate-700">{item.uom}</p>
                    </div>
                    <div className="rounded border border-slate-200 px-2 py-1">
                      <p className="text-[10px] uppercase text-slate-400">Unit price</p>
                      <p className="text-xs text-slate-700 tabular-nums">{item.currency} {item.unit_price}</p>
                    </div>
                    <div className="rounded border border-slate-200 px-2 py-1">
                      <p className="text-[10px] uppercase text-slate-400">Total</p>
                      <p className="text-xs font-semibold text-slate-800 tabular-nums">{item.currency} {item.unit_price * item.quantity}</p>
                    </div>
                  </div>
                </Card>
              )
            ))}
          </div>
        )}
      </SectionCard>

      <LineItemDrawer
        rfqId={rfqId}
        open={drawerOpen}
        onClose={closeDrawer}
        onCreated={handleCreated}
        isWritable={isWritable}
      />
    </div>
  );
}
```

- [ ] **Step 4: Run the targeted page tests and adjust the implementation**

Run:
`cd apps/atomy-q/WEB && npx vitest run src/app/(dashboard)/rfqs/[rfqId]/line-items/page.test.tsx src/app/(dashboard)/rfqs/[rfqId]/line-items/line-item-drawer.test.tsx src/hooks/use-create-rfq-line-item.test.ts`

Expected: PASS.

- [ ] **Step 5: Run the broader frontend verification**

Run:
`cd apps/atomy-q/WEB && npm run build && npm run lint`

Expected: both commands succeed.

- [ ] **Step 6: Update implementation summary if the drawer ships**

Add a short note to:
- `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`

Mention:
- the line-items screen now has a header CTA and inline drawer,
- line-item creation uses the live API mutation,
- mock mode blocks persistence with an explicit message.

- [ ] **Step 7: Commit**

```bash
git add apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/line-items/page.tsx apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/line-items/page.test.tsx apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md
git commit -m "feat: add rfq line item inline drawer"
```

## Coverage Check

This plan covers the spec as follows:
- visible header CTA: Task 3
- empty-state CTA: Task 3
- inline drawer behavior: Task 2
- create API integration: Task 1 and Task 3
- mock-mode blocking: Task 1 and Task 2
- tests for CTA, drawer, and submit flow: Tasks 1 through 3
- read-only non-draft behavior: Task 3

## Self-Review

- Placeholder scan: none
- Signature consistency: `useCreateRfqLineItem`, `LineItemDrawer`, and `RfqLineItemsPageContent` are used consistently across tasks
- Scope check: stays inside the RFQ line-items screen and the existing create endpoint
- Ambiguity check: drawer is the only creation surface; no new route is introduced

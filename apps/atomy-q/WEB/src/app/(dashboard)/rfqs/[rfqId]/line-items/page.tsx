'use client';

import React from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { AlertTriangle, LayoutGrid, Package, Plus, Table2 } from 'lucide-react';

import { PageHeader } from '@/components/ds/FilterBar';
import { Button } from '@/components/ds/Button';
import { Card, EmptyState, SectionCard } from '@/components/ds/Card';
import { DataTable, type ColumnDef } from '@/components/ds/DataTable';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { useRfq } from '@/hooks/use-rfq';
import { useRfqLineItems, type RfqLineItemRow } from '@/hooks/use-rfq-line-items';

import { LineItemDrawer } from './line-item-drawer';

type ViewMode = 'table' | 'grid';

function formatMoney(amount: number, currency: string): string {
  try {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency,
      maximumFractionDigits: 2,
    }).format(amount);
  } catch {
    return `${currency} ${amount.toLocaleString()}`;
  }
}

export function RfqLineItemsPageContent({ rfqId }: { rfqId: string }) {
  const queryClient = useQueryClient();
  const { data: rfq, isError: rfqIsError, error: rfqError } = useRfq(rfqId);
  const { data: lineItems = [], isLoading: lineItemsIsLoading, isError: lineItemsIsError, error: lineItemsError } = useRfqLineItems(rfqId);
  const [viewMode, setViewMode] = React.useState<ViewMode>('table');
  const [drawerOpen, setDrawerOpen] = React.useState(false);

  const isWritable = rfq?.status === 'draft';
  const canPersist = isWritable && process.env.NEXT_PUBLIC_USE_MOCKS !== 'true';

  const handleCreated = React.useCallback(async () => {
    await queryClient.invalidateQueries({ queryKey: ['rfqs', rfqId, 'line-items'] });
  }, [queryClient, rfqId]);

  const breadcrumbItems = [
    { label: 'RFQs', href: '/rfqs' },
    { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
    { label: 'Line Items' },
  ];

  const columns: ColumnDef<RfqLineItemRow>[] = [
    {
      key: 'rowType',
      label: 'Type',
      width: '90px',
      render: (row) => (
        <span className={['text-xs font-medium', row.rowType === 'heading' ? 'text-slate-500 uppercase tracking-wide' : 'text-slate-600'].join(' ')}>
          {row.rowType === 'heading' ? 'Section' : 'Line'}
        </span>
      ),
    },
    {
      key: 'description',
      label: 'Description',
      render: (row) => (
        row.rowType === 'heading' ? (
          <span className="text-sm font-semibold text-slate-800">{row.section ?? row.description}</span>
        ) : (
          <div className="space-y-0.5">
            <p className="text-sm font-medium text-slate-800">{row.description}</p>
            {row.specifications ? <p className="text-xs text-slate-500">{row.specifications}</p> : null}
          </div>
        )
      ),
    },
    {
      key: 'quantity',
      label: 'Qty',
      width: '90px',
      align: 'right',
      render: (row) =>
        row.rowType === 'heading' ? <span className="text-slate-400">—</span> : <span className="text-sm tabular-nums text-slate-700">{row.quantity}</span>,
    },
    {
      key: 'uom',
      label: 'Unit',
      width: '100px',
      render: (row) => (row.rowType === 'heading' ? <span className="text-slate-400">—</span> : <span className="text-sm text-slate-600">{row.uom}</span>),
    },
    {
      key: 'unit_price',
      label: 'Unit price',
      width: '130px',
      align: 'right',
      render: (row) =>
        row.rowType === 'heading' ? (
          <span className="text-slate-400">—</span>
        ) : (
          <span className="text-sm tabular-nums text-slate-700">{formatMoney(row.unit_price, row.currency)}</span>
        ),
    },
    {
      key: 'total',
      label: 'Total',
      width: '140px',
      align: 'right',
      render: (row) =>
        row.rowType === 'heading' ? (
          <span className="text-slate-400">—</span>
        ) : (
          <span className="text-sm font-semibold tabular-nums text-slate-800">{formatMoney(row.unit_price * row.quantity, row.currency)}</span>
        ),
    },
    {
      key: 'sort_order',
      label: 'Sort',
      width: '80px',
      align: 'right',
      render: (row) => <span className="text-xs text-slate-500">{row.sort_order}</span>,
    },
  ];

  if (rfqIsError) {
    const errorMessage = rfqError instanceof Error ? rfqError.message : 'RFQ data is unavailable.';
    return (
      <div className="space-y-5">
        <WorkspaceBreadcrumbs items={breadcrumbItems} />
        <PageHeader title="Line items" subtitle="RFQ unavailable" />
        <SectionCard title="RFQ unavailable">
          <EmptyState
            icon={<AlertTriangle size={20} />}
            title="Could not load RFQ context"
            description={errorMessage}
          />
        </SectionCard>
      </div>
    );
  }

  if (lineItemsIsError) {
    const errorMessage =
      lineItemsError instanceof Error ? lineItemsError.message : 'The live line-item list could not be loaded.';
    return (
      <div className="space-y-5">
        <WorkspaceBreadcrumbs items={breadcrumbItems} />
        <PageHeader title="Line items" subtitle="Line-item data unavailable" />
        <SectionCard title="Line-item data unavailable">
          <EmptyState
            icon={<AlertTriangle size={20} />}
            title="Could not load line items"
            description={errorMessage}
          />
        </SectionCard>
      </div>
    );
  }

  return (
    <div className="space-y-5">
      <WorkspaceBreadcrumbs items={breadcrumbItems} />
      <PageHeader
        title="Line items"
        subtitle={
          rfq?.status === 'draft'
            ? 'Editable because the RFQ is still in draft'
            : 'Read-only operational view of target line items'
        }
        actions={
          <div className="flex items-center gap-2">
            {isWritable && (
              <Button size="sm" onClick={() => setDrawerOpen(true)}>
                <Plus size={14} className="mr-1" />
                Add line item
              </Button>
            )}
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
          </div>
        }
      />

      <SectionCard title="Requested items" subtitle="Structured evaluation baseline">
        {lineItemsIsLoading ? (
          <div className="flex items-center justify-center py-8 text-slate-400 text-sm">Loading line items...</div>
        ) : viewMode === 'table' ? (
          <DataTable<RfqLineItemRow>
            columns={columns}
            rows={lineItems}
            rowClassName={(row) => (row.rowType === 'heading' ? 'bg-slate-50/80' : '')}
            emptyState={
            <EmptyState
              icon={<Package size={20} />}
              title="No line items"
              description="Add line items to define the scope of this RFQ."
              action={isWritable ? <Button size="sm" onClick={() => setDrawerOpen(true)}>Add line item</Button> : undefined}
            />
          }
        />
        ) : lineItems.length === 0 ? (
          <EmptyState
            icon={<Package size={20} />}
            title="No line items"
            description="Add line items to define the scope of this RFQ."
            action={isWritable ? <Button size="sm" onClick={() => setDrawerOpen(true)}>Add line item</Button> : undefined}
          />
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
                    <span className="rounded-full bg-white px-2 py-0.5 text-[10px] font-medium text-slate-500 border border-slate-200">
                      Section
                    </span>
                  </div>
                </Card>
              ) : (
                <Card key={item.id} padding="md" className="border border-slate-200">
                  <div className="space-y-1">
                    <div className="flex items-start justify-between gap-2">
                      <p className="text-sm font-semibold text-slate-800 leading-tight">{item.description}</p>
                      <span className="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-500">
                        {item.sort_order}
                      </span>
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
                      <p className="text-xs text-slate-700 tabular-nums">{formatMoney(item.unit_price, item.currency)}</p>
                    </div>
                    <div className="rounded border border-slate-200 px-2 py-1">
                      <p className="text-[10px] uppercase text-slate-400">Total</p>
                      <p className="text-xs font-semibold text-slate-800 tabular-nums">{formatMoney(item.unit_price * item.quantity, item.currency)}</p>
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
        onClose={() => setDrawerOpen(false)}
        onCreated={handleCreated}
        isWritable={canPersist}
      />
    </div>
  );
}

export default function RfqLineItemsPage({ params }: { params: Promise<{ rfqId: string }> }) {
  const { rfqId } = React.use(params);
  return <RfqLineItemsPageContent rfqId={rfqId} />;
}

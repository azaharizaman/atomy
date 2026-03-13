'use client';

import React from 'react';
import { PageHeader } from '@/components/ds/FilterBar';
import { SectionCard, Card } from '@/components/ds/Card';
import { DataTable, type ColumnDef } from '@/components/ds/DataTable';
import { EmptyState } from '@/components/ds/Card';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { useRfq } from '@/hooks/use-rfq';
import { Table2, LayoutGrid, Package } from 'lucide-react';

type LineItemRow = {
  id: string;
  heading: string;
  description: string;
  qty: number;
  unit: string;
  targetPrice: number;
  entryType: 'heading' | 'line';
  category?: string;
};

const MOCK_LINE_ITEMS: LineItemRow[] = [
  { id: '1', heading: 'Servers', description: '', qty: 0, unit: '', targetPrice: 0, entryType: 'heading', category: undefined },
  { id: '2', heading: '', description: 'Dell PowerEdge R750', qty: 12, unit: 'units', targetPrice: 4200, entryType: 'line', category: 'IT Hardware' },
  { id: '3', heading: '', description: 'Dell PowerEdge R650', qty: 6, unit: 'units', targetPrice: 3100, entryType: 'line', category: 'IT Hardware' },
  { id: '4', heading: '', description: 'HPE ProLiant DL380 Gen10', qty: 4, unit: 'units', targetPrice: 3800, entryType: 'line', category: 'IT Hardware' },
  { id: '5', heading: 'Storage', description: '', qty: 0, unit: '', targetPrice: 0, entryType: 'heading', category: undefined },
  { id: '6', heading: '', description: 'SAN Array 50TB', qty: 2, unit: 'units', targetPrice: 28500, entryType: 'line', category: 'Storage' },
  { id: '7', heading: '', description: 'NAS 24-bay 10Gb', qty: 1, unit: 'units', targetPrice: 12000, entryType: 'line', category: 'Storage' },
  { id: '8', heading: 'Network', description: '', qty: 0, unit: '', targetPrice: 0, entryType: 'heading', category: undefined },
  { id: '9', heading: '', description: 'Cisco Catalyst 9300-48P', qty: 8, unit: 'units', targetPrice: 14500, entryType: 'line', category: 'Network' },
  { id: '10', heading: '', description: '10Gb SFP+ Transceivers', qty: 24, unit: 'units', targetPrice: 320, entryType: 'line', category: 'Network' },
];

type ViewMode = 'table' | 'grid';

export default function RfqLineItemsPage({ params }: { params: Promise<{ rfqId: string }> }) {
  const { rfqId } = React.use(params);
  const { data: rfq } = useRfq(rfqId);
  const [viewMode, setViewMode] = React.useState<ViewMode>('table');

  const breadcrumbItems = [
    { label: 'RFQs', href: '/rfqs' },
    { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
    { label: 'Line Items' },
  ];

  const columns: ColumnDef<LineItemRow>[] = [
    {
      key: 'entryType',
      label: 'Type',
      width: '90px',
      render: (row) => (
        <span className={['text-xs font-medium', row.entryType === 'heading' ? 'text-slate-500 uppercase tracking-wide' : 'text-slate-600'].join(' ')}>
          {row.entryType === 'heading' ? 'Section' : 'Line'}
        </span>
      ),
    },
    {
      key: 'description',
      label: 'Description',
      render: (row) => (
        <span className={['text-sm', row.entryType === 'heading' ? 'font-semibold text-slate-800' : 'text-slate-700'].join(' ')}>
          {row.entryType === 'heading' ? row.heading : row.description}
        </span>
      ),
    },
    {
      key: 'category',
      label: 'Category',
      width: '120px',
      render: (row) => <span className="text-xs text-slate-500">{row.entryType === 'line' && row.category ? row.category : '—'}</span>,
    },
    {
      key: 'qty',
      label: 'Qty',
      width: '80px',
      align: 'right',
      render: (row) => (row.entryType === 'line' ? <span className="text-sm tabular-nums text-slate-700">{row.qty}</span> : <span className="text-slate-400">—</span>),
    },
    {
      key: 'unit',
      label: 'Unit',
      width: '80px',
      render: (row) => (row.entryType === 'line' && row.unit ? <span className="text-sm text-slate-600">{row.unit}</span> : <span className="text-slate-400">—</span>),
    },
    {
      key: 'targetPrice',
      label: 'Unit price',
      width: '110px',
      align: 'right',
      render: (row) =>
        row.entryType === 'line' ? (
          <span className="text-sm tabular-nums text-slate-700">${row.targetPrice.toLocaleString()}</span>
        ) : (
          <span className="text-slate-400">—</span>
        ),
    },
    {
      key: 'total',
      label: 'Total',
      width: '120px',
      align: 'right',
      render: (row) =>
        row.entryType === 'line' ? (
          <span className="text-sm font-semibold tabular-nums text-slate-800">${(row.targetPrice * row.qty).toLocaleString()}</span>
        ) : (
          <span className="text-slate-400">—</span>
        ),
    },
  ];

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
        }
      />
      <SectionCard title="Requested items" subtitle="Structured evaluation baseline">
        {viewMode === 'table' ? (
          <DataTable<LineItemRow>
            columns={columns}
            rows={MOCK_LINE_ITEMS}
            rowClassName={(row) => (row.entryType === 'heading' ? 'bg-slate-50/70' : '')}
            emptyState={
              <EmptyState
                icon={<Package size={20} />}
                title="No line items"
                description="Add line items to define the scope of this RFQ."
              />
            }
          />
        ) : (
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            {MOCK_LINE_ITEMS.map((item) =>
              item.entryType === 'heading' ? (
                <Card key={item.id} padding="sm" className="col-span-full border-slate-200 bg-slate-50/80">
                  <p className="text-sm font-semibold text-slate-800">{item.heading}</p>
                </Card>
              ) : (
                <Card key={item.id} padding="md" className="border border-slate-200">
                  <div className="flex items-start justify-between gap-2">
                    <p className="text-sm font-semibold text-slate-800 leading-tight">{item.description}</p>
                  </div>
                  <div className="grid grid-cols-2 gap-2 mt-3">
                    <div className="rounded border border-slate-200 px-2 py-1">
                      <p className="text-[10px] uppercase text-slate-400">Category</p>
                      <p className="text-xs text-slate-700">{item.category ?? '—'}</p>
                    </div>
                    <div className="rounded border border-slate-200 px-2 py-1">
                      <p className="text-[10px] uppercase text-slate-400">Qty</p>
                      <p className="text-xs text-slate-700">{item.qty}</p>
                    </div>
                    <div className="rounded border border-slate-200 px-2 py-1">
                      <p className="text-[10px] uppercase text-slate-400">Unit</p>
                      <p className="text-xs text-slate-700">{item.unit}</p>
                    </div>
                    <div className="rounded border border-slate-200 px-2 py-1">
                      <p className="text-[10px] uppercase text-slate-400">Unit price</p>
                      <p className="text-xs text-slate-700">${item.targetPrice.toLocaleString()}</p>
                    </div>
                    <div className="rounded border border-slate-200 px-2 py-1 col-span-2">
                      <p className="text-[10px] uppercase text-slate-400">Total</p>
                      <p className="text-xs font-semibold text-slate-800">${(item.targetPrice * item.qty).toLocaleString()}</p>
                    </div>
                  </div>
                </Card>
              )
            )}
          </div>
        )}
      </SectionCard>
    </div>
  );
}

'use client';

import React from 'react';
import { CheckCircle2, Upload, BarChart2, Send } from 'lucide-react';

import { Card } from '@/components/ds/Card';
import { StatusBadge } from '@/components/ds/Badge';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { useRfq } from '@/hooks/use-rfq';

function Scorecard({
  title,
  value,
  subtext,
  right,
}: {
  title: string;
  value: React.ReactNode;
  subtext?: React.ReactNode;
  right?: React.ReactNode;
}) {
  return (
    <Card padding="md" className="h-24 flex flex-col justify-between">
      <div className="flex items-start justify-between gap-3">
        <div className="text-xs font-medium text-slate-500">{title}</div>
        {right}
      </div>
      <div>
        <div className="text-lg font-bold text-slate-900 leading-tight">{value}</div>
        {subtext && <div className="text-xs text-slate-500 mt-0.5">{subtext}</div>}
      </div>
    </Card>
  );
}

const generateActivity = (rfqId: string) => [
  { id: '1', time: '2 hours ago', actor: 'Alex Kumar', action: 'uploaded quote from Dell Technologies', icon: <Upload size={12} /> },
  { id: '2', time: '4 hours ago', actor: 'Priya Nair', action: 'sent invitation to HP Enterprise', icon: <Send size={12} /> },
  { id: '3', time: 'Yesterday', actor: 'System', action: 'normalization completed (18/24 lines)', icon: <CheckCircle2 size={12} /> },
  { id: '4', time: 'Yesterday', actor: 'Alex Kumar', action: 'ran comparison preview — Run #004', icon: <BarChart2 size={12} /> },
  { id: '5', time: '3 days ago', actor: 'System', action: 'RFQ published and vendors notified', icon: <CheckCircle2 size={12} /> },
  { id: '6', time: '6 days ago', actor: 'Marcus Webb', action: `created ${rfqId}`, icon: <CheckCircle2 size={12} /> },
];

export default function RfqOverviewPage({ params }: { params: { rfqId: string } }) {
  const rfqId = params.rfqId;
  const { data: rfq } = useRfq(rfqId);
  const activity = generateActivity(rfqId);

  return (
    <div className="space-y-4">
      <WorkspaceBreadcrumbs
        items={[
          { label: 'RFQs', href: '/rfqs' },
          { label: rfq?.title ?? 'Requisition' },
          { label: 'Overview' },
        ]}
      />

      <div className="flex items-start justify-between gap-4">
        <div className="min-w-0">
          <div className="flex items-center gap-2">
            <h1 className="text-lg font-semibold text-slate-900 truncate">Overview</h1>
            {rfq?.status && <StatusBadge status={rfq.status as any} />}
          </div>
          <p className="text-sm text-slate-500 mt-0.5">
            {rfq?.id} · {rfq?.title}
          </p>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
        <Scorecard title="Quotes Received" value={`${rfq?.quotesCount ?? 8} / 10 expected`} subtext={<span>On track</span>} right={<div className="text-[10px] font-mono text-slate-400">KPI</div>} />
        <Scorecard title="Normalization" value="75% complete" subtext={<div className="h-1.5 bg-slate-200 rounded-full overflow-hidden mt-1"><div className="h-full bg-indigo-600 rounded-full" style={{ width: '75%' }} /></div>} />
        <Scorecard title="Comparison Status" value={<StatusBadge status="preview" />} subtext="Latest run: RUN-004" />
        <Scorecard title="Approval Status" value={<StatusBadge status="draft" label="None" />} subtext="No gates triggered" />
      </div>

      <Card padding="none">
        <div className="px-4 py-3 border-b border-slate-100">
          <h2 className="text-sm font-semibold text-slate-800">Activity Timeline</h2>
        </div>
        <div className="px-4 py-3">
          <div className="space-y-3">
            {activity.map((e) => (
              <div key={e.id} className="flex gap-3">
                <div className="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 shrink-0">
                  {e.icon}
                </div>
                <div className="min-w-0">
                  <div className="text-xs text-slate-400">{e.time}</div>
                  <div className="text-sm text-slate-700">
                    <span className="font-medium text-slate-900">{e.actor}</span> {e.action}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </Card>
    </div>
  );
}


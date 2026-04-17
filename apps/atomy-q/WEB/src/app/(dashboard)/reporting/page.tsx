'use client';

import React from 'react';
import { BarChart2, Download, TrendingUp } from 'lucide-react';
import { Button } from '@/components/ds/Button';
import { PageHeader } from '@/components/ds/FilterBar';
import { AlphaDeferredScreen } from '@/components/alpha/alpha-deferred-screen';
import { isAlphaMode } from '@/lib/alpha-mode';

export default function ReportingPage() {
  if (isAlphaMode()) {
    return <AlphaDeferredScreen title="Reporting" subtitle="Reporting is deferred in alpha." />;
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Reporting"
        subtitle="KPIs, spend trends, and exportable reports."
        actions={
          <Button size="sm" variant="outline" disabled>
            <Download size={14} className="mr-1.5" />
            Export
          </Button>
        }
      />

      <div className="rounded-lg border border-slate-200 bg-white p-8">
        <div className="flex flex-col items-center justify-center text-center">
          <div className="rounded-full bg-slate-100 p-4">
            <BarChart2 size={32} className="text-slate-500" />
          </div>
          <h2 className="mt-4 text-lg font-semibold text-slate-900">Reports & analytics</h2>
          <p className="mt-2 max-w-sm text-sm text-slate-500">
            View spend by category, vendor scores, and cycle-time reports. Export will be available when the reporting API is connected.
          </p>
          <div className="mt-6 flex gap-2">
            <Button size="sm" variant="outline" disabled>
              <TrendingUp size={14} className="mr-1.5" />
              Spend trend
            </Button>
            <Button size="sm" variant="outline" disabled>
              <BarChart2 size={14} className="mr-1.5" />
              Vendor scores
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
}

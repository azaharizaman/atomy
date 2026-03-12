'use client';

import React from 'react';

import { Card } from '@/components/ds/Card';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';

const SECTION_TITLES: Record<string, string> = {
  details: 'Details',
  'line-items': 'Line Items',
  vendors: 'Vendors',
  award: 'Award',
  'quote-intake': 'Quote Intake',
  'comparison-runs': 'Comparison Runs',
  approvals: 'Approvals',
  negotiations: 'Negotiations',
  documents: 'Documents',
  risk: 'Risk & Compliance',
  'decision-trail': 'Decision Trail',
};

export default function RfqSectionStubPage({ params }: { params: { rfqId: string; section: string } }) {
  const rfqId = decodeURIComponent(params.rfqId);
  const section = decodeURIComponent(params.section);
  const title = SECTION_TITLES[section] ?? section;

  return (
    <div className="space-y-4">
      <WorkspaceBreadcrumbs
        items={[
          { label: 'RFQs', href: '/rfqs' },
          { label: rfqId, href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
          { label: title },
        ]}
      />

      <Card padding="md">
        <h1 className="text-lg font-semibold text-slate-900">{title}</h1>
        <p className="text-sm text-slate-500 mt-1">
          This section is scaffolded for navigation parity with the Screen Blueprint. Implementation will land in the next slices.
        </p>
      </Card>
    </div>
  );
}


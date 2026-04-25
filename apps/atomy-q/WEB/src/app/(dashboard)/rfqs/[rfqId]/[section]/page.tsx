'use client';

import React from 'react';

import { Card } from '@/components/ds/Card';

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

export default function RfqSectionStubPage({ params }: { params: Promise<{ rfqId: string; section: string }> }) {
  const { rfqId, section } = React.use(params);
  const title = SECTION_TITLES[section] ?? section;

  return (
    <div className="space-y-4">
      <Card padding="md">
        <h1 className="text-lg font-semibold text-slate-900">{title}</h1>
        <p className="text-sm text-slate-500 mt-1">
          This section is scaffolded for navigation parity with the Screen Blueprint. Implementation will land in the next slices.
        </p>
      </Card>
    </div>
  );
}

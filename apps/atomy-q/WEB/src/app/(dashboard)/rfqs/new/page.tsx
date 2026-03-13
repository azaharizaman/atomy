'use client';

import React from 'react';
import { Card } from '@/components/ds/Card';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';

export default function NewRfqPage() {
  return (
    <div className="space-y-4">
      <WorkspaceBreadcrumbs
        items={[
          { label: 'RFQs', href: '/rfqs' },
          { label: 'New RFQ' },
        ]}
      />

      <Card padding="md">
        <h1 className="text-lg font-semibold text-slate-900">Create New RFQ</h1>
        <p className="text-sm text-slate-500 mt-1">
          This creation form is scaffolded. Implementation will land in the next slices.
        </p>
      </Card>
    </div>
  );
}

'use client';

import React from 'react';
import { FolderArchive, FileText, Upload } from 'lucide-react';
import { Button } from '@/components/ds/Button';
import { PageHeader } from '@/components/ds/FilterBar';

export default function DocumentsPage() {
  return (
    <div className="space-y-6">
      <PageHeader
        title="Documents"
        subtitle="Manage RFQ documents, evidence bundles, and attachments."
        actions={
          <Button size="sm" variant="primary">
            <Upload size={14} className="mr-1.5" />
            Upload
          </Button>
        }
      />

      <div className="rounded-lg border border-slate-200 bg-white p-8">
        <div className="flex flex-col items-center justify-center text-center">
          <div className="rounded-full bg-slate-100 p-4">
            <FolderArchive size={32} className="text-slate-500" />
          </div>
          <h2 className="mt-4 text-lg font-semibold text-slate-900">Documents library</h2>
          <p className="mt-2 max-w-sm text-sm text-slate-500">
            Upload and organize documents by RFQ. Evidence bundles and attachments will appear here once the API is connected.
          </p>
          <div className="mt-6 flex gap-2">
            <Button size="sm" variant="outline" disabled>
              <FileText size={14} className="mr-1.5" />
              Browse by RFQ
            </Button>
            <Button size="sm" variant="primary" disabled>
              <Upload size={14} className="mr-1.5" />
              Upload document
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
}

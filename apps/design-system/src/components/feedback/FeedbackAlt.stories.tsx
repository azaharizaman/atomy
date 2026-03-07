import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { AtomyQStatusBadgeAlt } from '../basic/AtomyQStatusBadgeAlt';
import { AtomyQAIInsightCardAlt } from './AtomyQAIInsightCardAlt';
import { AtomyQUploadZoneAlt } from './AtomyQUploadZoneAlt';
import {
  AtomyQOversightPanelAlt,
  type ChecklistItemAlt,
  type StakeholderFeedbackAlt,
} from './AtomyQOversightPanelAlt';

const meta: Meta = {
  title: 'Components/Feedback/Feedback Alt',
  parameters: { layout: 'padded' },
};

export default meta;

export const StatusBadges: StoryObj = {
  name: 'Status Badges Alt',
  render: () => (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center gap-2">
        <AtomyQStatusBadgeAlt tone="success" dot>Awarded</AtomyQStatusBadgeAlt>
        <AtomyQStatusBadgeAlt tone="info" dot>Open</AtomyQStatusBadgeAlt>
        <AtomyQStatusBadgeAlt tone="warning" dot>Pending</AtomyQStatusBadgeAlt>
        <AtomyQStatusBadgeAlt tone="danger" dot>Cancelled</AtomyQStatusBadgeAlt>
        <AtomyQStatusBadgeAlt tone="neutral" dot>Draft</AtomyQStatusBadgeAlt>
        <AtomyQStatusBadgeAlt tone="brand" dot>In Review</AtomyQStatusBadgeAlt>
      </div>
      <div className="flex flex-wrap items-center gap-2">
        <AtomyQStatusBadgeAlt tone="success">Approved</AtomyQStatusBadgeAlt>
        <AtomyQStatusBadgeAlt tone="warning">Flagged</AtomyQStatusBadgeAlt>
        <AtomyQStatusBadgeAlt tone="danger">Rejected</AtomyQStatusBadgeAlt>
        <AtomyQStatusBadgeAlt tone="neutral">Unknown</AtomyQStatusBadgeAlt>
      </div>
    </div>
  ),
};

export const AIInsightCard: StoryObj = {
  name: 'AI Insight Card Alt',
  render: () => (
    <div className="max-w-md space-y-4">
      <AtomyQAIInsightCardAlt title="AI Agent Summary">
        Based on current quote data, <strong>Vendor B</strong> offers the best value
        across price, quality, and delivery metrics. Risk assessment indicates low
        exposure with all compliance checks passed.
      </AtomyQAIInsightCardAlt>

      <AtomyQAIInsightCardAlt
        title="Anomaly Detected"
        actions={
          <button className="rounded-md bg-[var(--aq-purple-tint-12)] px-2.5 py-1 text-[11px] font-medium text-[var(--aq-purple-500)]">
            Review Details
          </button>
        }
      >
        Vendor C's unit price for line item #3 is 42% above market average.
        This may indicate a pricing error or premium specification.
      </AtomyQAIInsightCardAlt>
    </div>
  ),
};

export const UploadZone: StoryObj = {
  name: 'Upload Zone Alt',
  render: () => (
    <div className="max-w-lg">
      <AtomyQUploadZoneAlt
        accept=".pdf,.xlsx,.csv,.doc,.docx"
        maxSizeMB={50}
        description="PDF, Excel, CSV, or Word — up to 50MB per file"
        onFilesSelected={(files) => console.log('Files selected:', files)}
      />
    </div>
  ),
};

export const OversightPanel: StoryObj = {
  name: 'Oversight Panel Alt',
  render: function Render() {
    const [checklist, setChecklist] = useState<ChecklistItemAlt[]>([
      { id: '1', label: 'Verify pricing against market benchmarks', checked: true },
      { id: '2', label: 'Confirm vendor compliance certificates', checked: true },
      { id: '3', label: 'Review delivery timeline feasibility', checked: false },
      { id: '4', label: 'Validate insurance and bonding', checked: false },
      { id: '5', label: 'Approve final recommendation', checked: false },
    ]);

    const stakeholders: StakeholderFeedbackAlt[] = [
      { name: 'Sarah Chen', initials: 'SC', status: 'approved', comment: 'Looks good. Vendor B aligns with our strategy.' },
      { name: 'James Lee', initials: 'JL', status: 'approved' },
      { name: 'Aisha Patel', initials: 'AP', status: 'flagged', comment: 'Delivery timeline seems tight for Q4.' },
      { name: 'Mike Tan', initials: 'MT', status: 'pending' },
    ];

    const toggleCheck = (id: string) => {
      setChecklist((prev) =>
        prev.map((c) => (c.id === id ? { ...c, checked: !c.checked } : c)),
      );
    };

    return (
      <div className="flex h-[700px] border border-[var(--aq-border-default)] rounded-xl overflow-hidden">
        <div className="flex-1 bg-[var(--aq-bg-canvas)] p-6 text-[13px] text-[var(--aq-text-muted)]">
          Main content area
        </div>
        <AtomyQOversightPanelAlt
          title="Decision Oversight"
          verdictLabel="AI Recommendation"
          verdictValue="Award to Vendor B"
          verdictDescription="Highest composite score (87.4) with acceptable risk profile"
          checklist={checklist}
          onChecklistToggle={toggleCheck}
          stakeholders={stakeholders}
          ctaLabel="Submit Decision"
          onCtaClick={() => {}}
        />
      </div>
    );
  },
};

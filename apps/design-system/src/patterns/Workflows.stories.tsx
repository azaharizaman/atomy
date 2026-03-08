import type { Meta, StoryObj } from '@storybook/react-vite';
import { AtomyQStepper, type StepItem } from '../components/navigation/AtomyQStepper';
import { AtomyQBadge } from '../components/basic/AtomyQBadge';

const meta: Meta = {
  title: 'Patterns/Workflow Patterns',
  parameters: { layout: 'padded' },
};

export default meta;

const procurementWorkflow: StepItem[] = [
  { id: '1', label: 'Create RFQ', status: 'completed', description: 'Define requirements and specifications' },
  { id: '2', label: 'Invite Vendors', status: 'completed', description: 'Select and notify qualified vendors' },
  { id: '3', label: 'Collect Quotes', status: 'completed', description: 'AI parses and normalises vendor quotes' },
  { id: '4', label: 'AI Comparison', status: 'active', description: 'Automated scoring and ranking' },
  { id: '5', label: 'Review & Approve', status: 'pending', description: 'Manager reviews and approves recommendation' },
  { id: '6', label: 'Award & PO', status: 'pending', description: 'Generate purchase order for selected vendor' },
];

const approvalWorkflow: StepItem[] = [
  { id: '1', label: 'Draft', status: 'completed' },
  { id: '2', label: 'Submitted', status: 'completed' },
  { id: '3', label: 'Level 1 Approval', status: 'completed' },
  { id: '4', label: 'Level 2 Approval', status: 'active' },
  { id: '5', label: 'Approved', status: 'pending' },
];

const errorWorkflow: StepItem[] = [
  { id: '1', label: 'Upload Quote', status: 'completed' },
  { id: '2', label: 'AI Parsing', status: 'completed' },
  { id: '3', label: 'Validation', status: 'error', description: '3 line items missing unit prices' },
  { id: '4', label: 'Accepted', status: 'pending' },
];

export const WorkflowPatterns: StoryObj = {
  render: () => (
    <div className="max-w-3xl space-y-10">
      <div>
        <h2 className="text-xl font-semibold text-[var(--aq-text-primary)] mb-1">Workflow Patterns</h2>
        <p className="text-sm text-[var(--aq-text-muted)] mb-6">
          ERP systems have repeatable workflows. Developers should reuse these patterns across modules for consistency.
        </p>
      </div>

      {/* Procurement Workflow */}
      <div>
        <h3 className="text-sm font-semibold uppercase tracking-wider text-[var(--aq-text-muted)] mb-4">
          Full Procurement Workflow
        </h3>
        <div className="p-6 rounded-lg border border-[var(--aq-border-default)] bg-white">
          <AtomyQStepper steps={procurementWorkflow} orientation="vertical" />
        </div>
      </div>

      {/* Approval Workflow */}
      <div>
        <h3 className="text-sm font-semibold uppercase tracking-wider text-[var(--aq-text-muted)] mb-4">
          Multi-Level Approval
        </h3>
        <div className="p-6 rounded-lg border border-[var(--aq-border-default)] bg-white">
          <AtomyQStepper steps={approvalWorkflow} orientation="horizontal" />
        </div>
      </div>

      {/* Error Workflow */}
      <div>
        <h3 className="text-sm font-semibold uppercase tracking-wider text-[var(--aq-text-muted)] mb-4">
          Workflow with Error State
        </h3>
        <div className="p-6 rounded-lg border border-[var(--aq-border-default)] bg-white">
          <AtomyQStepper steps={errorWorkflow} orientation="horizontal" />
        </div>
      </div>

      {/* Standard Workflows */}
      <div>
        <h3 className="text-sm font-semibold uppercase tracking-wider text-[var(--aq-text-muted)] mb-4">Standard Workflow Templates</h3>
        <div className="border border-[var(--aq-border-default)] rounded-lg overflow-hidden">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-[var(--aq-bg-elevated)]">
                <th className="px-4 py-2 text-left text-xs font-semibold uppercase text-[var(--aq-text-muted)]">Workflow</th>
                <th className="px-4 py-2 text-left text-xs font-semibold uppercase text-[var(--aq-text-muted)]">Steps</th>
                <th className="px-4 py-2 text-left text-xs font-semibold uppercase text-[var(--aq-text-muted)]">Module</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-[var(--aq-border-subtle)]">
              <tr>
                <td className="px-4 py-3 font-medium">RFQ Lifecycle</td>
                <td className="px-4 py-3">
                  <div className="flex items-center gap-1 text-xs">
                    <AtomyQBadge variant="neutral">Draft</AtomyQBadge> → <AtomyQBadge variant="info">Open</AtomyQBadge> → <AtomyQBadge variant="success">Awarded</AtomyQBadge> → <AtomyQBadge variant="default">Closed</AtomyQBadge>
                  </div>
                </td>
                <td className="px-4 py-3 text-[var(--aq-text-muted)]">RFQ Management</td>
              </tr>
              <tr>
                <td className="px-4 py-3 font-medium">Quote Processing</td>
                <td className="px-4 py-3">
                  <div className="flex items-center gap-1 text-xs">
                    <AtomyQBadge variant="info">Received</AtomyQBadge> → <AtomyQBadge variant="info">Parsing</AtomyQBadge> → <AtomyQBadge variant="success">Accepted</AtomyQBadge>
                  </div>
                </td>
                <td className="px-4 py-3 text-[var(--aq-text-muted)]">Quote Intake</td>
              </tr>
              <tr>
                <td className="px-4 py-3 font-medium">Approval Chain</td>
                <td className="px-4 py-3">
                  <div className="flex items-center gap-1 text-xs">
                    <AtomyQBadge variant="warning">Pending</AtomyQBadge> → <AtomyQBadge variant="success">Approved</AtomyQBadge> / <AtomyQBadge variant="danger">Rejected</AtomyQBadge>
                  </div>
                </td>
                <td className="px-4 py-3 text-[var(--aq-text-muted)]">Approvals</td>
              </tr>
              <tr>
                <td className="px-4 py-3 font-medium">Vendor Invitation</td>
                <td className="px-4 py-3">
                  <div className="flex items-center gap-1 text-xs">
                    <AtomyQBadge variant="neutral">Not Invited</AtomyQBadge> → <AtomyQBadge variant="info">Invited</AtomyQBadge> → <AtomyQBadge variant="success">Responded</AtomyQBadge> / <AtomyQBadge variant="danger">Declined</AtomyQBadge>
                  </div>
                </td>
                <td className="px-4 py-3 text-[var(--aq-text-muted)]">Vendor Management</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  ),
};

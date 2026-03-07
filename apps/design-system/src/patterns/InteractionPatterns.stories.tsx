import type { Meta, StoryObj } from '@storybook/react-vite';
import { AtomyQButton } from '../components/basic/AtomyQButton';
import { AtomyQBadge } from '../components/basic/AtomyQBadge';
import { AtomyQAlert } from '../components/feedback/AtomyQAlert';

const meta: Meta = {
  title: 'Patterns/Interaction Patterns',
  parameters: { layout: 'padded' },
};

export default meta;

export const FormBehaviour: StoryObj = {
  render: () => (
    <div className="max-w-3xl space-y-6">
      <h2 className="text-xl font-semibold text-[var(--aq-text-primary)]">Form Behaviour Patterns</h2>

      <div className="border border-[var(--aq-border-default)] rounded-lg overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <tr className="bg-[var(--aq-bg-elevated)]">
              <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-[var(--aq-text-muted)]">Pattern</th>
              <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-[var(--aq-text-muted)]">Rule</th>
              <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-[var(--aq-text-muted)]">When</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-[var(--aq-border-subtle)]">
            <tr>
              <td className="px-4 py-3 font-medium">Auto-save</td>
              <td className="px-4 py-3 text-[var(--aq-text-muted)]">Save every 30s while user is editing</td>
              <td className="px-4 py-3"><AtomyQBadge variant="info">Draft forms only</AtomyQBadge></td>
            </tr>
            <tr>
              <td className="px-4 py-3 font-medium">Manual save</td>
              <td className="px-4 py-3 text-[var(--aq-text-muted)]">Explicit Save / Submit button required</td>
              <td className="px-4 py-3"><AtomyQBadge variant="warning">Publishing, approvals</AtomyQBadge></td>
            </tr>
            <tr>
              <td className="px-4 py-3 font-medium">Required field</td>
              <td className="px-4 py-3 text-[var(--aq-text-muted)]">Red asterisk (*) + HTML required attribute</td>
              <td className="px-4 py-3"><AtomyQBadge variant="neutral">All mandatory fields</AtomyQBadge></td>
            </tr>
            <tr>
              <td className="px-4 py-3 font-medium">Validation timing</td>
              <td className="px-4 py-3 text-[var(--aq-text-muted)]">Validate on blur first, then on change. Validate all on submit.</td>
              <td className="px-4 py-3"><AtomyQBadge variant="neutral">All forms</AtomyQBadge></td>
            </tr>
            <tr>
              <td className="px-4 py-3 font-medium">Keyboard submit</td>
              <td className="px-4 py-3 text-[var(--aq-text-muted)]">Ctrl+Enter submits the form</td>
              <td className="px-4 py-3"><AtomyQBadge variant="info">Multi-field forms</AtomyQBadge></td>
            </tr>
            <tr>
              <td className="px-4 py-3 font-medium">Unsaved changes</td>
              <td className="px-4 py-3 text-[var(--aq-text-muted)]">Warn before navigating away from dirty form</td>
              <td className="px-4 py-3"><AtomyQBadge variant="warning">Create/Edit forms</AtomyQBadge></td>
            </tr>
          </tbody>
        </table>
      </div>

      <h2 className="text-xl font-semibold text-[var(--aq-text-primary)] pt-4">Data Table Behaviour</h2>

      <div className="border border-[var(--aq-border-default)] rounded-lg overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <tr className="bg-[var(--aq-bg-elevated)]">
              <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-[var(--aq-text-muted)]">Feature</th>
              <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-[var(--aq-text-muted)]">Behaviour</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-[var(--aq-border-subtle)]">
            <tr><td className="px-4 py-3 font-medium">Sorting</td><td className="px-4 py-3 text-[var(--aq-text-muted)]">Click header cycles: asc → desc → none. Active sort shown with icon + colour.</td></tr>
            <tr><td className="px-4 py-3 font-medium">Bulk selection</td><td className="px-4 py-3 text-[var(--aq-text-muted)]">Header checkbox toggles all. Action bar appears when rows selected.</td></tr>
            <tr><td className="px-4 py-3 font-medium">Sticky header</td><td className="px-4 py-3 text-[var(--aq-text-muted)]">Table header stays visible when scrolling. Default on.</td></tr>
            <tr><td className="px-4 py-3 font-medium">Row click</td><td className="px-4 py-3 text-[var(--aq-text-muted)]">Navigates to detail view. Cursor changes to pointer.</td></tr>
            <tr><td className="px-4 py-3 font-medium">Empty state</td><td className="px-4 py-3 text-[var(--aq-text-muted)]">Centred message with optional CTA button.</td></tr>
            <tr><td className="px-4 py-3 font-medium">Loading</td><td className="px-4 py-3 text-[var(--aq-text-muted)]">Skeleton or spinner in table body area.</td></tr>
          </tbody>
        </table>
      </div>

      <h2 className="text-xl font-semibold text-[var(--aq-text-primary)] pt-4">Navigation Rules</h2>

      <div className="grid grid-cols-2 gap-4">
        <div className="p-4 rounded-lg border border-[var(--aq-border-default)]">
          <h4 className="font-medium text-sm mb-2">Use Modal When</h4>
          <ul className="text-sm text-[var(--aq-text-muted)] space-y-1.5 list-disc pl-4">
            <li>Quick confirmation (approve/reject)</li>
            <li>Viewing detail without losing context</li>
            <li>Simple forms (1-5 fields)</li>
          </ul>
        </div>
        <div className="p-4 rounded-lg border border-[var(--aq-border-default)]">
          <h4 className="font-medium text-sm mb-2">Use Full Page When</h4>
          <ul className="text-sm text-[var(--aq-text-muted)] space-y-1.5 list-disc pl-4">
            <li>Complex forms (Create RFQ, Scoring Policy)</li>
            <li>Data-heavy views (Comparison Matrix)</li>
            <li>Multi-step workflows</li>
          </ul>
        </div>
        <div className="p-4 rounded-lg border border-[var(--aq-border-default)]">
          <h4 className="font-medium text-sm mb-2">Use Slide-Over When</h4>
          <ul className="text-sm text-[var(--aq-text-muted)] space-y-1.5 list-disc pl-4">
            <li>Vendor profile preview</li>
            <li>Activity log / audit trail</li>
            <li>Filter/settings panel</li>
          </ul>
        </div>
        <div className="p-4 rounded-lg border border-[var(--aq-border-default)]">
          <h4 className="font-medium text-sm mb-2">Back Navigation</h4>
          <ul className="text-sm text-[var(--aq-text-muted)] space-y-1.5 list-disc pl-4">
            <li>Browser back always works</li>
            <li>Breadcrumbs for deep pages</li>
            <li>Never open new tab for internal pages</li>
          </ul>
        </div>
      </div>

      <h2 className="text-xl font-semibold text-[var(--aq-text-primary)] pt-4">Error Handling</h2>

      <div className="space-y-3">
        <AtomyQAlert variant="error" title="Server Error (500)">
          Something went wrong. Please try again or contact support if the issue persists.
        </AtomyQAlert>
        <AtomyQAlert variant="warning" title="Permission Denied (403)">
          You don't have permission to perform this action. Contact your administrator.
        </AtomyQAlert>
        <AtomyQAlert variant="info" title="Session Expiring">
          Your session will expire in 5 minutes. Save your work to avoid losing changes.
        </AtomyQAlert>
      </div>
    </div>
  ),
};

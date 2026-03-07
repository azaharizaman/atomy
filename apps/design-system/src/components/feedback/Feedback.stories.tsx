import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { AtomyQModal } from './AtomyQModal';
import { AtomyQToast } from './AtomyQToast';
import { AtomyQAlert } from './AtomyQAlert';
import { AtomyQConfirmDialog } from './AtomyQConfirmDialog';
import { AtomyQProgress } from './AtomyQProgress';
import { AtomyQSpinner } from './AtomyQSpinner';
import { AtomyQButton } from '../basic/AtomyQButton';

const meta: Meta = {
  title: 'Components/Feedback/Feedback Components',
  parameters: { layout: 'padded' },
};

export default meta;

export const Modal: StoryObj = {
  render: function Render() {
    const [open, setOpen] = useState(false);
    return (
      <>
        <AtomyQButton onClick={() => setOpen(true)}>Open Modal</AtomyQButton>
        <AtomyQModal
          open={open}
          onOpenChange={setOpen}
          title="Approve Comparison"
          description="Review and approve the AI-generated comparison for RFQ-2026-001."
          footer={
            <>
              <AtomyQButton variant="outline" onClick={() => setOpen(false)}>Cancel</AtomyQButton>
              <AtomyQButton variant="success" onClick={() => setOpen(false)}>Approve</AtomyQButton>
            </>
          }
        >
          <div className="space-y-3 text-sm text-[var(--aq-text-secondary)]">
            <p>The AI comparison has scored Apex Industrial Solutions as the recommended vendor with a confidence score of 92/100.</p>
            <div className="p-3 rounded-lg bg-[var(--aq-success-50)] text-[var(--aq-success-700)] text-sm">
              Estimated savings: RM 21,800 (5.2% below budget)
            </div>
          </div>
        </AtomyQModal>
      </>
    );
  },
};

export const SlideOverModal: StoryObj = {
  name: 'Slide-Over Modal',
  render: function Render() {
    const [open, setOpen] = useState(false);
    return (
      <>
        <AtomyQButton variant="outline" onClick={() => setOpen(true)}>Open Slide-Over</AtomyQButton>
        <AtomyQModal
          open={open}
          onOpenChange={setOpen}
          title="Vendor Details"
          description="Apex Industrial Solutions"
          size="slideOver"
          footer={
            <AtomyQButton variant="outline" onClick={() => setOpen(false)}>Close</AtomyQButton>
          }
        >
          <div className="space-y-4 text-sm">
            <div><span className="text-[var(--aq-text-muted)]">Contact:</span> <span>james.carter@apexind.com</span></div>
            <div><span className="text-[var(--aq-text-muted)]">Country:</span> <span>Malaysia</span></div>
            <div><span className="text-[var(--aq-text-muted)]">Rating:</span> <span>4.5 / 5.0</span></div>
            <div><span className="text-[var(--aq-text-muted)]">Status:</span> <span>Responded</span></div>
          </div>
        </AtomyQModal>
      </>
    );
  },
};

export const Toasts: StoryObj = {
  render: () => (
    <div className="space-y-3">
      <AtomyQToast variant="success" title="Quote accepted" message="Apex Industrial quote has been parsed successfully." onDismiss={() => {}} />
      <AtomyQToast variant="error" title="Parsing failed" message="Unable to parse softcore_quote_draft.pdf — 5 errors found." onDismiss={() => {}} action={{ label: 'View errors', onClick: () => {} }} />
      <AtomyQToast variant="warning" title="Approval required" message="RFQ-2026-004 award decision requires your approval." onDismiss={() => {}} />
      <AtomyQToast variant="info" title="Comparison running" message="AI comparison for Industrial Pumping Equipment is in progress." onDismiss={() => {}} />
    </div>
  ),
};

export const Alerts: StoryObj = {
  render: () => (
    <div className="space-y-3 max-w-lg">
      <AtomyQAlert variant="success" title="RFQ published">
        Your RFQ has been published and vendor invitations have been sent.
      </AtomyQAlert>
      <AtomyQAlert variant="error" title="Submission failed" dismissible action={{ label: 'Retry', onClick: () => {} }}>
        Failed to save RFQ. Please check your connection and try again.
      </AtomyQAlert>
      <AtomyQAlert variant="warning" title="Budget exceeded">
        The lowest quote exceeds the approved budget by 3.8%. Escalation may be required.
      </AtomyQAlert>
      <AtomyQAlert variant="info">
        AI comparison runs are scheduled for overnight processing during peak hours.
      </AtomyQAlert>
    </div>
  ),
};

export const ConfirmDialog: StoryObj = {
  render: function Render() {
    const [open, setOpen] = useState(false);
    const [openDest, setOpenDest] = useState(false);
    return (
      <div className="flex gap-3">
        <AtomyQButton variant="outline" onClick={() => setOpen(true)}>Confirm Action</AtomyQButton>
        <AtomyQConfirmDialog
          open={open}
          onOpenChange={setOpen}
          title="Submit for Approval"
          description="This will submit RFQ-2026-001 for manager approval. The comparison results and recommended vendor will be included."
          confirmLabel="Submit"
          onConfirm={() => setOpen(false)}
        />

        <AtomyQButton variant="destructive" onClick={() => setOpenDest(true)}>Delete RFQ</AtomyQButton>
        <AtomyQConfirmDialog
          open={openDest}
          onOpenChange={setOpenDest}
          title="Delete RFQ"
          description="Are you sure you want to delete RFQ-2026-008? This action cannot be undone. All associated quotes and comparisons will be permanently removed."
          confirmLabel="Delete"
          variant="destructive"
          onConfirm={() => setOpenDest(false)}
        />
      </div>
    );
  },
};

export const ProgressBars: StoryObj = {
  render: () => (
    <div className="space-y-6 max-w-md">
      <AtomyQProgress value={92} label="AI Confidence" showValue />
      <AtomyQProgress value={65} label="Quotes Received" showValue variant="warning" />
      <AtomyQProgress value={30} label="Approval Progress" showValue variant="default" />
      <AtomyQProgress value={8} label="Error Rate" showValue variant="danger" size="sm" />
      <AtomyQProgress value={100} label="Parsing Complete" showValue variant="success" size="lg" />
    </div>
  ),
};

export const Spinners: StoryObj = {
  render: () => (
    <div className="flex items-end gap-8">
      <AtomyQSpinner size="sm" />
      <AtomyQSpinner size="md" label="Loading..." />
      <AtomyQSpinner size="lg" label="Running AI comparison..." />
    </div>
  ),
};

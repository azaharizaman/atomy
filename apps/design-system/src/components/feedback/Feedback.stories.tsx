import type { Meta, StoryObj } from '@storybook/react-vite';
import { fn, expect, within, userEvent } from 'storybook/test';
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
  parameters: {
    layout: 'padded',
    docs: {
      description: {
        component: 'Feedback components for user notifications, confirmations, and status indicators. Includes modals, toasts, alerts, progress bars, and spinners.',
      },
    },
  },
};

export default meta;

// ==================== MODAL STORIES ====================

export const Modal: StoryObj<typeof AtomyQModal> = {
  render: function Render(args) {
    const [open, setOpen] = useState(false);
    return (
      <>
        <AtomyQButton onClick={() => setOpen(true)}>Open Modal</AtomyQButton>
        <AtomyQModal
          {...args}
          open={open}
          onOpenChange={(val) => {
            setOpen(val);
            args.onOpenChange?.(val);
          }}
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
  args: {
    title: 'Approve Comparison',
    description: 'Review and approve the AI-generated comparison for RFQ-2026-001.',
    size: 'md',
    onOpenChange: fn(),
  },
  argTypes: {
    title: { control: 'text', description: 'Modal title' },
    description: { control: 'text', description: 'Modal description/subtitle' },
    size: {
      control: 'select',
      options: ['sm', 'md', 'lg', 'xl', 'slideOver'],
      description: 'Modal size variant',
    },
  },
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);
    const openButton = canvas.getByRole('button', { name: /Open Modal/i });
    
    await expect(openButton).toBeInTheDocument();
    await userEvent.click(openButton);
  },
};

export const ModalSizes: StoryObj = {
  name: 'Modal Sizes',
  render: function Render() {
    const [size, setSize] = useState<'sm' | 'md' | 'lg' | 'xl' | 'slideOver' | null>(null);
    return (
      <>
        <div className="flex gap-2 flex-wrap">
          <AtomyQButton variant="outline" onClick={() => setSize('sm')}>Small Modal</AtomyQButton>
          <AtomyQButton variant="outline" onClick={() => setSize('md')}>Medium Modal</AtomyQButton>
          <AtomyQButton variant="outline" onClick={() => setSize('lg')}>Large Modal</AtomyQButton>
          <AtomyQButton variant="outline" onClick={() => setSize('xl')}>Extra Large Modal</AtomyQButton>
          <AtomyQButton variant="outline" onClick={() => setSize('slideOver')}>Slide-Over Modal</AtomyQButton>
        </div>
        {size && (
          <AtomyQModal
            open={true}
            onOpenChange={() => setSize(null)}
            title={`${size.charAt(0).toUpperCase() + size.slice(1)} Modal`}
            description={`This is a ${size} size modal example`}
            size={size}
            footer={<AtomyQButton onClick={() => setSize(null)}>Close</AtomyQButton>}
          >
            <p className="text-sm text-[var(--aq-text-secondary)]">
              Modal content goes here. The size determines the maximum width of the modal.
              {size === 'slideOver' && ' Slide-over modals appear from the right edge of the screen.'}
            </p>
          </AtomyQModal>
        )}
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
            <div className="grid grid-cols-2 gap-3">
              <div><span className="text-[var(--aq-text-muted)]">Contact:</span></div>
              <div>james.carter@apexind.com</div>
              <div><span className="text-[var(--aq-text-muted)]">Country:</span></div>
              <div>Malaysia</div>
              <div><span className="text-[var(--aq-text-muted)]">Rating:</span></div>
              <div>4.5 / 5.0 ⭐</div>
              <div><span className="text-[var(--aq-text-muted)]">Status:</span></div>
              <div>Responded</div>
              <div><span className="text-[var(--aq-text-muted)]">Response Time:</span></div>
              <div>2.3 days avg</div>
            </div>
          </div>
        </AtomyQModal>
      </>
    );
  },
};

// ==================== TOAST STORIES ====================

export const Toast: StoryObj<typeof AtomyQToast> = {
  render: (args) => (
    <AtomyQToast {...args} />
  ),
  args: {
    title: 'Quote accepted',
    message: 'Apex Industrial quote has been parsed successfully.',
    variant: 'success',
    onDismiss: fn(),
  },
  argTypes: {
    title: { control: 'text', description: 'Toast title' },
    message: { control: 'text', description: 'Toast message body' },
    variant: {
      control: 'select',
      options: ['success', 'error', 'warning', 'info'],
      description: 'Toast variant for styling',
    },
  },
  play: async ({ canvasElement, args }) => {
    const canvas = within(canvasElement);
    const toast = canvas.getByRole('alert');
    
    await expect(toast).toBeInTheDocument();
    await expect(canvas.getByText(args.title)).toBeInTheDocument();
    
    const dismissButton = canvas.getByRole('button', { name: /dismiss/i });
    await userEvent.click(dismissButton);
    await expect(args.onDismiss).toHaveBeenCalled();
  },
};

export const ToastVariants: StoryObj = {
  name: 'Toast Variants',
  render: () => (
    <div className="space-y-3">
      <AtomyQToast 
        variant="success" 
        title="Quote accepted" 
        message="Apex Industrial quote has been parsed successfully." 
        onDismiss={fn()} 
      />
      <AtomyQToast 
        variant="error" 
        title="Parsing failed" 
        message="Unable to parse softcore_quote_draft.pdf — 5 errors found." 
        onDismiss={fn()} 
        action={{ label: 'View errors', onClick: fn() }} 
      />
      <AtomyQToast 
        variant="warning" 
        title="Approval required" 
        message="RFQ-2026-004 award decision requires your approval." 
        onDismiss={fn()} 
      />
      <AtomyQToast 
        variant="info" 
        title="Comparison running" 
        message="AI comparison for Industrial Pumping Equipment is in progress." 
        onDismiss={fn()} 
      />
    </div>
  ),
};

export const ToastWithAction: StoryObj = {
  name: 'Toast with Action',
  render: function Render() {
    const handleAction = fn();
    const handleDismiss = fn();
    return (
      <AtomyQToast
        variant="error"
        title="Submission failed"
        message="Unable to submit the RFQ. Please check your connection."
        action={{ label: 'Retry', onClick: handleAction }}
        onDismiss={handleDismiss}
      />
    );
  },
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);
    const actionButton = canvas.getByRole('button', { name: /Retry/i });
    
    await expect(actionButton).toBeInTheDocument();
    await userEvent.click(actionButton);
  },
};

// ==================== ALERT STORIES ====================

export const Alert: StoryObj<typeof AtomyQAlert> = {
  render: (args) => (
    <div className="max-w-lg">
      <AtomyQAlert {...args}>
        {args.children || 'Your RFQ has been published and vendor invitations have been sent.'}
      </AtomyQAlert>
    </div>
  ),
  args: {
    title: 'RFQ published',
    variant: 'success',
    dismissible: false,
    children: 'Your RFQ has been published and vendor invitations have been sent.',
    onDismiss: fn(),
  },
  argTypes: {
    title: { control: 'text', description: 'Alert title' },
    variant: {
      control: 'select',
      options: ['success', 'error', 'warning', 'info'],
      description: 'Alert variant for styling',
    },
    dismissible: { control: 'boolean', description: 'Show dismiss button' },
    children: { control: 'text', description: 'Alert content' },
  },
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);
    const alert = canvas.getByRole('alert');
    
    await expect(alert).toBeInTheDocument();
  },
};

export const AlertVariants: StoryObj = {
  name: 'Alert Variants',
  render: () => (
    <div className="space-y-3 max-w-lg">
      <AtomyQAlert variant="success" title="RFQ published">
        Your RFQ has been published and vendor invitations have been sent.
      </AtomyQAlert>
      <AtomyQAlert variant="error" title="Submission failed" dismissible action={{ label: 'Retry', onClick: fn() }}>
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

// ==================== CONFIRM DIALOG STORIES ====================

export const ConfirmDialog: StoryObj<typeof AtomyQConfirmDialog> = {
  render: function Render(args) {
    const [open, setOpen] = useState(false);
    return (
      <>
        <AtomyQButton variant="outline" onClick={() => setOpen(true)}>Open Confirm Dialog</AtomyQButton>
        <AtomyQConfirmDialog
          {...args}
          open={open}
          onOpenChange={(val) => {
            setOpen(val);
            args.onOpenChange?.(val);
          }}
          onConfirm={() => {
            args.onConfirm?.();
            setOpen(false);
          }}
        />
      </>
    );
  },
  args: {
    title: 'Submit for Approval',
    description: 'This will submit RFQ-2026-001 for manager approval. The comparison results and recommended vendor will be included.',
    confirmLabel: 'Submit',
    variant: 'default',
    onOpenChange: fn(),
    onConfirm: fn(),
  },
  argTypes: {
    title: { control: 'text', description: 'Dialog title' },
    description: { control: 'text', description: 'Dialog description' },
    confirmLabel: { control: 'text', description: 'Confirm button label' },
    variant: {
      control: 'select',
      options: ['default', 'destructive'],
      description: 'Dialog variant',
    },
  },
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);
    const openButton = canvas.getByRole('button', { name: /Open Confirm Dialog/i });
    
    await userEvent.click(openButton);
  },
};

export const DestructiveConfirmDialog: StoryObj = {
  name: 'Destructive Confirm Dialog',
  render: function Render() {
    const [open, setOpen] = useState(false);
    const handleConfirm = fn();
    return (
      <>
        <AtomyQButton variant="destructive" onClick={() => setOpen(true)}>Delete RFQ</AtomyQButton>
        <AtomyQConfirmDialog
          open={open}
          onOpenChange={setOpen}
          title="Delete RFQ"
          description="Are you sure you want to delete RFQ-2026-008? This action cannot be undone. All associated quotes and comparisons will be permanently removed."
          confirmLabel="Delete"
          variant="destructive"
          onConfirm={() => {
            handleConfirm();
            setOpen(false);
          }}
        />
      </>
    );
  },
};

// ==================== PROGRESS STORIES ====================

export const Progress: StoryObj<typeof AtomyQProgress> = {
  render: (args) => (
    <div className="max-w-md">
      <AtomyQProgress {...args} />
    </div>
  ),
  args: {
    value: 75,
    label: 'Completion Progress',
    showValue: true,
    variant: 'default',
    size: 'md',
  },
  argTypes: {
    value: { control: { type: 'range', min: 0, max: 100 }, description: 'Progress value (0-100)' },
    label: { control: 'text', description: 'Progress label' },
    showValue: { control: 'boolean', description: 'Show percentage value' },
    variant: {
      control: 'select',
      options: ['default', 'success', 'warning', 'danger'],
      description: 'Progress bar variant',
    },
    size: {
      control: 'select',
      options: ['sm', 'md', 'lg'],
      description: 'Progress bar size',
    },
  },
};

export const ProgressVariants: StoryObj = {
  name: 'Progress Variants',
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

export const AnimatedProgress: StoryObj = {
  name: 'Animated Progress',
  render: function Render() {
    const [value, setValue] = useState(0);
    
    return (
      <div className="space-y-4 max-w-md">
        <AtomyQProgress value={value} label="Upload Progress" showValue />
        <div className="flex gap-2">
          <AtomyQButton size="sm" variant="outline" onClick={() => setValue(Math.max(0, value - 10))}>
            -10%
          </AtomyQButton>
          <AtomyQButton size="sm" variant="outline" onClick={() => setValue(Math.min(100, value + 10))}>
            +10%
          </AtomyQButton>
          <AtomyQButton size="sm" variant="outline" onClick={() => setValue(0)}>
            Reset
          </AtomyQButton>
          <AtomyQButton size="sm" variant="primary" onClick={() => setValue(100)}>
            Complete
          </AtomyQButton>
        </div>
      </div>
    );
  },
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);
    const increaseButton = canvas.getByRole('button', { name: /\+10%/i });
    
    await userEvent.click(increaseButton);
    await userEvent.click(increaseButton);
    await userEvent.click(increaseButton);
  },
};

// ==================== SPINNER STORIES ====================

export const Spinner: StoryObj<typeof AtomyQSpinner> = {
  render: (args) => (
    <AtomyQSpinner {...args} />
  ),
  args: {
    size: 'md',
    label: 'Loading...',
  },
  argTypes: {
    size: {
      control: 'select',
      options: ['sm', 'md', 'lg'],
      description: 'Spinner size',
    },
    label: { control: 'text', description: 'Loading label text' },
  },
};

export const SpinnerSizes: StoryObj = {
  name: 'Spinner Sizes',
  render: () => (
    <div className="flex items-end gap-8">
      <AtomyQSpinner size="sm" />
      <AtomyQSpinner size="md" label="Loading..." />
      <AtomyQSpinner size="lg" label="Running AI comparison..." />
    </div>
  ),
};

// ==================== COMBINED FEEDBACK DEMO ====================

export const FeedbackDemo: StoryObj = {
  name: 'Interactive Feedback Demo',
  render: function Render() {
    const [showToast, setShowToast] = useState(false);
    const [toastVariant, setToastVariant] = useState<'success' | 'error' | 'warning' | 'info'>('success');
    const [showAlert, setShowAlert] = useState(true);
    const [progress, setProgress] = useState(0);
    const [loading, setLoading] = useState(false);

    const simulateProgress = () => {
      setLoading(true);
      setProgress(0);
      const interval = setInterval(() => {
        setProgress((prev) => {
          if (prev >= 100) {
            clearInterval(interval);
            setLoading(false);
            setShowToast(true);
            setToastVariant('success');
            return 100;
          }
          return prev + 10;
        });
      }, 200);
    };

    return (
      <div className="space-y-6 max-w-lg">
        <div className="p-4 border border-[var(--aq-border-default)] rounded-lg space-y-4">
          <h3 className="font-medium text-[var(--aq-text-primary)]">Feedback Demo</h3>
          
          {showAlert && (
            <AtomyQAlert 
              variant="info" 
              dismissible 
              onDismiss={() => setShowAlert(false)}
            >
              Click the buttons below to trigger different feedback types.
            </AtomyQAlert>
          )}

          <div className="flex gap-2 flex-wrap">
            <AtomyQButton 
              onClick={() => { setToastVariant('success'); setShowToast(true); }}
              variant="success"
              size="sm"
            >
              Success Toast
            </AtomyQButton>
            <AtomyQButton 
              onClick={() => { setToastVariant('error'); setShowToast(true); }}
              variant="destructive"
              size="sm"
            >
              Error Toast
            </AtomyQButton>
            <AtomyQButton 
              onClick={() => { setToastVariant('warning'); setShowToast(true); }}
              variant="outline"
              size="sm"
            >
              Warning Toast
            </AtomyQButton>
            <AtomyQButton 
              onClick={simulateProgress}
              variant="primary"
              size="sm"
              loading={loading}
            >
              Simulate Progress
            </AtomyQButton>
          </div>

          {(loading || progress > 0) && (
            <AtomyQProgress 
              value={progress} 
              label="Processing..." 
              showValue 
              variant={progress === 100 ? 'success' : 'default'}
            />
          )}

          {loading && (
            <div className="flex items-center gap-2">
              <AtomyQSpinner size="sm" />
              <span className="text-sm text-[var(--aq-text-muted)]">Processing request...</span>
            </div>
          )}
        </div>

        {showToast && (
          <div className="fixed bottom-4 right-4 z-50">
            <AtomyQToast
              variant={toastVariant}
              title={toastVariant === 'success' ? 'Operation completed' : toastVariant === 'error' ? 'Operation failed' : 'Attention needed'}
              message={`This is a ${toastVariant} notification message.`}
              onDismiss={() => setShowToast(false)}
            />
          </div>
        )}
      </div>
    );
  },
};

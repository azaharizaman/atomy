import type { Meta, StoryObj } from '@storybook/react-vite';
import { fn, expect, within, userEvent } from 'storybook/test';
import { useState } from 'react';
import { AtomyQModal } from '@/components/feedback/AtomyQModal';
import { AtomyQConfirmDialog } from '@/components/feedback/AtomyQConfirmDialog';
import { AtomyQButton } from '@/components/basic/AtomyQButton';
import { AtomyQInput } from '@/components/form/AtomyQInput';
import { AtomyQSelect } from '@/components/form/AtomyQSelect';
import { AtomyQTextarea } from '@/components/form/AtomyQTextarea';
import { AtomyQCheckbox } from '@/components/form/AtomyQCheckbox';
import { AtomyQStepper } from '@/components/navigation/AtomyQStepper';
import { AtomyQProgress } from '@/components/feedback/AtomyQProgress';
import { AtomyQAlert } from '@/components/feedback/AtomyQAlert';
import { 
  AlertTriangle, CheckCircle, Info, X, 
  Upload, FileText, User, Mail, Building,
  ChevronLeft, ChevronRight
} from 'lucide-react';

const meta: Meta = {
  title: 'Layouts/Modal Layouts',
  parameters: {
    layout: 'centered',
    docs: {
      description: {
        component: `
# Modal Layout Patterns

AtomyQ uses various modal patterns for different interaction types. Each modal type serves a specific purpose and follows consistent structure.

## Modal Types

| Type | Purpose | Size |
|------|---------|------|
| **Centered Modal** | Form input, confirmations | sm-xl |
| **Slide-Over Modal** | Details, side panels | Fixed width |
| **Confirmation Dialog** | Yes/No decisions | Small |
| **Fullscreen Modal** | Complex workflows | Full viewport |

## Structure

\`\`\`
┌────────────────────────────────────┐
│  Header (Title + Close)            │
├────────────────────────────────────┤
│                                    │
│  Content Area                      │
│                                    │
├────────────────────────────────────┤
│  Footer (Actions)                  │
└────────────────────────────────────┘
\`\`\`
        `,
      },
    },
  },
};

export default meta;

// ==================== CENTERED MODAL ====================

export const CenteredModal: StoryObj = {
  name: 'Centered Modal',
  render: function Render() {
    const [open, setOpen] = useState(false);
    const [size, setSize] = useState<'sm' | 'md' | 'lg' | 'xl'>('md');
    
    return (
      <div className="space-y-4">
        {/* ASCII Diagram */}
        <div className="p-4 bg-[var(--aq-bg-elevated)] rounded-lg border border-[var(--aq-border-default)] font-mono text-xs text-[var(--aq-text-muted)] max-w-md">
          <pre>{`
Centered Modal Structure:
┌────────────────────────────┐
│  ✕  Title              [X] │ ← Header with close
├────────────────────────────┤
│                            │
│  Content Area              │ ← Scrollable content
│  • Form fields             │
│  • Information             │
│                            │
├────────────────────────────┤
│        [Cancel] [Primary]  │ ← Right-aligned actions
└────────────────────────────┘
          `}</pre>
        </div>
        
        <div className="flex gap-2">
          {(['sm', 'md', 'lg', 'xl'] as const).map((s) => (
            <AtomyQButton 
              key={s} 
              variant="outline" 
              onClick={() => { setSize(s); setOpen(true); }}
            >
              {s.toUpperCase()} Modal
            </AtomyQButton>
          ))}
        </div>
        
        <AtomyQModal
          open={open}
          onOpenChange={setOpen}
          title="Create New RFQ"
          description={`This is a ${size} centered modal`}
          size={size}
          footer={
            <>
              <AtomyQButton variant="outline" onClick={() => setOpen(false)}>Cancel</AtomyQButton>
              <AtomyQButton variant="primary" onClick={() => setOpen(false)}>Create</AtomyQButton>
            </>
          }
        >
          <div className="space-y-4">
            <AtomyQInput label="Title" placeholder="Enter RFQ title..." required />
            <AtomyQSelect 
              label="Category" 
              placeholder="Select category..."
              options={[
                { value: 'equipment', label: 'Equipment' },
                { value: 'services', label: 'Services' },
              ]} 
            />
            <AtomyQTextarea label="Description" placeholder="Enter description..." />
          </div>
        </AtomyQModal>
      </div>
    );
  },
  parameters: {
    docs: {
      description: {
        story: `
## Centered Modal

Standard modal dialog centered on screen. Best for focused tasks requiring user attention.

**Sizes:**
- **sm** (max-w-md): Simple confirmations, quick actions
- **md** (max-w-lg): Standard forms, information display
- **lg** (max-w-2xl): Complex forms, multi-step content
- **xl** (max-w-4xl): Data-heavy content, tables in modals

**Use for:** Form submission, quick edits, information display
        `,
      },
    },
  },
};

// ==================== SLIDE-OVER MODAL ====================

export const SlideOverModal: StoryObj = {
  name: 'Slide-Over Modal',
  render: function Render() {
    const [open, setOpen] = useState(false);
    
    return (
      <div className="space-y-4">
        {/* ASCII Diagram */}
        <div className="p-4 bg-[var(--aq-bg-elevated)] rounded-lg border border-[var(--aq-border-default)] font-mono text-xs text-[var(--aq-text-muted)] max-w-md">
          <pre>{`
Slide-Over Modal Structure:
                    ┌──────────────────┐
                    │ [X] Title        │
                    ├──────────────────┤
  Overlay           │                  │
  (click to close)  │ Scrollable       │
                    │ Content          │
                    │                  │
                    │ • Details        │
                    │ • Lists          │
                    │ • Preview        │
                    │                  │
                    ├──────────────────┤
                    │      [Actions]   │
                    └──────────────────┘
                         ← Slides from right
          `}</pre>
        </div>
        
        <AtomyQButton variant="outline" onClick={() => setOpen(true)}>
          Open Slide-Over
        </AtomyQButton>
        
        <AtomyQModal
          open={open}
          onOpenChange={setOpen}
          title="Vendor Details"
          description="Apex Industrial Solutions"
          size="slideOver"
          footer={
            <>
              <AtomyQButton variant="outline" onClick={() => setOpen(false)}>Close</AtomyQButton>
              <AtomyQButton variant="primary">Send Message</AtomyQButton>
            </>
          }
        >
          <div className="space-y-6">
            <div className="flex items-center gap-4">
              <div className="size-16 rounded-full bg-[var(--aq-brand-100)] flex items-center justify-center">
                <Building className="size-8 text-[var(--aq-brand-600)]" />
              </div>
              <div>
                <h3 className="font-medium text-[var(--aq-text-primary)]">Apex Industrial Solutions</h3>
                <p className="text-sm text-[var(--aq-text-muted)]">Verified Vendor • Since 2018</p>
              </div>
            </div>
            
            <div className="space-y-3">
              <h4 className="font-medium text-sm text-[var(--aq-text-primary)]">Contact Information</h4>
              <div className="grid grid-cols-2 gap-3 text-sm">
                <div className="flex items-center gap-2 text-[var(--aq-text-muted)]">
                  <User className="size-4" />
                  <span>James Carter</span>
                </div>
                <div className="flex items-center gap-2 text-[var(--aq-text-muted)]">
                  <Mail className="size-4" />
                  <span>james@apexind.com</span>
                </div>
              </div>
            </div>
            
            <div className="space-y-3">
              <h4 className="font-medium text-sm text-[var(--aq-text-primary)]">Performance Metrics</h4>
              <div className="space-y-2">
                <div className="flex justify-between text-sm">
                  <span className="text-[var(--aq-text-muted)]">Response Rate</span>
                  <span className="text-[var(--aq-text-primary)]">94%</span>
                </div>
                <AtomyQProgress value={94} size="sm" />
              </div>
              <div className="space-y-2">
                <div className="flex justify-between text-sm">
                  <span className="text-[var(--aq-text-muted)]">On-Time Delivery</span>
                  <span className="text-[var(--aq-text-primary)]">87%</span>
                </div>
                <AtomyQProgress value={87} size="sm" />
              </div>
            </div>
            
            <div className="space-y-3">
              <h4 className="font-medium text-sm text-[var(--aq-text-primary)]">Recent Activity</h4>
              <div className="space-y-2 text-sm">
                {['Quote submitted for RFQ-2026-001', 'Updated company profile', 'Responded to clarification'].map((item, i) => (
                  <div key={i} className="flex items-center gap-2 text-[var(--aq-text-muted)]">
                    <div className="size-1.5 rounded-full bg-[var(--aq-brand-500)]" />
                    {item}
                  </div>
                ))}
              </div>
            </div>
          </div>
        </AtomyQModal>
      </div>
    );
  },
  parameters: {
    docs: {
      description: {
        story: `
## Slide-Over Modal

Panel that slides in from the right edge. Maintains context with the underlying page.

**Characteristics:**
- Fixed width (typically 400-500px)
- Slides in from right
- Overlay dims background
- Scrollable content area

**Use for:** Detail panels, quick previews, secondary navigation
        `,
      },
    },
  },
};

// ==================== CONFIRMATION DIALOG ====================

export const ConfirmationDialog: StoryObj = {
  name: 'Confirmation Dialog',
  render: function Render() {
    const [openDefault, setOpenDefault] = useState(false);
    const [openDestructive, setOpenDestructive] = useState(false);
    const handleConfirm = fn();
    
    return (
      <div className="space-y-4">
        {/* ASCII Diagram */}
        <div className="p-4 bg-[var(--aq-bg-elevated)] rounded-lg border border-[var(--aq-border-default)] font-mono text-xs text-[var(--aq-text-muted)] max-w-md">
          <pre>{`
Confirmation Dialog Structure:
┌────────────────────────────┐
│  ⚠️  Title                 │ ← Icon indicates severity
├────────────────────────────┤
│                            │
│  Brief explanation of      │
│  what will happen.         │
│                            │
├────────────────────────────┤
│    [Cancel]  [Confirm]     │ ← Clear action labels
└────────────────────────────┘

Variants:
• Default - Blue/Primary confirm button
• Destructive - Red confirm button
          `}</pre>
        </div>
        
        <div className="flex gap-2">
          <AtomyQButton variant="outline" onClick={() => setOpenDefault(true)}>
            Default Confirm
          </AtomyQButton>
          <AtomyQButton variant="destructive" onClick={() => setOpenDestructive(true)}>
            Destructive Confirm
          </AtomyQButton>
        </div>
        
        <AtomyQConfirmDialog
          open={openDefault}
          onOpenChange={setOpenDefault}
          title="Submit for Approval"
          description="This will submit RFQ-2026-001 for manager approval. You won't be able to make changes after submission."
          confirmLabel="Submit"
          variant="default"
          onConfirm={() => {
            handleConfirm();
            setOpenDefault(false);
          }}
        />
        
        <AtomyQConfirmDialog
          open={openDestructive}
          onOpenChange={setOpenDestructive}
          title="Delete RFQ"
          description="Are you sure you want to delete RFQ-2026-008? This action cannot be undone. All quotes and comparisons will be permanently removed."
          confirmLabel="Delete"
          variant="destructive"
          onConfirm={() => {
            handleConfirm();
            setOpenDestructive(false);
          }}
        />
      </div>
    );
  },
  parameters: {
    docs: {
      description: {
        story: `
## Confirmation Dialog

Simple dialog for yes/no decisions. Keeps users in control of destructive or important actions.

**Variants:**
- **Default**: For safe actions (submit, send, approve)
- **Destructive**: For irreversible actions (delete, remove, revoke)

**Best Practices:**
- Use clear, specific titles
- Explain consequences in description
- Use action-oriented button labels (not just "Yes/No")
        `,
      },
    },
  },
};

// ==================== WIZARD MODAL ====================

export const WizardModal: StoryObj = {
  name: 'Wizard/Multi-Step Modal',
  render: function Render() {
    const [open, setOpen] = useState(false);
    const [step, setStep] = useState(0);
    
    const steps = [
      { label: 'Basic Info', description: 'RFQ details' },
      { label: 'Line Items', description: 'Add items' },
      { label: 'Vendors', description: 'Invite vendors' },
      { label: 'Review', description: 'Confirm details' },
    ];
    
    const stepContent = [
      <div key="0" className="space-y-4">
        <AtomyQInput label="RFQ Title" placeholder="Enter title..." required />
        <AtomyQSelect label="Category" placeholder="Select..." options={[
          { value: 'equipment', label: 'Equipment' },
          { value: 'services', label: 'Services' },
        ]} />
      </div>,
      <div key="1" className="space-y-4">
        <AtomyQAlert variant="info">Add line items to your RFQ</AtomyQAlert>
        <div className="p-8 border-2 border-dashed border-[var(--aq-border-default)] rounded-lg text-center">
          <FileText className="size-8 mx-auto text-[var(--aq-text-muted)] mb-2" />
          <p className="text-sm text-[var(--aq-text-muted)]">No items added yet</p>
          <AtomyQButton variant="outline" size="sm" className="mt-2">Add Item</AtomyQButton>
        </div>
      </div>,
      <div key="2" className="space-y-4">
        <AtomyQInput label="Search Vendors" placeholder="Search by name..." leftIcon={<User className="size-4" />} />
        <div className="space-y-2">
          {['Apex Industrial', 'TechFlow Systems', 'Global Pumps'].map((vendor) => (
            <AtomyQCheckbox key={vendor} label={vendor} description="Pre-qualified vendor" />
          ))}
        </div>
      </div>,
      <div key="3" className="space-y-4">
        <AtomyQAlert variant="success">Ready to publish!</AtomyQAlert>
        <div className="bg-[var(--aq-bg-elevated)] rounded-lg p-4 space-y-2 text-sm">
          <div className="flex justify-between">
            <span className="text-[var(--aq-text-muted)]">Title</span>
            <span className="text-[var(--aq-text-primary)]">Industrial Equipment</span>
          </div>
          <div className="flex justify-between">
            <span className="text-[var(--aq-text-muted)]">Items</span>
            <span className="text-[var(--aq-text-primary)]">0</span>
          </div>
          <div className="flex justify-between">
            <span className="text-[var(--aq-text-muted)]">Vendors</span>
            <span className="text-[var(--aq-text-primary)]">3 invited</span>
          </div>
        </div>
      </div>,
    ];
    
    return (
      <div className="space-y-4">
        {/* ASCII Diagram */}
        <div className="p-4 bg-[var(--aq-bg-elevated)] rounded-lg border border-[var(--aq-border-default)] font-mono text-xs text-[var(--aq-text-muted)] max-w-md">
          <pre>{`
Wizard Modal Structure:
┌────────────────────────────┐
│  Title                 [X] │
├────────────────────────────┤
│  ①──②──③──④               │ ← Progress stepper
├────────────────────────────┤
│                            │
│  Step Content              │ ← Changes per step
│                            │
├────────────────────────────┤
│  [Back]        [Next/Done] │ ← Navigation
└────────────────────────────┘
          `}</pre>
        </div>
        
        <AtomyQButton variant="primary" onClick={() => { setOpen(true); setStep(0); }}>
          Open Wizard
        </AtomyQButton>
        
        <AtomyQModal
          open={open}
          onOpenChange={setOpen}
          title="Create RFQ"
          description={`Step ${step + 1} of ${steps.length}: ${steps[step].label}`}
          size="lg"
          footer={
            <>
              <AtomyQButton 
                variant="outline" 
                onClick={() => step > 0 ? setStep(step - 1) : setOpen(false)}
              >
                <ChevronLeft className="size-4" />
                {step > 0 ? 'Back' : 'Cancel'}
              </AtomyQButton>
              <AtomyQButton 
                variant="primary"
                onClick={() => step < 3 ? setStep(step + 1) : setOpen(false)}
              >
                {step < 3 ? 'Next' : 'Create RFQ'}
                {step < 3 && <ChevronRight className="size-4" />}
              </AtomyQButton>
            </>
          }
        >
          <div className="space-y-6">
            <AtomyQStepper 
              steps={steps.map((s, i) => ({
                ...s,
                status: i < step ? 'completed' : i === step ? 'current' : 'upcoming'
              }))} 
              orientation="horizontal" 
            />
            {stepContent[step]}
          </div>
        </AtomyQModal>
      </div>
    );
  },
  parameters: {
    docs: {
      description: {
        story: `
## Wizard/Multi-Step Modal

Modal with step-by-step progression. Breaks complex tasks into manageable chunks.

**Structure:**
- Progress indicator (stepper)
- Step content area
- Back/Next navigation

**Use for:** Complex forms, onboarding flows, guided workflows
        `,
      },
    },
  },
};

// ==================== FULLSCREEN MODAL ====================

export const FullscreenModal: StoryObj = {
  name: 'Fullscreen Modal',
  render: function Render() {
    const [open, setOpen] = useState(false);
    
    return (
      <div className="space-y-4">
        {/* ASCII Diagram */}
        <div className="p-4 bg-[var(--aq-bg-elevated)] rounded-lg border border-[var(--aq-border-default)] font-mono text-xs text-[var(--aq-text-muted)] max-w-md">
          <pre>{`
Fullscreen Modal Structure:
┌────────────────────────────────────────┐
│  [←] Title                   [Actions] │ ← Full header
├────────────────────────────────────────┤
│                                        │
│                                        │
│         Full Content Area              │
│                                        │
│    (Complex layouts, tables,           │
│     multi-panel views)                 │
│                                        │
│                                        │
└────────────────────────────────────────┘
          `}</pre>
        </div>
        
        <AtomyQButton variant="primary" onClick={() => setOpen(true)}>
          Open Fullscreen
        </AtomyQButton>
        
        {open && (
          <div className="fixed inset-0 z-50 bg-white">
            <header className="h-14 border-b border-[var(--aq-border-default)] px-6 flex items-center justify-between">
              <div className="flex items-center gap-3">
                <AtomyQButton variant="ghost" size="sm" onClick={() => setOpen(false)}>
                  <ChevronLeft className="size-4" /> Back
                </AtomyQButton>
                <span className="text-[var(--aq-border-default)]">|</span>
                <h2 className="font-semibold text-[var(--aq-text-primary)]">Quote Comparison</h2>
              </div>
              <div className="flex gap-2">
                <AtomyQButton variant="outline" size="sm">Export</AtomyQButton>
                <AtomyQButton variant="success" size="sm">Approve</AtomyQButton>
              </div>
            </header>
            <main className="h-[calc(100vh-56px)] overflow-auto p-6 bg-[var(--aq-bg-canvas)]">
              <div className="grid grid-cols-12 gap-4 h-full">
                <div className="col-span-3 bg-white rounded-lg border border-[var(--aq-border-default)] p-4">
                  <h3 className="font-medium mb-3">Line Items</h3>
                  <div className="space-y-2">
                    {['Industrial Pump', 'Motor', 'Panel', 'Install'].map((item) => (
                      <div key={item} className="p-2 rounded bg-[var(--aq-bg-elevated)] text-sm">
                        {item}
                      </div>
                    ))}
                  </div>
                </div>
                {[1, 2, 3].map((i) => (
                  <div key={i} className="col-span-3 bg-white rounded-lg border border-[var(--aq-border-default)] p-4">
                    <h3 className="font-medium mb-3">Vendor {i}</h3>
                    <div className="space-y-2 text-sm text-[var(--aq-text-muted)]">
                      <p>Total: RM {(400000 + i * 20000).toLocaleString()}</p>
                      <p>Score: {95 - i * 3}/100</p>
                    </div>
                  </div>
                ))}
              </div>
            </main>
          </div>
        )}
      </div>
    );
  },
  parameters: {
    docs: {
      description: {
        story: `
## Fullscreen Modal

Takes over the entire viewport for complex workflows.

**Use for:**
- Comparison views
- Document editors
- Complex data entry
- Immersive experiences

**Characteristics:**
- Full viewport coverage
- Custom header with back navigation
- Rich content layouts
        `,
      },
    },
  },
};

// ==================== UPLOAD MODAL ====================

export const UploadModal: StoryObj = {
  name: 'Upload/Import Modal',
  render: function Render() {
    const [open, setOpen] = useState(false);
    const [uploading, setUploading] = useState(false);
    const [progress, setProgress] = useState(0);
    
    const simulateUpload = () => {
      setUploading(true);
      setProgress(0);
      const interval = setInterval(() => {
        setProgress((p) => {
          if (p >= 100) {
            clearInterval(interval);
            setUploading(false);
            return 100;
          }
          return p + 10;
        });
      }, 200);
    };
    
    return (
      <div className="space-y-4">
        {/* ASCII Diagram */}
        <div className="p-4 bg-[var(--aq-bg-elevated)] rounded-lg border border-[var(--aq-border-default)] font-mono text-xs text-[var(--aq-text-muted)] max-w-md">
          <pre>{`
Upload Modal Structure:
┌────────────────────────────┐
│  Upload Files          [X] │
├────────────────────────────┤
│  ┌──────────────────────┐  │
│  │                      │  │
│  │   📁 Drop zone      │  │ ← Drag & drop area
│  │   or click to        │  │
│  │   browse             │  │
│  │                      │  │
│  └──────────────────────┘  │
│                            │
│  📄 file.pdf    ████░ 75%  │ ← File list + progress
│                            │
├────────────────────────────┤
│     [Cancel]  [Upload]     │
└────────────────────────────┘
          `}</pre>
        </div>
        
        <AtomyQButton variant="outline" onClick={() => setOpen(true)}>
          <Upload className="size-4" /> Upload Files
        </AtomyQButton>
        
        <AtomyQModal
          open={open}
          onOpenChange={setOpen}
          title="Upload Quote Documents"
          description="Upload vendor quote files for parsing"
          size="md"
          footer={
            <>
              <AtomyQButton variant="outline" onClick={() => setOpen(false)}>Cancel</AtomyQButton>
              <AtomyQButton variant="primary" onClick={simulateUpload} loading={uploading}>
                {uploading ? 'Uploading...' : 'Upload'}
              </AtomyQButton>
            </>
          }
        >
          <div className="space-y-4">
            <div className="border-2 border-dashed border-[var(--aq-border-default)] rounded-lg p-8 text-center hover:border-[var(--aq-brand-500)] transition-colors cursor-pointer">
              <Upload className="size-10 mx-auto text-[var(--aq-text-muted)] mb-3" />
              <p className="text-sm text-[var(--aq-text-primary)] font-medium">
                Drop files here or click to browse
              </p>
              <p className="text-xs text-[var(--aq-text-muted)] mt-1">
                Supports PDF, Excel, Word (max 10MB)
              </p>
            </div>
            
            {progress > 0 && (
              <div className="space-y-3">
                <div className="flex items-center gap-3 p-3 bg-[var(--aq-bg-elevated)] rounded-lg">
                  <FileText className="size-5 text-[var(--aq-text-muted)]" />
                  <div className="flex-1">
                    <p className="text-sm font-medium text-[var(--aq-text-primary)]">vendor_quote.pdf</p>
                    <AtomyQProgress value={progress} size="sm" />
                  </div>
                  {progress < 100 && (
                    <span className="text-xs text-[var(--aq-text-muted)]">{progress}%</span>
                  )}
                  {progress === 100 && (
                    <CheckCircle className="size-5 text-[var(--aq-success-500)]" />
                  )}
                </div>
              </div>
            )}
          </div>
        </AtomyQModal>
      </div>
    );
  },
  parameters: {
    docs: {
      description: {
        story: `
## Upload/Import Modal

Specialized modal for file upload workflows.

**Components:**
- Drag & drop zone
- File list with progress
- Supported formats info

**Use for:** File uploads, document imports, bulk data import
        `,
      },
    },
  },
};

// ==================== MODAL OVERVIEW ====================

export const ModalOverview: StoryObj = {
  name: 'All Modal Types Overview',
  render: () => (
    <div className="p-8 space-y-8 bg-[var(--aq-bg-canvas)] min-h-screen max-w-4xl">
      <h1 className="text-2xl font-semibold text-[var(--aq-text-primary)]">Modal Layout Patterns</h1>
      
      <div className="grid grid-cols-2 gap-6">
        {/* Centered Modal */}
        <div className="bg-white rounded-lg border border-[var(--aq-border-default)] p-4">
          <h3 className="font-medium text-[var(--aq-text-primary)] mb-2">Centered Modal</h3>
          <pre className="text-xs font-mono text-[var(--aq-text-muted)] bg-[var(--aq-bg-elevated)] p-3 rounded">{`┌────────────────────────┐
│ Header            [X]  │
├────────────────────────┤
│ Content Area           │
├────────────────────────┤
│    [Cancel] [Primary]  │
└────────────────────────┘`}</pre>
          <p className="text-sm text-[var(--aq-text-muted)] mt-2">Forms, confirmations, info</p>
        </div>
        
        {/* Slide-Over */}
        <div className="bg-white rounded-lg border border-[var(--aq-border-default)] p-4">
          <h3 className="font-medium text-[var(--aq-text-primary)] mb-2">Slide-Over Modal</h3>
          <pre className="text-xs font-mono text-[var(--aq-text-muted)] bg-[var(--aq-bg-elevated)] p-3 rounded">{`         ┌──────────────┐
Overlay  │ Header  [X]  │
         │──────────────│
         │ Content      │
         │ (scrollable) │
         │──────────────│
         │   [Actions]  │
         └──────────────┘`}</pre>
          <p className="text-sm text-[var(--aq-text-muted)] mt-2">Details, previews, side panels</p>
        </div>
        
        {/* Confirmation */}
        <div className="bg-white rounded-lg border border-[var(--aq-border-default)] p-4">
          <h3 className="font-medium text-[var(--aq-text-primary)] mb-2">Confirmation Dialog</h3>
          <pre className="text-xs font-mono text-[var(--aq-text-muted)] bg-[var(--aq-bg-elevated)] p-3 rounded">{`┌────────────────────────┐
│ ⚠️  Title              │
├────────────────────────┤
│ Brief message about    │
│ the action.            │
├────────────────────────┤
│  [Cancel]  [Confirm]   │
└────────────────────────┘`}</pre>
          <p className="text-sm text-[var(--aq-text-muted)] mt-2">Yes/No decisions, destructive actions</p>
        </div>
        
        {/* Wizard */}
        <div className="bg-white rounded-lg border border-[var(--aq-border-default)] p-4">
          <h3 className="font-medium text-[var(--aq-text-primary)] mb-2">Wizard Modal</h3>
          <pre className="text-xs font-mono text-[var(--aq-text-muted)] bg-[var(--aq-bg-elevated)] p-3 rounded">{`┌────────────────────────┐
│ Title             [X]  │
├────────────────────────┤
│ ①──②──③──④            │
├────────────────────────┤
│ Step Content           │
├────────────────────────┤
│ [Back]    [Next/Done]  │
└────────────────────────┘`}</pre>
          <p className="text-sm text-[var(--aq-text-muted)] mt-2">Multi-step workflows, onboarding</p>
        </div>
        
        {/* Fullscreen */}
        <div className="bg-white rounded-lg border border-[var(--aq-border-default)] p-4">
          <h3 className="font-medium text-[var(--aq-text-primary)] mb-2">Fullscreen Modal</h3>
          <pre className="text-xs font-mono text-[var(--aq-text-muted)] bg-[var(--aq-bg-elevated)] p-3 rounded">{`┌────────────────────────────┐
│ [←] Title      [Actions]   │
├────────────────────────────┤
│                            │
│    Full Content Area       │
│                            │
│    (100% viewport)         │
│                            │
└────────────────────────────┘`}</pre>
          <p className="text-sm text-[var(--aq-text-muted)] mt-2">Complex views, comparisons</p>
        </div>
        
        {/* Upload */}
        <div className="bg-white rounded-lg border border-[var(--aq-border-default)] p-4">
          <h3 className="font-medium text-[var(--aq-text-primary)] mb-2">Upload Modal</h3>
          <pre className="text-xs font-mono text-[var(--aq-text-muted)] bg-[var(--aq-bg-elevated)] p-3 rounded">{`┌────────────────────────┐
│ Upload Files      [X]  │
├────────────────────────┤
│  ┌──────────────────┐  │
│  │  📁 Drop zone   │  │
│  └──────────────────┘  │
│  📄 file.pdf ███░ 75%  │
├────────────────────────┤
│  [Cancel]   [Upload]   │
└────────────────────────┘`}</pre>
          <p className="text-sm text-[var(--aq-text-muted)] mt-2">File uploads, imports</p>
        </div>
      </div>
    </div>
  ),
  parameters: {
    layout: 'fullscreen',
    docs: {
      description: {
        story: 'Quick reference showing all modal layout patterns with their ASCII diagrams.',
      },
    },
  },
};

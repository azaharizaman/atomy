import type { Meta, StoryObj } from '@storybook/react-vite';
import { fn } from 'storybook/test';
import { useState } from 'react';
import { AtomyQSidebar } from '@/components/navigation/AtomyQSidebar';
import { AtomyQBreadcrumb } from '@/components/navigation/AtomyQBreadcrumb';
import { AtomyQTabs, AtomyQTabContent } from '@/components/navigation/AtomyQTabs';
import { AtomyQKPICard } from '@/components/data/AtomyQKPICard';
import { AtomyQButton } from '@/components/basic/AtomyQButton';
import { AtomyQInput } from '@/components/form/AtomyQInput';
import { AtomyQSelect } from '@/components/form/AtomyQSelect';
import { AtomyQTextarea } from '@/components/form/AtomyQTextarea';
import { navigationItems } from '@/data/mockData';
import { 
  Plus, Download, FileText, TrendingUp, Clock, Sparkles, 
  Search, Filter, MoreHorizontal, ChevronRight, Bell, User,
  Settings, HelpCircle
} from 'lucide-react';

const meta: Meta = {
  title: 'Layouts/Page Layouts',
  parameters: {
    layout: 'fullscreen',
    docs: {
      description: {
        component: `
# Page Layout Patterns

AtomyQ uses consistent page layouts across all modules. Each layout follows the global shell structure with variations for different content types.

## Global Shell Structure

\`\`\`
┌──────────────────────────────────────────────────────┐
│ Sidebar (240px / 64px collapsed)  │ Header (56px)    │
│                                   │──────────────────│
│ • Logo                            │ Content Area     │
│ • Nav sections                    │                  │
│   – Workspace                     │ • Page title     │
│   – Collaboration                 │ • Breadcrumbs    │
│   – Governance                    │ • Actions        │
│   – Administration                │ • Main content   │
│                                   │                  │
│ • Collapse toggle                 │──────────────────│
│                                   │ Footer (32px)    │
└──────────────────────────────────────────────────────┘
\`\`\`

## Grid System
- **12-column grid** with 16px gutters
- Content max-width: **1440px**
- Side padding: 24px (desktop), 16px (tablet)
        `,
      },
    },
  },
};

export default meta;

const AppShell = ({ children, title, breadcrumbs, actions }: { 
  children: React.ReactNode; 
  title: string; 
  breadcrumbs?: { label: string; href?: string }[];
  actions?: React.ReactNode;
}) => {
  const [collapsed, setCollapsed] = useState(false);
  
  return (
    <div className="flex h-screen bg-[var(--aq-bg-canvas)]">
      <AtomyQSidebar 
        sections={navigationItems} 
        collapsed={collapsed}
        onToggleCollapse={() => setCollapsed(!collapsed)}
        activeHref="/rfqs"
      />
      <div className="flex-1 flex flex-col min-w-0">
        <header className="h-14 border-b border-[var(--aq-border-default)] bg-white px-6 flex items-center justify-between shrink-0">
          <div className="flex items-center gap-4">
            {breadcrumbs && <AtomyQBreadcrumb items={breadcrumbs} />}
          </div>
          <div className="flex items-center gap-3">
            <button className="p-2 rounded-md hover:bg-[var(--aq-bg-elevated)] text-[var(--aq-text-muted)]">
              <Bell className="size-5" />
            </button>
            <button className="p-2 rounded-md hover:bg-[var(--aq-bg-elevated)] text-[var(--aq-text-muted)]">
              <HelpCircle className="size-5" />
            </button>
            <button className="size-8 rounded-full bg-[var(--aq-brand-600)] text-white flex items-center justify-center text-sm font-medium">
              SC
            </button>
          </div>
        </header>
        <div className="flex-1 overflow-auto">
          <div className="px-6 py-4 border-b border-[var(--aq-border-default)] bg-white">
            <div className="flex items-center justify-between">
              <h1 className="text-xl font-semibold text-[var(--aq-text-primary)]">{title}</h1>
              {actions}
            </div>
          </div>
          <main className="p-6">
            {children}
          </main>
        </div>
      </div>
    </div>
  );
};

// ==================== DASHBOARD LAYOUT ====================

export const DashboardLayout: StoryObj = {
  name: 'Dashboard Layout',
  render: () => (
    <AppShell 
      title="Dashboard" 
      breadcrumbs={[{ label: 'Home' }]}
      actions={
        <div className="flex gap-2">
          <AtomyQButton variant="outline" size="sm"><Download className="size-4" /> Export</AtomyQButton>
          <AtomyQButton variant="primary" size="sm"><Plus className="size-4" /> New RFQ</AtomyQButton>
        </div>
      }
    >
      <div className="space-y-6">
        {/* ASCII Diagram */}
        <div className="p-4 bg-[var(--aq-bg-elevated)] rounded-lg border border-[var(--aq-border-default)] font-mono text-xs text-[var(--aq-text-muted)]">
          <pre>{`
Dashboard Layout Structure:
┌─────┬─────┬─────┬─────┐
│ KPI │ KPI │ KPI │ KPI │  ← 4-column KPI grid
├───────────┬─────────────┤
│ Chart /   │ Tasks /     │
│ Table     │ Alerts      │
│ (8 cols)  │ (4 cols)    │  ← 8:4 content split
└───────────┴─────────────┘
          `}</pre>
        </div>
        
        {/* KPI Cards - 4 column grid */}
        <div className="grid grid-cols-4 gap-4">
          <AtomyQKPICard label="Active RFQs" value="24" change="+12%" trend="up" period="vs last month" icon={<FileText className="size-4" />} />
          <AtomyQKPICard label="Savings Rate" value="8.2%" change="+2.1%" trend="up" period="this quarter" icon={<TrendingUp className="size-4" />} />
          <AtomyQKPICard label="Avg Response" value="2.3 days" change="-0.5" trend="up" period="improving" icon={<Clock className="size-4" />} />
          <AtomyQKPICard label="AI Accuracy" value="94%" change="+3%" trend="up" period="vs baseline" icon={<Sparkles className="size-4" />} />
        </div>
        
        {/* 8:4 Content Split */}
        <div className="grid grid-cols-12 gap-6">
          <div className="col-span-8 bg-white rounded-lg border border-[var(--aq-border-default)] p-6">
            <h3 className="font-medium text-[var(--aq-text-primary)] mb-4">Recent Activity Chart</h3>
            <div className="h-64 bg-[var(--aq-bg-elevated)] rounded flex items-center justify-center text-[var(--aq-text-muted)]">
              Chart Area (8 columns)
            </div>
          </div>
          <div className="col-span-4 bg-white rounded-lg border border-[var(--aq-border-default)] p-6">
            <h3 className="font-medium text-[var(--aq-text-primary)] mb-4">Pending Tasks</h3>
            <div className="space-y-3">
              {['Review RFQ-2026-001', 'Approve comparison', 'Follow up with vendor'].map((task, i) => (
                <div key={i} className="flex items-center gap-2 p-2 rounded hover:bg-[var(--aq-bg-elevated)]">
                  <div className="size-2 rounded-full bg-[var(--aq-warning-500)]" />
                  <span className="text-sm text-[var(--aq-text-secondary)]">{task}</span>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </AppShell>
  ),
  parameters: {
    docs: {
      description: {
        story: `
## Dashboard Layout

KPI cards in a 4-column grid at top, followed by 2-column main/side layout below.

\`\`\`
┌─────┬─────┬─────┬─────┐
│ KPI │ KPI │ KPI │ KPI │
├───────────┬─────────────┤
│ Chart /   │ Tasks /     │
│ Table     │ Alerts      │
│ (8 cols)  │ (4 cols)    │
└───────────┴─────────────┘
\`\`\`

**Use for:** Home dashboards, analytics views, executive summaries
        `,
      },
    },
  },
};

// ==================== DATA TABLE LAYOUT ====================

export const DataTableLayout: StoryObj = {
  name: 'Data Table Layout',
  render: () => (
    <AppShell 
      title="RFQ List" 
      breadcrumbs={[{ label: 'Workspace', href: '/' }, { label: 'RFQs' }]}
      actions={
        <div className="flex gap-2">
          <AtomyQButton variant="outline" size="sm"><Download className="size-4" /> Export</AtomyQButton>
          <AtomyQButton variant="primary" size="sm"><Plus className="size-4" /> Create RFQ</AtomyQButton>
        </div>
      }
    >
      <div className="space-y-4">
        {/* ASCII Diagram */}
        <div className="p-4 bg-[var(--aq-bg-elevated)] rounded-lg border border-[var(--aq-border-default)] font-mono text-xs text-[var(--aq-text-muted)]">
          <pre>{`
Data Table Layout Structure:
┌─────────────────────────┐
│ Filter bar + Actions    │  ← Search, filters, bulk actions
├─────────────────────────┤
│ Data Table (12 cols)    │
│ • Sortable headers      │
│ • Row selection         │  ← Full-width table
│ • Inline status badges  │
├─────────────────────────┤
│ Pagination              │  ← Page controls
└─────────────────────────┘
          `}</pre>
        </div>
        
        {/* Filter Bar */}
        <div className="bg-white rounded-lg border border-[var(--aq-border-default)] p-4">
          <div className="flex items-center gap-3">
            <div className="flex-1 max-w-sm">
              <AtomyQInput placeholder="Search RFQs..." leftIcon={<Search className="size-4" />} />
            </div>
            <AtomyQButton variant="outline" size="sm"><Filter className="size-4" /> Filters</AtomyQButton>
            <div className="flex-1" />
            <span className="text-sm text-[var(--aq-text-muted)]">32 results</span>
          </div>
        </div>
        
        {/* Table Area */}
        <div className="bg-white rounded-lg border border-[var(--aq-border-default)]">
          <div className="h-[400px] flex items-center justify-center text-[var(--aq-text-muted)]">
            Full-width Data Table (12 columns)
          </div>
          <div className="border-t border-[var(--aq-border-default)] px-4 py-3 flex items-center justify-between">
            <span className="text-sm text-[var(--aq-text-muted)]">Showing 1-25 of 32</span>
            <div className="flex gap-1">
              <button className="px-3 py-1 text-sm rounded border border-[var(--aq-border-default)]">Prev</button>
              <button className="px-3 py-1 text-sm rounded bg-[var(--aq-brand-600)] text-white">1</button>
              <button className="px-3 py-1 text-sm rounded border border-[var(--aq-border-default)]">2</button>
              <button className="px-3 py-1 text-sm rounded border border-[var(--aq-border-default)]">Next</button>
            </div>
          </div>
        </div>
      </div>
    </AppShell>
  ),
  parameters: {
    docs: {
      description: {
        story: `
## Data Table Layout

Full-width table with filter bar above and pagination below.

\`\`\`
┌─────────────────────────┐
│ Filter bar + Actions    │
├─────────────────────────┤
│ Data Table (12 cols)    │
│ • Sortable headers      │
│ • Row selection         │
│ • Inline status badges  │
├─────────────────────────┤
│ Pagination              │
└─────────────────────────┘
\`\`\`

**Use for:** List views, search results, data grids
        `,
      },
    },
  },
};

// ==================== FORM LAYOUT ====================

export const FormLayout: StoryObj = {
  name: 'Form Layout',
  render: () => (
    <AppShell 
      title="Create RFQ" 
      breadcrumbs={[{ label: 'Workspace', href: '/' }, { label: 'RFQs', href: '/rfqs' }, { label: 'New' }]}
    >
      <div className="space-y-4">
        {/* ASCII Diagram */}
        <div className="p-4 bg-[var(--aq-bg-elevated)] rounded-lg border border-[var(--aq-border-default)] font-mono text-xs text-[var(--aq-text-muted)]">
          <pre>{`
Form Layout Structure:
┌───────────┬─────────────┐
│ Form      │ Summary     │
│ Fields    │ Panel       │
│ (8 cols)  │ (4 cols)    │  ← 8:4 split
├───────────┴─────────────┤
│ Form actions (right)    │  ← Sticky footer
└─────────────────────────┘
          `}</pre>
        </div>
        
        {/* 8:4 Form Split */}
        <div className="grid grid-cols-12 gap-6">
          {/* Form Fields - 8 columns */}
          <div className="col-span-8 bg-white rounded-lg border border-[var(--aq-border-default)] p-6">
            <h3 className="font-medium text-[var(--aq-text-primary)] mb-4">RFQ Details</h3>
            <div className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <AtomyQInput label="Title" placeholder="Enter title..." required />
                <AtomyQSelect label="Category" placeholder="Select..." options={[
                  { value: 'equipment', label: 'Equipment' },
                  { value: 'services', label: 'Services' },
                ]} required />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <AtomyQInput label="Budget" placeholder="0.00" type="number" />
                <AtomyQInput label="Deadline" type="date" />
              </div>
              <AtomyQTextarea label="Description" placeholder="Describe requirements..." />
            </div>
          </div>
          
          {/* Summary Panel - 4 columns */}
          <div className="col-span-4">
            <div className="bg-white rounded-lg border border-[var(--aq-border-default)] p-6 sticky top-6">
              <h3 className="font-medium text-[var(--aq-text-primary)] mb-4">Summary</h3>
              <div className="space-y-3 text-sm">
                <div className="flex justify-between">
                  <span className="text-[var(--aq-text-muted)]">Status</span>
                  <span className="text-[var(--aq-text-primary)]">Draft</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-[var(--aq-text-muted)]">Created</span>
                  <span className="text-[var(--aq-text-primary)]">Just now</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-[var(--aq-text-muted)]">Items</span>
                  <span className="text-[var(--aq-text-primary)]">0</span>
                </div>
              </div>
              <div className="mt-6 pt-4 border-t border-[var(--aq-border-default)]">
                <AtomyQButton variant="primary" className="w-full">Save & Continue</AtomyQButton>
              </div>
            </div>
          </div>
        </div>
        
        {/* Form Actions Footer */}
        <div className="bg-white rounded-lg border border-[var(--aq-border-default)] p-4 flex justify-end gap-2">
          <AtomyQButton variant="outline">Save as Draft</AtomyQButton>
          <AtomyQButton variant="primary">Create & Publish</AtomyQButton>
        </div>
      </div>
    </AppShell>
  ),
  parameters: {
    docs: {
      description: {
        story: `
## Form Layout

Two-column form with sidebar summary panel.

\`\`\`
┌───────────┬─────────────┐
│ Form      │ Summary     │
│ Fields    │ Panel       │
│ (8 cols)  │ (4 cols)    │
├───────────┴─────────────┤
│ Form actions (right)    │
└─────────────────────────┘
\`\`\`

**Use for:** Creation forms, edit forms, wizards
        `,
      },
    },
  },
};

// ==================== DETAIL VIEW LAYOUT ====================

export const DetailViewLayout: StoryObj = {
  name: 'Detail View Layout',
  render: () => (
    <AppShell 
      title="RFQ-2026-001" 
      breadcrumbs={[
        { label: 'Workspace', href: '/' }, 
        { label: 'RFQs', href: '/rfqs' }, 
        { label: 'RFQ-2026-001' }
      ]}
      actions={
        <div className="flex gap-2">
          <AtomyQButton variant="outline" size="sm">Edit</AtomyQButton>
          <AtomyQButton variant="primary" size="sm">Run Comparison</AtomyQButton>
        </div>
      }
    >
      <div className="space-y-4">
        {/* ASCII Diagram */}
        <div className="p-4 bg-[var(--aq-bg-elevated)] rounded-lg border border-[var(--aq-border-default)] font-mono text-xs text-[var(--aq-text-muted)]">
          <pre>{`
Detail View Layout Structure:
┌─────────────────────────┐
│ Entity Header + Actions │  ← Title, status, primary actions
├─────────────────────────┤
│ Tab Bar                 │  ← Content navigation
├─────────────────────────┤
│ Tab Content             │  ← Dynamic content area
└─────────────────────────┘
          `}</pre>
        </div>
        
        {/* Entity Header */}
        <div className="bg-white rounded-lg border border-[var(--aq-border-default)] p-6">
          <div className="flex items-start justify-between">
            <div>
              <div className="flex items-center gap-2 mb-1">
                <span className="px-2 py-0.5 bg-[var(--aq-info-100)] text-[var(--aq-info-700)] text-xs rounded-full font-medium">Open</span>
                <span className="px-2 py-0.5 bg-[var(--aq-warning-100)] text-[var(--aq-warning-700)] text-xs rounded-full font-medium">High Priority</span>
              </div>
              <h2 className="text-lg font-semibold text-[var(--aq-text-primary)]">Industrial Pumping Equipment</h2>
              <p className="text-sm text-[var(--aq-text-muted)]">Equipment • Budget: RM 450,000 • Deadline: Mar 15, 2026</p>
            </div>
            <div className="text-right">
              <p className="text-sm text-[var(--aq-text-muted)]">4 Vendors Invited</p>
              <p className="text-sm text-[var(--aq-text-muted)]">3 Quotes Received</p>
            </div>
          </div>
        </div>
        
        {/* Tabbed Content */}
        <div className="bg-white rounded-lg border border-[var(--aq-border-default)]">
          <AtomyQTabs
            items={[
              { value: 'details', label: 'Details' },
              { value: 'quotes', label: 'Quotes', badge: '3' },
              { value: 'comparison', label: 'Comparison' },
              { value: 'history', label: 'History' },
            ]}
            defaultValue="details"
          >
            <AtomyQTabContent value="details">
              <div className="p-6">
                <p className="text-sm text-[var(--aq-text-muted)]">Tab content area - Details view</p>
              </div>
            </AtomyQTabContent>
          </AtomyQTabs>
        </div>
      </div>
    </AppShell>
  ),
  parameters: {
    docs: {
      description: {
        story: `
## Detail View Layout

Header section with entity info, tabbed content below.

\`\`\`
┌─────────────────────────┐
│ Entity Header + Actions │
├─────────────────────────┤
│ Tab Bar                 │
├─────────────────────────┤
│ Tab Content             │
└─────────────────────────┘
\`\`\`

**Use for:** Entity detail pages, record views, document views
        `,
      },
    },
  },
};

// ==================== MULTI-PANEL LAYOUT ====================

export const MultiPanelLayout: StoryObj = {
  name: 'Multi-Panel Layout (Comparison)',
  render: () => (
    <AppShell 
      title="Quote Comparison" 
      breadcrumbs={[
        { label: 'Workspace', href: '/' }, 
        { label: 'RFQs', href: '/rfqs' }, 
        { label: 'RFQ-2026-001', href: '/rfqs/001' },
        { label: 'Comparison' }
      ]}
      actions={
        <div className="flex gap-2">
          <AtomyQButton variant="outline" size="sm"><Download className="size-4" /> Export</AtomyQButton>
          <AtomyQButton variant="success" size="sm">Approve Selection</AtomyQButton>
        </div>
      }
    >
      <div className="space-y-4">
        {/* ASCII Diagram */}
        <div className="p-4 bg-[var(--aq-bg-elevated)] rounded-lg border border-[var(--aq-border-default)] font-mono text-xs text-[var(--aq-text-muted)]">
          <pre>{`
Multi-Panel Layout Structure:
┌───────┬───────┬───────┬───────┐
│ Item  │Vendor │Vendor │Vendor │
│ List  │  A    │  B    │  C    │
│(3col) │(3col) │(3col) │(3col) │  ← Equal columns
└───────┴───────┴───────┴───────┘
          `}</pre>
        </div>
        
        {/* Multi-Panel Comparison */}
        <div className="grid grid-cols-12 gap-4">
          {/* Item List - 3 columns */}
          <div className="col-span-3 bg-white rounded-lg border border-[var(--aq-border-default)] p-4">
            <h3 className="font-medium text-[var(--aq-text-primary)] text-sm mb-3">Line Items</h3>
            <div className="space-y-2">
              {['Industrial Pump', 'Motor Assembly', 'Control Panel', 'Installation'].map((item, i) => (
                <div key={i} className="p-2 rounded bg-[var(--aq-bg-elevated)] text-sm hover:bg-[var(--aq-brand-50)] cursor-pointer">
                  {item}
                </div>
              ))}
            </div>
          </div>
          
          {/* Vendor Columns - 3 columns each */}
          {['Apex Industrial', 'TechFlow Systems', 'Global Pumps'].map((vendor, i) => (
            <div key={i} className={`col-span-3 bg-white rounded-lg border ${i === 0 ? 'border-[var(--aq-success-500)] border-2' : 'border-[var(--aq-border-default)]'} p-4`}>
              <div className="flex items-center justify-between mb-3">
                <h3 className="font-medium text-[var(--aq-text-primary)] text-sm">{vendor}</h3>
                {i === 0 && <span className="px-2 py-0.5 bg-[var(--aq-success-100)] text-[var(--aq-success-700)] text-xs rounded-full">Recommended</span>}
              </div>
              <div className="space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-[var(--aq-text-muted)]">Total</span>
                  <span className="font-medium">RM {(420000 + i * 15000).toLocaleString()}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-[var(--aq-text-muted)]">Score</span>
                  <span className="font-medium">{92 - i * 5}/100</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-[var(--aq-text-muted)]">Delivery</span>
                  <span>{14 + i * 7} days</span>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </AppShell>
  ),
  parameters: {
    docs: {
      description: {
        story: `
## Multi-Panel Layout (Comparison)

Side-by-side panels for comparing vendor quotes.

\`\`\`
┌───────┬───────┬───────┬───────┐
│ Item  │Vendor │Vendor │Vendor │
│ List  │  A    │  B    │  C    │
│(3col) │(3col) │(3col) │(3col) │
└───────┴───────┴───────┴───────┘
\`\`\`

**Use for:** Comparison views, side-by-side analysis, multi-document views
        `,
      },
    },
  },
};

// ==================== SETTINGS LAYOUT ====================

export const SettingsLayout: StoryObj = {
  name: 'Settings Layout',
  render: () => (
    <AppShell 
      title="Settings" 
      breadcrumbs={[{ label: 'Administration', href: '/admin' }, { label: 'Settings' }]}
    >
      <div className="space-y-4">
        {/* ASCII Diagram */}
        <div className="p-4 bg-[var(--aq-bg-elevated)] rounded-lg border border-[var(--aq-border-default)] font-mono text-xs text-[var(--aq-text-muted)]">
          <pre>{`
Settings Layout Structure:
┌───────────┬─────────────────┐
│ Section   │ Content         │
│ Nav       │ Area            │
│ (3 cols)  │ (9 cols)        │  ← Sidebar navigation + content
└───────────┴─────────────────┘
          `}</pre>
        </div>
        
        {/* Settings Layout */}
        <div className="grid grid-cols-12 gap-6">
          {/* Section Nav - 3 columns */}
          <div className="col-span-3">
            <nav className="bg-white rounded-lg border border-[var(--aq-border-default)] p-2">
              {['General', 'Notifications', 'Security', 'Integrations', 'Billing'].map((section, i) => (
                <a 
                  key={section}
                  href="#"
                  className={`block px-3 py-2 rounded text-sm ${i === 0 ? 'bg-[var(--aq-brand-50)] text-[var(--aq-brand-700)] font-medium' : 'text-[var(--aq-text-secondary)] hover:bg-[var(--aq-bg-elevated)]'}`}
                >
                  {section}
                </a>
              ))}
            </nav>
          </div>
          
          {/* Content Area - 9 columns */}
          <div className="col-span-9 bg-white rounded-lg border border-[var(--aq-border-default)] p-6">
            <h3 className="font-medium text-[var(--aq-text-primary)] mb-4">General Settings</h3>
            <div className="space-y-4">
              <AtomyQInput label="Organization Name" placeholder="Acme Corp" />
              <AtomyQSelect label="Default Currency" options={[
                { value: 'myr', label: 'MYR - Malaysian Ringgit' },
                { value: 'usd', label: 'USD - US Dollar' },
              ]} value="myr" />
              <AtomyQSelect label="Timezone" options={[
                { value: 'asia/kl', label: 'Asia/Kuala_Lumpur (GMT+8)' },
              ]} value="asia/kl" />
            </div>
          </div>
        </div>
      </div>
    </AppShell>
  ),
  parameters: {
    docs: {
      description: {
        story: `
## Settings Layout

Sidebar navigation with content area for settings pages.

\`\`\`
┌───────────┬─────────────────┐
│ Section   │ Content         │
│ Nav       │ Area            │
│ (3 cols)  │ (9 cols)        │
└───────────┴─────────────────┘
\`\`\`

**Use for:** Settings pages, preference screens, configuration panels
        `,
      },
    },
  },
};

// ==================== LAYOUT COMPARISON ====================

export const LayoutComparison: StoryObj = {
  name: 'All Layouts Overview',
  render: () => (
    <div className="p-8 space-y-8 bg-[var(--aq-bg-canvas)] min-h-screen">
      <h1 className="text-2xl font-semibold text-[var(--aq-text-primary)]">Page Layout Patterns</h1>
      
      <div className="grid grid-cols-2 gap-6">
        {/* Dashboard */}
        <div className="bg-white rounded-lg border border-[var(--aq-border-default)] p-4">
          <h3 className="font-medium text-[var(--aq-text-primary)] mb-2">Dashboard Layout</h3>
          <pre className="text-xs font-mono text-[var(--aq-text-muted)] bg-[var(--aq-bg-elevated)] p-3 rounded">{`┌─────┬─────┬─────┬─────┐
│ KPI │ KPI │ KPI │ KPI │
├───────────┬─────────────┤
│ Main (8)  │ Side (4)    │
└───────────┴─────────────┘`}</pre>
          <p className="text-sm text-[var(--aq-text-muted)] mt-2">Home dashboards, analytics</p>
        </div>
        
        {/* Data Table */}
        <div className="bg-white rounded-lg border border-[var(--aq-border-default)] p-4">
          <h3 className="font-medium text-[var(--aq-text-primary)] mb-2">Data Table Layout</h3>
          <pre className="text-xs font-mono text-[var(--aq-text-muted)] bg-[var(--aq-bg-elevated)] p-3 rounded">{`┌─────────────────────────┐
│ Filter bar              │
├─────────────────────────┤
│ Data Table (12 cols)    │
├─────────────────────────┤
│ Pagination              │
└─────────────────────────┘`}</pre>
          <p className="text-sm text-[var(--aq-text-muted)] mt-2">List views, search results</p>
        </div>
        
        {/* Form */}
        <div className="bg-white rounded-lg border border-[var(--aq-border-default)] p-4">
          <h3 className="font-medium text-[var(--aq-text-primary)] mb-2">Form Layout</h3>
          <pre className="text-xs font-mono text-[var(--aq-text-muted)] bg-[var(--aq-bg-elevated)] p-3 rounded">{`┌───────────┬─────────────┐
│ Form (8)  │ Summary (4) │
├───────────┴─────────────┤
│ Actions                 │
└─────────────────────────┘`}</pre>
          <p className="text-sm text-[var(--aq-text-muted)] mt-2">Creation forms, edit pages</p>
        </div>
        
        {/* Detail View */}
        <div className="bg-white rounded-lg border border-[var(--aq-border-default)] p-4">
          <h3 className="font-medium text-[var(--aq-text-primary)] mb-2">Detail View Layout</h3>
          <pre className="text-xs font-mono text-[var(--aq-text-muted)] bg-[var(--aq-bg-elevated)] p-3 rounded">{`┌─────────────────────────┐
│ Entity Header + Actions │
├─────────────────────────┤
│ Tab Bar                 │
├─────────────────────────┤
│ Tab Content             │
└─────────────────────────┘`}</pre>
          <p className="text-sm text-[var(--aq-text-muted)] mt-2">Record detail pages</p>
        </div>
        
        {/* Multi-Panel */}
        <div className="bg-white rounded-lg border border-[var(--aq-border-default)] p-4">
          <h3 className="font-medium text-[var(--aq-text-primary)] mb-2">Multi-Panel Layout</h3>
          <pre className="text-xs font-mono text-[var(--aq-text-muted)] bg-[var(--aq-bg-elevated)] p-3 rounded">{`┌───────┬───────┬───────┐
│ List  │ Col A │ Col B │
│  (3)  │  (4)  │  (4)  │
└───────┴───────┴───────┘`}</pre>
          <p className="text-sm text-[var(--aq-text-muted)] mt-2">Comparison views</p>
        </div>
        
        {/* Settings */}
        <div className="bg-white rounded-lg border border-[var(--aq-border-default)] p-4">
          <h3 className="font-medium text-[var(--aq-text-primary)] mb-2">Settings Layout</h3>
          <pre className="text-xs font-mono text-[var(--aq-text-muted)] bg-[var(--aq-bg-elevated)] p-3 rounded">{`┌───────────┬─────────────┐
│ Nav (3)   │ Content (9) │
│           │             │
└───────────┴─────────────┘`}</pre>
          <p className="text-sm text-[var(--aq-text-muted)] mt-2">Configuration screens</p>
        </div>
      </div>
    </div>
  ),
  parameters: {
    layout: 'fullscreen',
    docs: {
      description: {
        story: 'Quick reference showing all page layout patterns with their ASCII diagrams.',
      },
    },
  },
};

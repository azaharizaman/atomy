import React from 'react';
import {
  Palette, Type, LayoutGrid, MousePointer2, Tag, FormInput,
  Table2, Layers, Navigation, SlidersHorizontal, Bell, BookOpen,
  ChevronRight, BarChart2, Clock, Eye, FileText, Users,
  Award, List, FileCheck, ShieldCheck, GitBranch, AlertTriangle,
  Plus, Download, RefreshCw, Lock, CheckCircle2, Upload, Star,
  FolderArchive, Settings, Sparkles, Search
} from 'lucide-react';

// Import DS components
import { Button, IconButton } from '../components/ds/Button';
import {
  StatusBadge, SLATimerBadge, ConfidenceBadge, VersionChip, CountBadge, StatusDot
} from '../components/ds/Badge';
import { Avatar, AvatarLabel, AvatarStack } from '../components/ds/Avatar';
import {
  TextInput, SearchInput, SelectInput, Textarea, Checkbox, ToggleSwitch, FilterChip
} from '../components/ds/Input';
import { Card, SectionCard, InfoGrid, EmptyState, UploadZone, DocPreview } from '../components/ds/Card';
import { PrimaryTabs, SecondaryTabs, VerticalTabs, TabPanel } from '../components/ds/Tabs';
import { BreadcrumbBar } from '../components/ds/BreadcrumbBar';
import { ProgressBar, CircularProgress, MiniProgress } from '../components/ds/Progress';
import { KPIScorecard, MetricChip, MetricRow } from '../components/ds/KPIScorecard';
import { DataTable } from '../components/ds/DataTable';
import type { ColumnDef } from '../components/ds/DataTable';
import { TableFooter, Pagination } from '../components/ds/Pagination';
import { SlideOver, SlideOverSection } from '../components/ds/SlideOver';
import { Timeline, ActivityFeed } from '../components/ds/Timeline';
import { Alert, Banner, Checklist } from '../components/ds/Alert';
import { NavItem, NavGroup, SubNavItem, NavigationLink, NavLabel } from '../components/ds/Sidebar';
import { FilterBar, PageHeader, SectionHeader } from '../components/ds/FilterBar';
import { ActiveRecordSnippet, ActiveRecordMenu } from '../components/ds/ActiveRecordMenu';
import { MainNav } from '../components/ds/MainNav';
import { WorkspaceBreadcrumbs } from '../components/ds/WorkspaceBreadcrumbs';
import { RecordHeader } from '../components/ds/RecordHeader';
import { SlideOverStackManager, type SlideOverStackItem } from '../components/ds/SlideOverStackManager';
import { TopBar } from '../components/ds/TopBar';
import { SignInCard, MfaPromptPanel } from '../components/ds/AuthComponents';
import { Stepper, StickyActionBar, LineItemEditor, UploadDropzoneWithProgress } from '../components/ds/CreateRFQComponents';
import type { LineItem, UploadItemProgress } from '../components/ds/CreateRFQComponents';
import { QuoteDetailActionBar, ValidationCallout, OverrideChip, RevertControl } from '../components/ds/QuoteIntakeComponents';
import { ConversionBadge, ConflictIndicator, NormalizationLockBar, MappingGrid } from '../components/ds/NormalizationComponents';
import type { MappingGridRow } from '../components/ds/NormalizationComponents';
import { ComparisonMatrixGrid, RecommendationCard, ApprovalGateBanner, ReadinessBanner, DeltaBadge } from '../components/ds/ComparisonComponents';
import type { ComparisonMatrixRow } from '../components/ds/ComparisonComponents';
import { PriorityMarker, AssignmentControl, SnoozeControl, EvidenceTabsPanel, DecisionPanel } from '../components/ds/ApprovalComponents';
import { AwardDecisionSummary, SplitAllocationEditor, SignOffChecklist, DebriefStatusList, HandoffStatusTimeline, PayloadPreviewPanel, ProtestTimerBadge } from '../components/ds/AwardComponents';
import type { StatusVariant } from '../components/ds/tokens';

// ─── Showcase Navigation Sections ─────────────────────────────────────────────

const SECTIONS = [
  { id: 'foundation',    label: 'Foundation',       icon: <Palette size={14} /> },
  { id: 'typography',    label: 'Typography',        icon: <Type size={14} /> },
  { id: 'buttons',       label: 'Buttons',           icon: <MousePointer2 size={14} /> },
  { id: 'badges',        label: 'Badges & Status',   icon: <Tag size={14} /> },
  { id: 'avatar',        label: 'Avatars',           icon: <Users size={14} /> },
  { id: 'inputs',        label: 'Form Controls',     icon: <FormInput size={14} /> },
  { id: 'cards',         label: 'Cards & Surfaces',  icon: <Layers size={14} /> },
  { id: 'tabs',          label: 'Tabs',              icon: <LayoutGrid size={14} /> },
  { id: 'navigation',    label: 'Navigation',        icon: <Navigation size={14} /> },
  { id: 'data-display',  label: 'Data Display',      icon: <Table2 size={14} /> },
  { id: 'progress',      label: 'Progress',          icon: <BarChart2 size={14} /> },
  { id: 'timeline',      label: 'Timeline',          icon: <Clock size={14} /> },
  { id: 'alerts',        label: 'Alerts & Banners',  icon: <Bell size={14} /> },
  { id: 'slideover',     label: 'Slide-Over',        icon: <SlidersHorizontal size={14} /> },
  { id: 'p1-workflows',  label: 'P1 Workflow Blocks', icon: <GitBranch size={14} /> },
  { id: 'layouts',       label: 'Layouts',           icon: <BookOpen size={14} /> },
];

// ─── Helper: Section Shell ─────────────────────────────────────────────────────

function Section({ id, title, subtitle, children }: {
  id: string; title: string; subtitle?: string; children: React.ReactNode;
}) {
  return (
    <section id={id} className="mb-16">
      <div className="mb-6 pb-3 border-b border-slate-200">
        <h2 className="text-xl font-semibold text-slate-900">{title}</h2>
        {subtitle && <p className="text-sm text-slate-500 mt-1">{subtitle}</p>}
      </div>
      {children}
    </section>
  );
}

function SubSection({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <div className="mb-8">
      <h3 className="text-sm font-semibold text-slate-600 mb-3 uppercase tracking-wide">{title}</h3>
      {children}
    </div>
  );
}

function TokenRow({ name, value, swatch }: { name: string; value: string; swatch?: string }) {
  return (
    <div className="flex items-center gap-3 py-1.5 border-b border-slate-100 last:border-0">
      {swatch && <div className={`w-6 h-6 rounded border border-black/10 shrink-0 ${swatch}`} />}
      <code className="text-xs font-mono text-indigo-700 w-48 shrink-0">{name}</code>
      <span className="text-xs text-slate-500">{value}</span>
    </div>
  );
}

// ─── Sample Data for DataTable ────────────────────────────────────────────────

interface RFQRow {
  id: string;
  rfqId: string;
  title: string;
  owner: string;
  status: StatusVariant;
  deadline: string;
  estValue: string;
  category: string;
  vendors: number;
  quotes: number;
  savings: string;
}

const RFQ_ROWS: RFQRow[] = [
  { id: '1', rfqId: 'RFQ-2401', title: 'Server Infrastructure Refresh', owner: 'Alex Kumar',    status: 'active',  deadline: '2026-04-15', estValue: '$1,200,000', category: 'IT Hardware',  vendors: 5, quotes: 8, savings: '12%' },
  { id: '2', rfqId: 'RFQ-2402', title: 'Office Furniture Q2',           owner: 'Sarah Chen',    status: 'pending', deadline: '2026-03-28', estValue: '$84,500',    category: 'Facilities',   vendors: 3, quotes: 3, savings: '—'   },
  { id: '3', rfqId: 'RFQ-2403', title: 'Cloud Software Licenses',       owner: 'Marcus Webb',   status: 'active',  deadline: '2026-04-02', estValue: '$340,000',   category: 'Software',     vendors: 4, quotes: 6, savings: '8%'  },
  { id: '4', rfqId: 'RFQ-2404', title: 'Network Security Audit',        owner: 'Priya Nair',    status: 'awarded', deadline: '2026-03-10', estValue: '$95,000',    category: 'Security',     vendors: 2, quotes: 2, savings: '5%'  },
  { id: '5', rfqId: 'RFQ-2405', title: 'Marketing Print Materials',     owner: 'James Okonkwo', status: 'draft',   deadline: '2026-05-01', estValue: '$12,000',    category: 'Marketing',    vendors: 0, quotes: 0, savings: '—'   },
  { id: '6', rfqId: 'RFQ-2406', title: 'Fleet Vehicle Leasing',         owner: 'Sarah Chen',    status: 'closed',  deadline: '2026-02-28', estValue: '$480,000',   category: 'Transport',    vendors: 6, quotes: 6, savings: '3%'  },
  { id: '7', rfqId: 'RFQ-2407', title: 'Catering Services Contract',    owner: 'Alex Kumar',    status: 'active',  deadline: '2026-04-20', estValue: '$72,000',    category: 'Facilities',   vendors: 4, quotes: 5, savings: '14%' },
  { id: '8', rfqId: 'RFQ-2408', title: 'IT Support Annual',             owner: 'Marcus Webb',   status: 'pending', deadline: '2026-03-31', estValue: '$220,000',   category: 'IT Services',  vendors: 2, quotes: 1, savings: '—'   },
];

const RFQ_COLUMNS: ColumnDef<RFQRow>[] = [
  {
    key: 'rfqId', label: 'ID', width: '90px', sortable: true,
    render: row => <span className="font-mono text-xs font-medium text-slate-600">{row.rfqId}</span>,
  },
  {
    key: 'title', label: 'RFQ Title', sortable: true,
    render: row => (
      <div>
        <div className="text-sm font-medium text-slate-800 leading-tight">{row.title}</div>
        <div className="flex items-center gap-1.5 mt-0.5">
          <Avatar name={row.owner} size="xs" />
          <span className="text-[11px] text-slate-400">{row.owner}</span>
        </div>
      </div>
    ),
  },
  {
    key: 'status', label: 'Status', width: '100px', sortable: true,
    render: row => <StatusBadge status={row.status} />,
  },
  {
    key: 'deadline', label: 'Deadline', width: '100px', sortable: true,
    render: row => <span className="text-xs text-slate-600 tabular-nums">{row.deadline}</span>,
  },
  {
    key: 'estValue', label: 'Est. Value', width: '110px', align: 'right', sortable: true,
    render: row => <span className="text-sm font-medium text-slate-800 tabular-nums">{row.estValue}</span>,
  },
];

// ─── Showcase Page ─────────────────────────────────────────────────────────────

export function ShowcasePage() {
  const [activeSection, setActiveSection] = React.useState('foundation');
  const [activeTab, setActiveTab] = React.useState('tab1');
  const [activeSecondaryTab, setActiveSecondaryTab] = React.useState('freelancer');
  const [selectedIds, setSelectedIds] = React.useState<(string | number)[]>(['1', '2']);
  const [expandedId, setExpandedId] = React.useState<string | number | null>('1');
  const [page, setPage] = React.useState(1);
  const [viewMode, setViewMode] = React.useState<'table' | 'grid'>('table');
  const [tableSearch, setTableSearch] = React.useState('');
  const [statusFilter, setStatusFilter] = React.useState('all');
  const [categoryFilter, setCategoryFilter] = React.useState('all');
  const [recentlyHiredOnly, setRecentlyHiredOnly] = React.useState(false);
  const [advancedFiltersOpen, setAdvancedFiltersOpen] = React.useState(false);
  const [withSavingsOnly, setWithSavingsOnly] = React.useState(false);
  const [minValueFilter, setMinValueFilter] = React.useState('');
  const [maxValueFilter, setMaxValueFilter] = React.useState('');
  const [fromDateFilter, setFromDateFilter] = React.useState('');
  const [toDateFilter, setToDateFilter] = React.useState('');
  const [configOpen, setConfigOpen] = React.useState(false);
  const [recordsPerPage, setRecordsPerPage] = React.useState(8);
  const [showGroupSummaryRow, setShowGroupSummaryRow] = React.useState(true);
  const [showTableSummaryRow, setShowTableSummaryRow] = React.useState(true);
  const [showColumns, setShowColumns] = React.useState<Record<string, boolean>>({
    rfqId: true,
    title: true,
    status: true,
    deadline: true,
    estValue: true,
  });
  const [slideOverOpen, setSlideOverOpen] = React.useState(false);
  const [slideOverWidth, setSlideOverWidth] = React.useState<'sm' | 'md' | 'lg' | 'xl'>('md');
  const [toggleA, setToggleA] = React.useState(false);
  const [toggleB, setToggleB] = React.useState(true);
  const [searchVal, setSearchVal] = React.useState('');
  const [stackPanels, setStackPanels] = React.useState<SlideOverStackItem[]>([]);
  const [lineItems, setLineItems] = React.useState<LineItem[]>([
    { id: 'li-h-1', entryType: 'heading', heading: 'Compute Nodes', subheading: 'Primary server fleet', description: '', qty: 0, unit: 'EA', targetPrice: 0 },
    { id: 'li-1', description: 'Rack Server 2U', qty: 12, unit: 'EA', targetPrice: 8400 },
    { id: 'li-h-2', entryType: 'heading', heading: 'Storage', subheading: '', description: '', qty: 0, unit: 'EA', targetPrice: 0 },
    { id: 'li-2', description: 'Enterprise SSD 3.84TB', qty: 48, unit: 'EA', targetPrice: 620 },
  ]);
  const [uploadItems] = React.useState<UploadItemProgress[]>([
    { id: 'up-1', fileName: 'rfq_specs.pdf', progress: 100 },
    { id: 'up-2', fileName: 'compliance_matrix.xlsx', progress: 72 },
  ]);
  const [selectedMapIds, setSelectedMapIds] = React.useState<string[]>(['map-2', 'map-4']);
  const [approvalAssignee, setApprovalAssignee] = React.useState('marcus');
  const [evidenceTab, setEvidenceTab] = React.useState('documents');
  const [decisionReason, setDecisionReason] = React.useState('');
  const [splitAlloc, setSplitAlloc] = React.useState([
    { id: 'dell', name: 'Dell Technologies', value: 70 },
    { id: 'hp', name: 'HP Enterprise', value: 20 },
    { id: 'lenovo', name: 'Lenovo', value: 10 },
  ]);
  const [signoffs, setSignoffs] = React.useState([
    { id: 'fin', label: 'Financial review complete', checked: true },
    { id: 'legal', label: 'Legal review complete', checked: false },
    { id: 'lead', label: 'Procurement lead sign-off', checked: false },
  ]);

  function scrollToSection(id: string) {
    setActiveSection(id);
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  const ACTIVITY_EVENTS = [
    { id: '1', timestamp: '2 hours ago', actor: 'Alex Kumar', action: 'uploaded quote from Dell Technologies', iconColor: 'indigo' as const, icon: <Upload size={10} /> },
    { id: '2', timestamp: '4 hours ago', actor: 'Priya Nair', action: 'sent invitation to HP Enterprise', iconColor: 'slate' as const },
    { id: '3', timestamp: '6 hours ago', actor: 'System', action: 'normalization completed for Lenovo quote (18/24 lines)', iconColor: 'green' as const, icon: <CheckCircle2 size={10} /> },
    { id: '4', timestamp: 'Yesterday',   actor: 'Alex Kumar', action: 'ran comparison preview — Run #004', iconColor: 'indigo' as const, icon: <BarChart2 size={10} /> },
    { id: '5', timestamp: 'Yesterday',   actor: 'System', action: 'RFQ published and vendors notified', iconColor: 'green' as const, icon: <CheckCircle2 size={10} /> },
    { id: '6', timestamp: '3 days ago',  actor: 'Marcus Webb', action: 'created RFQ-2401', iconColor: 'slate' as const },
  ];

  const MAPPING_ROWS: MappingGridRow[] = [
    { id: 'map-1', lineNumber: 1, vendorDescription: '2U Compute Node - Intel Xeon', confidence: 'high', mappedLine: 'CPU Node', taxonomyCode: '43211503', normalizedQty: '12', normalizedUnit: 'EA', normalizedPrice: '$8,230', currency: 'USD' },
    { id: 'map-2', lineNumber: 2, vendorDescription: 'NVMe SSD 3.84TB', confidence: 'medium', mappedLine: 'Storage SSD', taxonomyCode: '43201803', normalizedQty: '48', normalizedUnit: 'EA', normalizedPrice: '$599', currency: 'USD', conflict: true },
    { id: 'map-3', lineNumber: 3, vendorDescription: 'Rack Rail Kit', confidence: 'high', mappedLine: 'Accessories', taxonomyCode: '43201600', normalizedQty: '12', normalizedUnit: 'SET', normalizedPrice: '$120', currency: 'USD' },
    { id: 'map-4', lineNumber: 4, vendorDescription: '5Y NBD Support', confidence: 'low', mappedLine: 'Support Services', taxonomyCode: '81112300', normalizedQty: '12', normalizedUnit: 'EA', normalizedPrice: '$930', currency: 'USD', conflict: true, overridden: true },
  ];

  const COMPARISON_ROWS: ComparisonMatrixRow[] = [
    { id: 'cmp-1', lineItem: 'CPU Node', values: ['$8,230', '$8,420', '$8,510'], bestVendorIndex: 0 },
    { id: 'cmp-2', lineItem: 'Storage SSD', values: ['$599', '$612', '$640'], bestVendorIndex: 0 },
    { id: 'cmp-3', lineItem: 'Support Services', values: ['$930', '$910', null], bestVendorIndex: 1 },
  ];

  function parseCurrencyValue(value: string): number {
    const normalized = value.replace(/[^0-9.-]/g, '');
    return Number(normalized || 0);
  }

  const filteredRfqRows = React.useMemo(() => {
    return RFQ_ROWS.filter(row => {
      const search = tableSearch.trim().toLowerCase();
      const matchSearch = search.length === 0
        || row.rfqId.toLowerCase().includes(search)
        || row.title.toLowerCase().includes(search)
        || row.owner.toLowerCase().includes(search);
      const matchStatus = statusFilter === 'all' || row.status === statusFilter;
      const matchCategory = categoryFilter === 'all' || row.category === categoryFilter;
      const matchRecentlyHired = !recentlyHiredOnly || row.vendors >= 4;
      const rowValue = parseCurrencyValue(row.estValue);
      const matchMin = minValueFilter === '' || rowValue >= Number(minValueFilter);
      const matchMax = maxValueFilter === '' || rowValue <= Number(maxValueFilter);
      const rowDate = row.deadline;
      const matchFrom = fromDateFilter === '' || rowDate >= fromDateFilter;
      const matchTo = toDateFilter === '' || rowDate <= toDateFilter;
      const matchSavings = !withSavingsOnly || row.savings !== '—';

      return matchSearch && matchStatus && matchCategory && matchRecentlyHired && matchMin && matchMax && matchFrom && matchTo && matchSavings;
    });
  }, [tableSearch, statusFilter, categoryFilter, recentlyHiredOnly, minValueFilter, maxValueFilter, fromDateFilter, toDateFilter, withSavingsOnly]);

  const totalPages = Math.max(1, Math.ceil(filteredRfqRows.length / recordsPerPage));
  const safePage = Math.min(page, totalPages);
  const pagedRows = filteredRfqRows.slice((safePage - 1) * recordsPerPage, safePage * recordsPerPage);

  const configuredColumns = RFQ_COLUMNS.filter(col => showColumns[col.key] !== false);
  const groupedRowsSource = filteredRfqRows.slice(0, 8);

  React.useEffect(() => {
    setPage(1);
  }, [tableSearch, statusFilter, categoryFilter, recentlyHiredOnly, minValueFilter, maxValueFilter, fromDateFilter, toDateFilter, withSavingsOnly, recordsPerPage]);

  function renderSummaryRow(rows: RFQRow[], label = 'Summary'): React.ReactNode {
    const totalValue = rows.reduce((sum, row) => sum + parseCurrencyValue(row.estValue), 0);
    const totalVendors = rows.reduce((sum, row) => sum + row.vendors, 0);
    const maxQuotes = rows.reduce((max, row) => Math.max(max, row.quotes), 0);
    const minQuotes = rows.reduce((min, row) => Math.min(min, row.quotes), Number.POSITIVE_INFINITY);

    return (
      <div className="flex items-center justify-between text-xs text-slate-700">
        <span className="font-semibold">{label}</span>
        <div className="flex items-center gap-4">
          <span>Total Value: <strong>${totalValue.toLocaleString('en-US')}</strong></span>
          <span>Vendors: <strong>{totalVendors}</strong></span>
          <span>Max Quotes: <strong>{maxQuotes}</strong></span>
          <span>Min Quotes: <strong>{minQuotes === Number.POSITIVE_INFINITY ? 0 : minQuotes}</strong></span>
        </div>
      </div>
    );
  }

  const MAIN_NAV_ITEMS = [
    { id: 'dashboard', label: 'Dashboard', icon: <LayoutGrid size={15} /> },
    {
      id: 'rfqs',
      label: 'Requisition',
      icon: <FileText size={15} />,
      badge: 22,
      children: [
        { id: 'rfq-active', label: 'Active', badge: 12 },
        { id: 'rfq-closed', label: 'Closed', badge: 5 },
        { id: 'rfq-awarded', label: 'Awarded', badge: 3 },
        { id: 'rfq-archived', label: 'Archived' },
        { id: 'rfq-draft', label: 'Draft', badge: 2 },
      ],
    },
    { id: 'documents', label: 'Documents', icon: <FolderArchive size={15} /> },
    { id: 'reporting', label: 'Reporting', icon: <BarChart2 size={15} /> },
    {
      id: 'settings',
      label: 'Settings',
      icon: <Settings size={15} />,
      children: [
        { id: 'settings-users', label: 'Users & Roles' },
        { id: 'settings-scoring', label: 'Scoring Policies' },
      ],
    },
  ];

  return (
    <div className="flex h-screen overflow-hidden bg-slate-100" style={{ fontFamily: "'Inter', system-ui, sans-serif" }}>

      {/* ── Showcase Left Nav ── */}
      <aside className="w-56 flex-shrink-0 flex flex-col bg-white border-r border-slate-200 overflow-y-auto">
        {/* Header */}
        <div className="px-4 py-4 border-b border-slate-200">
          <div className="flex items-center gap-2.5">
            <div className="w-7 h-7 bg-indigo-600 rounded-md flex items-center justify-center">
              <span className="text-white text-xs font-bold">AQ</span>
            </div>
            <div>
              <div className="text-sm font-semibold text-slate-900">Atomy-Q</div>
              <div className="text-[10px] text-slate-400 font-medium uppercase tracking-wide">Design System</div>
            </div>
          </div>
        </div>

        {/* Nav */}
        <nav className="flex-1 py-3 px-2">
          {SECTIONS.map(s => (
            <button
              key={s.id}
              onClick={() => scrollToSection(s.id)}
              className={[
                'flex items-center gap-2.5 w-full text-left px-2.5 py-1.5 rounded-md text-sm transition-colors mb-0.5',
                activeSection === s.id
                  ? 'bg-indigo-50 text-indigo-700 font-medium'
                  : 'text-slate-600 hover:bg-slate-100 hover:text-slate-800',
              ].join(' ')}
            >
              <span className={activeSection === s.id ? 'text-indigo-500' : 'text-slate-400'}>{s.icon}</span>
              {s.label}
            </button>
          ))}
        </nav>

        {/* Footer */}
        <div className="border-t border-slate-200 px-4 py-3">
          <p className="text-[10px] text-slate-400">
            Atomy-Q DS <VersionChip version="v1.0.0" className="ml-1" />
          </p>
        </div>
      </aside>

      {/* ── Main Content ── */}
      <main className="flex-1 overflow-y-auto">
        <div className="max-w-4xl mx-auto px-8 py-8">

          {/* ═══════════════════════════════════════════════════════════
              FOUNDATION
          ════════════════════════════════════════════════════════════ */}
          <Section id="foundation" title="Foundation" subtitle="Core design tokens — color, spacing, and shadow system for Atomy-Q.">

            <SubSection title="Color Palette">
              <div className="grid grid-cols-2 gap-6">
                {/* Neutral / Surface */}
                <Card padding="md">
                  <h4 className="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Neutrals & Surface</h4>
                  <div className="flex gap-1.5">
                    {[
                      { cls: 'bg-white border border-slate-200',   label: 'White' },
                      { cls: 'bg-slate-50',  label: '50'  },
                      { cls: 'bg-slate-100', label: '100' },
                      { cls: 'bg-slate-200', label: '200' },
                      { cls: 'bg-slate-300', label: '300' },
                      { cls: 'bg-slate-400', label: '400' },
                      { cls: 'bg-slate-500', label: '500' },
                      { cls: 'bg-slate-600', label: '600' },
                      { cls: 'bg-slate-700', label: '700' },
                      { cls: 'bg-slate-800', label: '800' },
                      { cls: 'bg-slate-900', label: '900' },
                    ].map(s => (
                      <div key={s.label} className="flex flex-col items-center gap-1">
                        <div className={`w-7 h-7 rounded ${s.cls}`} />
                        <span className="text-[9px] text-slate-500">{s.label}</span>
                      </div>
                    ))}
                  </div>
                </Card>

                {/* Accent - Indigo */}
                <Card padding="md">
                  <h4 className="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Accent — Indigo (Interactive)</h4>
                  <div className="flex gap-1.5">
                    {[
                      { cls: 'bg-indigo-50',  label: '50'  },
                      { cls: 'bg-indigo-100', label: '100' },
                      { cls: 'bg-indigo-200', label: '200' },
                      { cls: 'bg-indigo-300', label: '300' },
                      { cls: 'bg-indigo-400', label: '400' },
                      { cls: 'bg-indigo-500', label: '500' },
                      { cls: 'bg-indigo-600', label: '600 ★' },
                      { cls: 'bg-indigo-700', label: '700' },
                      { cls: 'bg-indigo-800', label: '800' },
                    ].map(s => (
                      <div key={s.label} className="flex flex-col items-center gap-1">
                        <div className={`w-7 h-7 rounded ${s.cls}`} />
                        <span className="text-[9px] text-slate-500">{s.label}</span>
                      </div>
                    ))}
                  </div>
                </Card>

                {/* Semantic */}
                <Card padding="md" className="col-span-2">
                  <h4 className="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Semantic Colors</h4>
                  <div className="grid grid-cols-4 gap-3">
                    {[
                      { name: 'Success', shades: ['bg-green-50', 'bg-green-100', 'bg-green-200', 'bg-green-500', 'bg-green-700'] },
                      { name: 'Warning', shades: ['bg-amber-50', 'bg-amber-100', 'bg-amber-200', 'bg-amber-500', 'bg-amber-700'] },
                      { name: 'Danger',  shades: ['bg-red-50',   'bg-red-100',   'bg-red-200',   'bg-red-500',   'bg-red-700']   },
                      { name: 'Info',    shades: ['bg-blue-50',  'bg-blue-100',  'bg-blue-200',  'bg-blue-500',  'bg-blue-700']  },
                    ].map(group => (
                      <div key={group.name}>
                        <p className="text-xs font-medium text-slate-600 mb-2">{group.name}</p>
                        <div className="flex gap-1">
                          {group.shades.map((cls, i) => (
                            <div key={i} className={`w-7 h-7 rounded ${cls}`} />
                          ))}
                        </div>
                      </div>
                    ))}
                  </div>
                </Card>
              </div>
            </SubSection>

            <SubSection title="Spacing Scale">
              <Card padding="md">
                <div className="flex items-end gap-3 flex-wrap">
                  {[1, 2, 3, 4, 5, 6, 8, 10, 12, 16, 20, 24].map(n => (
                    <div key={n} className="flex flex-col items-center gap-1.5">
                      <div className="bg-indigo-200 rounded-sm" style={{ width: n * 4, height: n * 4 }} />
                      <span className="text-[10px] font-mono text-slate-500">{n * 4}px</span>
                    </div>
                  ))}
                </div>
              </Card>
            </SubSection>

            <SubSection title="Shadows & Surfaces">
              <div className="flex gap-4 flex-wrap">
                {[
                  { label: 'shadow-none', cls: '' },
                  { label: 'shadow-sm',   cls: 'shadow-sm' },
                  { label: 'shadow',      cls: 'shadow' },
                  { label: 'shadow-md',   cls: 'shadow-md' },
                  { label: 'shadow-lg',   cls: 'shadow-lg' },
                  { label: 'shadow-xl',   cls: 'shadow-xl' },
                ].map(s => (
                  <div key={s.label} className="flex flex-col items-center gap-2">
                    <div className={`w-20 h-14 bg-white rounded-lg border border-slate-100 ${s.cls}`} />
                    <span className="text-[10px] font-mono text-slate-500">{s.label}</span>
                  </div>
                ))}
              </div>
            </SubSection>

            <SubSection title="Border Radius">
              <div className="flex gap-4 flex-wrap">
                {[
                  { label: 'rounded-sm', cls: 'rounded-sm' },
                  { label: 'rounded',    cls: 'rounded' },
                  { label: 'rounded-md', cls: 'rounded-md' },
                  { label: 'rounded-lg', cls: 'rounded-lg' },
                  { label: 'rounded-xl', cls: 'rounded-xl' },
                  { label: 'rounded-full', cls: 'rounded-full' },
                ].map(s => (
                  <div key={s.label} className="flex flex-col items-center gap-2">
                    <div className={`w-16 h-10 bg-indigo-100 border border-indigo-300 ${s.cls}`} />
                    <span className="text-[10px] font-mono text-slate-500">{s.label}</span>
                  </div>
                ))}
              </div>
            </SubSection>
          </Section>

          {/* ═══════════════════════════════════════════════════════════
              TYPOGRAPHY
          ════════════════════════════════════════════════════════════ */}
          <Section id="typography" title="Typography" subtitle="Inter typeface with JetBrains Mono for IDs and codes.">
            <div className="grid grid-cols-2 gap-6">
              <Card padding="lg">
                <h4 className="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-4">Type Scale</h4>
                <div className="flex flex-col gap-3">
                  {[
                    { tag: 'Heading XL', cls: 'text-2xl font-semibold text-slate-900', ex: 'Heading XL — 24/600' },
                    { tag: 'Heading L',  cls: 'text-xl font-semibold text-slate-900',  ex: 'Heading L — 20/600' },
                    { tag: 'Heading M',  cls: 'text-lg font-semibold text-slate-900',  ex: 'Heading M — 18/600' },
                    { tag: 'Heading S',  cls: 'text-base font-semibold text-slate-900', ex: 'Heading S — 16/600' },
                    { tag: 'Body M',     cls: 'text-sm text-slate-700',                 ex: 'Body M — 14/400 — For general text and descriptions.' },
                    { tag: 'Body S',     cls: 'text-xs text-slate-600',                 ex: 'Body S — 12/400 — Labels, captions, meta.' },
                    { tag: 'Caption',    cls: 'text-[11px] font-medium text-slate-400 uppercase tracking-wider', ex: 'CAPTION — 11/500 UPPERCASE' },
                    { tag: 'Mono',       cls: 'text-xs font-mono text-slate-700 bg-slate-100 px-1.5 py-0.5 rounded', ex: 'RFQ-2401 · v2.1 · UNSPSC:43211503' },
                  ].map(t => (
                    <div key={t.tag} className="flex items-baseline gap-3 border-b border-slate-100 pb-2 last:border-0 last:pb-0">
                      <span className="text-[10px] font-medium text-slate-400 w-20 shrink-0">{t.tag}</span>
                      <span className={t.cls}>{t.ex}</span>
                    </div>
                  ))}
                </div>
              </Card>

              <Card padding="lg">
                <h4 className="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-4">Text Colors</h4>
                <div className="flex flex-col gap-2">
                  {[
                    { cls: 'text-slate-900', label: 'text-slate-900', desc: 'Primary — headings, key data' },
                    { cls: 'text-slate-700', label: 'text-slate-700', desc: 'Secondary — body text' },
                    { cls: 'text-slate-600', label: 'text-slate-600', desc: 'Tertiary — descriptions' },
                    { cls: 'text-slate-500', label: 'text-slate-500', desc: 'Muted — captions, labels' },
                    { cls: 'text-slate-400', label: 'text-slate-400', desc: 'Subtle — placeholders, hints' },
                    { cls: 'text-indigo-600', label: 'text-indigo-600', desc: 'Interactive — links, accent' },
                    { cls: 'text-green-700',  label: 'text-green-700',  desc: 'Success / positive' },
                    { cls: 'text-amber-700',  label: 'text-amber-700',  desc: 'Warning / pending' },
                    { cls: 'text-red-700',    label: 'text-red-700',    desc: 'Danger / error / critical' },
                  ].map(t => (
                    <div key={t.label} className="flex items-center gap-3">
                      <span className={`text-sm font-medium w-40 shrink-0 ${t.cls}`}>Aa {t.desc.split(' — ')[0]}</span>
                      <code className="text-[11px] font-mono text-slate-400">{t.label}</code>
                    </div>
                  ))}
                </div>
              </Card>
            </div>
          </Section>

          {/* ═══════════════════════════════════════════════════════════
              BUTTONS
          ════════════════════════════════════════════════════════════ */}
          <Section id="buttons" title="Buttons" subtitle="All variants, sizes, and states. Indigo is reserved for primary interactive actions.">
            <SubSection title="Variants">
              <Card padding="lg">
                <div className="flex items-center gap-3 flex-wrap">
                  <Button variant="primary">Primary</Button>
                  <Button variant="secondary">Secondary</Button>
                  <Button variant="outline">Outline</Button>
                  <Button variant="ghost">Ghost</Button>
                  <Button variant="destructive">Destructive</Button>
                </div>
              </Card>
            </SubSection>

            <SubSection title="Sizes">
              <Card padding="lg">
                <div className="flex items-center gap-3 flex-wrap">
                  <Button variant="primary" size="xs">Extra Small</Button>
                  <Button variant="primary" size="sm">Small</Button>
                  <Button variant="primary" size="md">Medium</Button>
                  <Button variant="primary" size="lg">Large</Button>
                </div>
              </Card>
            </SubSection>

            <SubSection title="With Icons & States">
              <Card padding="lg">
                <div className="flex items-center gap-3 flex-wrap">
                  <Button variant="primary" icon={<Plus size={13} />}>New RFQ</Button>
                  <Button variant="secondary" icon={<Download size={13} />}>Export</Button>
                  <Button variant="outline" icon={<RefreshCw size={13} />}>Refresh</Button>
                  <Button variant="ghost" icon={<Lock size={13} />}>Lock Run</Button>
                  <Button variant="primary" icon={<Plus size={13} />} iconPosition="right">Icon Right</Button>
                  <Button variant="primary" loading>Processing…</Button>
                  <Button variant="primary" disabled>Disabled</Button>
                  <Button variant="outline" disabled>Disabled</Button>
                </div>
              </Card>
            </SubSection>

            <SubSection title="Icon Buttons">
              <Card padding="lg">
                <div className="flex items-center gap-3">
                  <IconButton variant="primary" size="md" label="Add">
                    <Plus size={14} />
                  </IconButton>
                  <IconButton variant="secondary" size="md" label="Download">
                    <Download size={14} />
                  </IconButton>
                  <IconButton variant="outline" size="md" label="Refresh">
                    <RefreshCw size={14} />
                  </IconButton>
                  <IconButton variant="ghost" size="md" label="Filter">
                    <SlidersHorizontal size={14} />
                  </IconButton>
                  <IconButton variant="ghost" size="sm" label="Small ghost">
                    <Lock size={13} />
                  </IconButton>
                </div>
              </Card>
            </SubSection>
          </Section>

          {/* ═══════════════════════════════════════════════════════════
              BADGES & STATUS
          ════════════════════════════════════════════════════════════ */}
          <Section id="badges" title="Badges & Status" subtitle="Semantic status badges, SLA timers, confidence levels, and version chips.">
            <SubSection title="Status Badges">
              <Card padding="lg">
                <div className="flex items-center gap-2 flex-wrap">
                  {(['active', 'approved', 'paid', 'final', 'generated', 'awarded', 'pending', 'processing', 'preview', 'stale', 'due', 'rejected', 'error', 'overdue', 'closed', 'locked', 'unpaid', 'draft', 'new'] as StatusVariant[]).map(s => (
                    <StatusBadge key={s} status={s} />
                  ))}
                </div>
              </Card>
            </SubSection>

            <SubSection title="SLA Timer Badges">
              <Card padding="lg">
                <div className="flex items-center gap-3">
                  <SLATimerBadge variant="safe" value="2d 4h" />
                  <SLATimerBadge variant="warning" value="6h" />
                  <SLATimerBadge variant="overdue" value="OVERDUE" />
                </div>
              </Card>
            </SubSection>

            <SubSection title="Confidence Badges">
              <Card padding="lg">
                <div className="flex flex-col gap-3 max-w-xs">
                  <div className="flex items-center gap-3">
                    <ConfidenceBadge variant="high" />
                    <ConfidenceBadge variant="medium" />
                    <ConfidenceBadge variant="low" />
                  </div>
                  <div className="border-t border-slate-100 pt-3">
                    <p className="text-xs text-slate-400 mb-2">With progress bar:</p>
                    <ConfidenceBadge variant="high" showBar percentage={92} />
                    <div className="mt-2">
                      <ConfidenceBadge variant="medium" showBar percentage={58} />
                    </div>
                    <div className="mt-2">
                      <ConfidenceBadge variant="low" showBar percentage={22} />
                    </div>
                  </div>
                </div>
              </Card>
            </SubSection>

            <SubSection title="Utility Badges">
              <Card padding="lg">
                <div className="flex items-center gap-3 flex-wrap">
                  <VersionChip version="v2.1" />
                  <VersionChip version="v1.8" />
                  <CountBadge count={12} />
                  <CountBadge count={3} variant="indigo" />
                  <CountBadge count={2} variant="red" />
                  <StatusDot color="green" />
                  <StatusDot color="amber" />
                  <StatusDot color="red" />
                  <StatusDot color="slate" />
                </div>
              </Card>
            </SubSection>
          </Section>

          {/* ═══════════════════════════════════════════════════════════
              AVATARS
          ════════════════════════════════════════════════════════════ */}
          <Section id="avatar" title="Avatars" subtitle="User avatars with deterministic color fallback based on name initials.">
            <div className="grid grid-cols-2 gap-4">
              <Card padding="lg">
                <h4 className="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Sizes</h4>
                <div className="flex items-end gap-3">
                  <Avatar name="Alex Kumar" size="xs" />
                  <Avatar name="Sarah Chen" size="sm" />
                  <Avatar name="Marcus Webb" size="md" />
                  <Avatar name="Priya Nair" size="lg" />
                  <Avatar name="James Okonkwo" size="xl" />
                </div>
              </Card>
              <Card padding="lg">
                <h4 className="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">With Label</h4>
                <div className="flex flex-col gap-3">
                  <AvatarLabel name="Alex Kumar" subtitle="Procurement Manager" size="md" />
                  <AvatarLabel name="Sarah Chen" subtitle="sarah.chen@atomy.co" size="sm" />
                </div>
              </Card>
              <Card padding="lg">
                <h4 className="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Color Variants (auto)</h4>
                <div className="flex gap-2 flex-wrap">
                  {['Alex Kumar', 'Sarah Chen', 'Marcus Webb', 'Priya Nair', 'James Okonkwo', 'Liu Wei', 'Ayasha Morningstar'].map(n => (
                    <Avatar key={n} name={n} size="md" />
                  ))}
                </div>
              </Card>
              <Card padding="lg">
                <h4 className="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Avatar Stack</h4>
                <div className="flex flex-col gap-3">
                  <AvatarStack names={['Alex Kumar', 'Sarah Chen', 'Marcus Webb', 'Priya Nair', 'James Okonkwo']} max={4} />
                  <AvatarStack names={['Alex Kumar', 'Sarah Chen', 'Marcus Webb']} size="md" />
                </div>
              </Card>
            </div>
          </Section>

          {/* ═══════════════════════════════════════════════════════════
              FORM CONTROLS
          ════════════════════════════════════════════════════════════ */}
          <Section id="inputs" title="Form Controls" subtitle="Text inputs, selects, toggles, checkboxes, and filter chips.">
            <div className="grid grid-cols-2 gap-4">
              <Card padding="lg">
                <h4 className="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Text Inputs</h4>
                <div className="flex flex-col gap-3">
                  <TextInput label="RFQ Title" placeholder="e.g. Server Infrastructure Refresh" required />
                  <TextInput label="With error" placeholder="Vendor name" error="This field is required." />
                  <TextInput label="With hint" placeholder="Optional note" hint="Max 500 characters." />
                </div>
              </Card>
              <Card padding="lg">
                <h4 className="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Search & Select</h4>
                <div className="flex flex-col gap-3">
                  <SearchInput placeholder="Search vendors…" shortcut="/" containerClassName="w-full" />
                  <SelectInput
                    label="Category"
                    placeholder="All categories"
                    options={[
                      { value: 'it', label: 'IT Hardware' },
                      { value: 'sw', label: 'Software' },
                      { value: 'fac', label: 'Facilities' },
                    ]}
                  />
                  <SelectInput
                    label="Compact Select"
                    compact
                    placeholder="Status"
                    options={[{ value: 'a', label: 'Active' }, { value: 'c', label: 'Closed' }]}
                  />
                </div>
              </Card>
              <Card padding="lg">
                <h4 className="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Textarea</h4>
                <Textarea label="Decision Rationale" placeholder="Provide your decision rationale…" required />
              </Card>
              <Card padding="lg">
                <h4 className="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Toggles & Checkboxes</h4>
                <div className="flex flex-col gap-3">
                  <ToggleSwitch checked={toggleA} onChange={setToggleA} label="Split Award" />
                  <ToggleSwitch checked={toggleB} onChange={setToggleB} label="View Original Values" />
                  <ToggleSwitch checked={false} onChange={() => {}} label="Disabled toggle" disabled />
                  <div className="flex flex-col gap-2 pt-1">
                    <Checkbox label="Financial review complete" defaultChecked />
                    <Checkbox label="Legal review complete" />
                    <Checkbox label="Procurement lead sign-off" />
                    <Checkbox label="Indeterminate" indeterminate />
                  </div>
                </div>
              </Card>
              <Card padding="lg" className="col-span-2">
                <h4 className="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Filter Bar</h4>
                <FilterBar
                  searchValue={searchVal}
                  onSearchChange={setSearchVal}
                  searchPlaceholder="Search RFQs…"
                  filters={[
                    { key: 'status', label: 'Status', options: [{ value: 'active', label: 'Active' }, { value: 'closed', label: 'Closed' }] },
                    { key: 'category', label: 'Category', options: [{ value: 'it', label: 'IT' }, { value: 'fac', label: 'Facilities' }] },
                  ]}
                  activeFilters={[
                    { key: 'status', label: 'Status', value: 'Active' },
                    { key: 'owner', label: 'Owner', value: 'Alex Kumar' },
                  ]}
                  onRemoveFilter={() => {}}
                  onClearAll={() => {}}
                  extraActions={<Button variant="outline" size="sm" icon={<Download size={13} />}>Export</Button>}
                />
              </Card>
            </div>
          </Section>

          {/* ═══════════════════════════════════════════════════════════
              CARDS & SURFACES
          ════════════════════════════════════════════════════════════ */}
          <Section id="cards" title="Cards & Surfaces" subtitle="Card containers, info grids, empty states, upload zones, and document previews.">
            <div className="grid grid-cols-3 gap-4">
              <Card padding="md" hover>
                <p className="text-sm font-medium text-slate-800">Hover Card</p>
                <p className="text-xs text-slate-500 mt-1">Click to navigate</p>
              </Card>
              <Card padding="md" bordered={false} className="shadow-md">
                <p className="text-sm font-medium text-slate-800">Elevated (no border)</p>
                <p className="text-xs text-slate-500 mt-1">shadow-md applied</p>
              </Card>
              <Card padding="md" className="border-indigo-200 bg-indigo-50/30">
                <p className="text-sm font-medium text-indigo-800">Accent Tinted</p>
                <p className="text-xs text-indigo-600 mt-1">Active / selected card</p>
              </Card>
            </div>

            <div className="mt-4 grid grid-cols-2 gap-4">
              <SectionCard
                title="Customer Information"
                subtitle="Primary record card"
                actions={<Button variant="ghost" size="xs">Actions</Button>}
              >
                <InfoGrid
                  cols={2}
                  items={[
                    { label: 'Company',   value: 'Dell Technologies' },
                    { label: 'Category',  value: 'IT Hardware' },
                    { label: 'Est. Value',value: '$1,200,000' },
                    { label: 'Deadline',  value: '2026-04-15' },
                    { label: 'Vendors',   value: '5 invited' },
                    { label: 'Savings %', value: '12%' },
                  ]}
                />
              </SectionCard>

              <div className="flex flex-col gap-4">
                <EmptyState
                  icon={<FileText size={28} />}
                  title="No documents yet"
                  description="Upload vendor quotes and supporting documents to get started."
                  action={<Button variant="primary" size="sm" icon={<Plus size={12} />}>Upload Quote</Button>}
                  className="border border-dashed border-slate-200 rounded-lg"
                />
                <UploadZone compact />
                <DocPreview fileName="Dell_Quote_RFQ2401.pdf" pageInfo="Page 1 of 4" className="h-24" />
              </div>
            </div>
          </Section>

          {/* ═══════════════════════════════════════════════════════════
              TABS
          ════════════════════════════════════════════════════════════ */}
          <Section id="tabs" title="Tabs" subtitle="Primary (underline), Secondary (pill), and Vertical tabs for different hierarchical levels.">
            <SubSection title="Primary Tabs (Section-level navigation)">
              <Card padding="none">
                <PrimaryTabs
                  tabs={[
                    { id: 'tab1', label: 'Project Details', icon: <FileText size={13} /> },
                    { id: 'tab2', label: 'Position Details' },
                    { id: 'tab3', label: 'Preference', count: 4 },
                    { id: 'tab4', label: 'Disabled', disabled: true },
                  ]}
                  activeTab={activeTab}
                  onChange={setActiveTab}
                  className="px-4"
                />
                <div className="p-4">
                  <TabPanel id="tab1" activeTab={activeTab}>
                    <p className="text-sm text-slate-600">Project Details content area.</p>
                  </TabPanel>
                  <TabPanel id="tab2" activeTab={activeTab}>
                    <p className="text-sm text-slate-600">Position Details content area.</p>
                  </TabPanel>
                  <TabPanel id="tab3" activeTab={activeTab}>
                    <p className="text-sm text-slate-600">Preference content area — 4 items.</p>
                  </TabPanel>
                </div>
              </Card>
            </SubSection>

            <SubSection title="Secondary Tabs (Record-level sub-navigation)">
              <Card padding="md">
                <SecondaryTabs
                  tabs={[
                    { id: 'freelancer', label: 'Freelancer', count: 12 },
                    { id: 'questions',  label: 'Questions' },
                    { id: 'offers',     label: 'Offers' },
                    { id: 'interview',  label: 'Interview', count: 2 },
                    { id: 'contracts',  label: 'Contracts', count: 3 },
                    { id: 'payment',    label: 'Payment' },
                    { id: 'compliance', label: 'Compliance' },
                  ]}
                  activeTab={activeSecondaryTab}
                  onChange={setActiveSecondaryTab}
                />
              </Card>
            </SubSection>

            <SubSection title="Vertical Tabs">
              <div className="flex gap-4">
                <Card padding="md" className="w-48">
                  <VerticalTabs
                    tabs={[
                      { id: 'overview',   label: 'Overview',   icon: <Eye size={13} /> },
                      { id: 'details',    label: 'Details',    icon: <FileText size={13} /> },
                      { id: 'lineitems',  label: 'Line Items', icon: <List size={13} />, count: 24 },
                      { id: 'vendors',    label: 'Vendors',    icon: <Users size={13} />, count: 5 },
                      { id: 'award',      label: 'Award',      icon: <Award size={13} /> },
                    ]}
                    activeTab="overview"
                    onChange={() => {}}
                  />
                </Card>
                <Card padding="md" className="flex-1">
                  <p className="text-sm text-slate-600">Selected tab content renders here.</p>
                </Card>
              </div>
            </SubSection>
          </Section>

          {/* ═══════════════════════════════════════════════════════════
              NAVIGATION
          ════════════════════════════════════════════════════════════ */}
          <Section id="navigation" title="Navigation" subtitle="Breadcrumb bar, sidebar nav items, and Active Record Menu navigation links.">
            <SubSection title="Breadcrumb Bar">
              <Card padding="md">
                <div className="flex flex-col gap-3">
                  <BreadcrumbBar
                    items={[
                      { label: 'RFQs' },
                      { label: 'Server Infrastructure Refresh' },
                      { label: 'Quote Intake' },
                    ]}
                    showHome
                  />
                  <BreadcrumbBar
                    items={[
                      { label: 'RFQs' },
                      { label: 'Server Infrastructure Refresh' },
                      { label: 'Quote Intake' },
                      { label: 'Dell Technologies Quote' },
                    ]}
                    showHome
                  />
                </div>
              </Card>
            </SubSection>

            <SubSection title="Sidebar Navigation">
              <div className="grid grid-cols-2 gap-4">
                <Card padding="sm">
                  <div className="flex flex-col gap-0.5">
                    <NavLabel label="Main" />
                    <NavItem label="Dashboard" icon={<LayoutGrid size={15} />} />
                    <NavGroup label="Requisition" icon={<FileText size={15} />} active defaultOpen badge={22}>
                      <SubNavItem label="Active" active badge={12} />
                      <SubNavItem label="Closed" badge={5} />
                      <SubNavItem label="Awarded" badge={3} />
                      <SubNavItem label="Archived" />
                      <SubNavItem label="Draft" badge={2} />
                    </NavGroup>
                    <NavItem label="Documents" icon={<FolderArchive size={15} />} />
                    <NavItem label="Reporting" icon={<BarChart2 size={15} />} />
                    <NavLabel label="System" />
                    <NavGroup label="Settings" icon={<Settings size={15} />}>
                      <SubNavItem label="Users & Roles" />
                      <SubNavItem label="Scoring Policies" />
                    </NavGroup>
                  </div>
                </Card>

                <Card padding="sm">
                  <div className="flex flex-col gap-0.5">
                    <span className="block px-3 mb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400">RFQ</span>
                    <NavigationLink label="Overview" icon={<Eye size={13} />} active />
                    <NavigationLink label="Details" icon={<FileText size={13} />} />
                    <NavigationLink label="Line Items" icon={<List size={13} />} />
                    <NavigationLink label="Vendors" icon={<Users size={13} />} />
                    <NavigationLink label="Award" icon={<Award size={13} />} />
                    <div className="border-t border-slate-200 my-2" />
                    <span className="block px-3 mb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400">Child Records</span>
                    <NavigationLink label="Quote Intake" icon={<FileCheck size={13} />} badge={8} />
                    <NavigationLink label="Comparison Runs" icon={<BarChart2 size={13} />} badge={3} />
                    <NavigationLink label="Approvals" icon={<ShieldCheck size={13} />} badge={2} />
                    <NavigationLink label="Risk & Compliance" icon={<AlertTriangle size={13} />} statusDot="amber" />
                  </div>
                </Card>
              </div>
            </SubSection>

            <SubSection title="P0 Contract Components">
              <div className="grid grid-cols-2 gap-4">
                <Card padding="sm">
                  <p className="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">MainNav (single-group accordion)</p>
                  <MainNav
                    items={MAIN_NAV_ITEMS}
                    activeItem="rfq-active"
                    onNavigate={() => {}}
                  />
                </Card>

                <div className="flex flex-col gap-3">
                  <Card padding="md">
                    <p className="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">WorkspaceBreadcrumbs</p>
                    <WorkspaceBreadcrumbs
                      segments={[
                        { label: 'RFQs', path: '/rfqs' },
                        { label: 'Server Infrastructure Refresh', path: '/rfqs/RFQ-2401' },
                        { label: 'Approvals', path: '/rfqs/RFQ-2401/approvals' },
                        { label: 'APR-00412', path: '/rfqs/RFQ-2401/approvals/APR-00412' },
                      ]}
                    />
                  </Card>
                  <RecordHeader
                    title="APR-00412"
                    status="pending"
                    leading={<span className="text-xs text-slate-500">Comparison Approval</span>}
                    metadata={[
                      { label: 'SLA', value: <SLATimerBadge variant="safe" value="1d 18h" /> },
                      { label: 'Assignee', value: 'Marcus Webb' },
                      { label: 'RFQ', value: 'RFQ-2401' },
                      { label: 'Run', value: 'RUN-005' },
                    ]}
                    actions={<Button size="sm" variant="primary">Open Detail</Button>}
                  />
                </div>
              </div>
            </SubSection>
          </Section>

          {/* ═══════════════════════════════════════════════════════════
              DATA DISPLAY
          ════════════════════════════════════════════════════════════ */}
          <Section id="data-display" title="Data Display" subtitle="Data table with sorting, row selection, bulk actions, inline expansion, and pagination.">
            <SubSection title="KPI Scorecards">
              <div className="grid grid-cols-4 gap-3">
                <KPIScorecard
                  title="Quotes Received"
                  value="8"
                  subtitle="of 10 expected"
                  progress={{ value: 80, type: 'bar', variant: 'indigo' }}
                />
                <KPIScorecard
                  title="Normalization"
                  value="75%"
                  subtitle="18 of 24 lines"
                  progress={{ value: 75, type: 'circular', variant: 'indigo' }}
                />
                <KPIScorecard
                  title="Comparison Status"
                  value="Preview"
                  badge={<StatusBadge status="preview" />}
                  trend={{ direction: 'neutral', label: 'Preview mode' }}
                />
                <KPIScorecard
                  title="Approval Status"
                  value="Pending"
                  badge={<StatusBadge status="pending" />}
                />
              </div>
            </SubSection>

            <SubSection title="Metric Row">
              <Card padding="none">
                <MetricRow
                  metrics={[
                    { label: 'Contracted', value: '2/3' },
                    { label: 'Avg. Offer', value: '$165' },
                    { label: 'Unpaid Contracts', value: '0' },
                    { label: 'Missing Itineraries', value: '4' },
                    { label: 'Unread Messages', value: '0' },
                  ]}
                />
              </Card>
            </SubSection>

            <SubSection title="Data Table">
              <div className="space-y-4">
                <Card padding="md" className="relative">
                  <div className="flex items-center justify-between gap-3 mb-3">
                    <div>
                      <h4 className="text-sm font-semibold text-slate-800">Requisitions</h4>
                      <p className="text-xs text-slate-500">{filteredRfqRows.length} records</p>
                    </div>
                    <div className="flex items-center gap-2 flex-wrap justify-end">
                      <SearchInput
                        value={tableSearch}
                        onChange={e => setTableSearch(e.target.value)}
                        placeholder="Search RFQ, title, owner..."
                        containerClassName="w-56"
                        compact
                      />
                      <SelectInput
                        compact
                        value={statusFilter}
                        onChange={e => setStatusFilter(e.target.value)}
                        options={[
                          { value: 'all', label: 'All Status' },
                          { value: 'active', label: 'Active' },
                          { value: 'pending', label: 'Pending' },
                          { value: 'awarded', label: 'Awarded' },
                          { value: 'draft', label: 'Draft' },
                          { value: 'closed', label: 'Closed' },
                        ]}
                      />
                      <SelectInput
                        compact
                        value={categoryFilter}
                        onChange={e => setCategoryFilter(e.target.value)}
                        options={[
                          { value: 'all', label: 'All Categories' },
                          { value: 'IT Hardware', label: 'IT Hardware' },
                          { value: 'Facilities', label: 'Facilities' },
                          { value: 'Software', label: 'Software' },
                          { value: 'Security', label: 'Security' },
                          { value: 'Marketing', label: 'Marketing' },
                          { value: 'Transport', label: 'Transport' },
                          { value: 'IT Services', label: 'IT Services' },
                        ]}
                      />
                      <ToggleSwitch checked={recentlyHiredOnly} onChange={setRecentlyHiredOnly} label="Recently Hired" size="sm" />

                      <button
                        className="inline-flex items-center justify-center w-7 h-7 rounded border border-slate-200 text-slate-500 hover:bg-slate-50"
                        onClick={() => setAdvancedFiltersOpen(v => !v)}
                        title="Filters"
                      >
                        <SlidersHorizontal size={13} />
                      </button>
                      <button
                        className="inline-flex items-center justify-center w-7 h-7 rounded border border-slate-200 text-slate-500 hover:bg-slate-50"
                        onClick={() => setConfigOpen(v => !v)}
                        title="Configure"
                      >
                        <Settings size={13} />
                      </button>
                      <div className="inline-flex rounded border border-slate-200 overflow-hidden">
                        <button
                          className={['px-2 h-7 text-xs', viewMode === 'table' ? 'bg-indigo-50 text-indigo-700' : 'bg-white text-slate-600'].join(' ')}
                          onClick={() => setViewMode('table')}
                        >
                          Table
                        </button>
                        <button
                          className={['px-2 h-7 text-xs border-l border-slate-200', viewMode === 'grid' ? 'bg-indigo-50 text-indigo-700' : 'bg-white text-slate-600'].join(' ')}
                          onClick={() => setViewMode('grid')}
                        >
                          Grid
                        </button>
                      </div>
                    </div>
                  </div>

                  {advancedFiltersOpen && (
                    <div className="mb-3 grid grid-cols-5 gap-2 items-center">
                      <TextInput type="date" value={fromDateFilter} onChange={e => setFromDateFilter(e.target.value)} placeholder="From date" />
                      <TextInput type="date" value={toDateFilter} onChange={e => setToDateFilter(e.target.value)} placeholder="To date" />
                      <TextInput type="number" value={minValueFilter} onChange={e => setMinValueFilter(e.target.value)} placeholder="Min value" />
                      <TextInput type="number" value={maxValueFilter} onChange={e => setMaxValueFilter(e.target.value)} placeholder="Max value" />
                      <Checkbox
                        label="Only with savings"
                        checked={withSavingsOnly}
                        onChange={e => setWithSavingsOnly(e.currentTarget.checked)}
                      />
                    </div>
                  )}

                  {configOpen && (
                    <div className="absolute right-4 top-20 z-20 w-72 bg-white border border-slate-200 rounded-md shadow-lg p-3">
                      <p className="text-xs font-semibold text-slate-700 uppercase tracking-wide mb-2">View Configuration</p>
                      <div className="space-y-2">
                        <p className="text-[11px] text-slate-500 font-medium">Show / Hide Columns</p>
                        {Object.keys(showColumns).map(key => (
                          <Checkbox
                            key={key}
                            label={key}
                            checked={showColumns[key]}
                            onChange={e => setShowColumns(prev => ({ ...prev, [key]: e.currentTarget.checked }))}
                          />
                        ))}
                        <div className="pt-2">
                          <SelectInput
                            label="Records per page"
                            compact
                            value={String(recordsPerPage)}
                            onChange={e => setRecordsPerPage(Number(e.target.value))}
                            options={[
                              { value: '4', label: '4' },
                              { value: '8', label: '8' },
                              { value: '12', label: '12' },
                            ]}
                          />
                        </div>
                        {viewMode === 'table' && (
                          <div className="pt-2 space-y-2">
                            <ToggleSwitch checked={showGroupSummaryRow} onChange={setShowGroupSummaryRow} label="Show group summary" size="sm" />
                            <ToggleSwitch checked={showTableSummaryRow} onChange={setShowTableSummaryRow} label="Show table summary row" size="sm" />
                          </div>
                        )}
                      </div>
                    </div>
                  )}

                  {viewMode === 'table' ? (
                    <>
                      <DataTable
                        columns={configuredColumns}
                        rows={pagedRows}
                        selectable
                        selectedIds={selectedIds}
                        onSelectChange={setSelectedIds}
                        expandable
                        expandedId={expandedId}
                        onExpandChange={setExpandedId}
                        expandedIndentColumns={1}
                        renderExpanded={row => (
                          <div className="grid grid-cols-6 gap-4 px-4 py-3 bg-slate-50 border-b border-slate-200">
                            {[
                              { label: 'Category', value: row.category },
                              { label: 'Deadline', value: row.deadline },
                              { label: 'Vendors', value: row.vendors },
                              { label: 'Quotes', value: row.quotes },
                              { label: 'Est. Value', value: row.estValue },
                              { label: 'Savings %', value: row.savings },
                            ].map(item => (
                              <div key={item.label}>
                                <span className="block text-[10px] font-medium text-slate-400 uppercase tracking-wider mb-0.5">{item.label}</span>
                                <span className="block text-xs font-medium text-slate-700">{item.value}</span>
                              </div>
                            ))}
                          </div>
                        )}
                        bulkActions={[
                          { label: 'Close Selected', onClick: () => {} },
                          { label: 'Archive Selected', onClick: () => {} },
                          { label: 'Assign Owner', onClick: () => {} },
                          { label: 'Export Selected', onClick: () => {} },
                        ]}
                        showActions
                        showTableSummary={showTableSummaryRow}
                        renderTableSummary={rows => renderSummaryRow(rows, 'Current page summary')}
                      />
                      <TableFooter
                        page={safePage}
                        totalPages={totalPages}
                        onPageChange={setPage}
                        totalItems={filteredRfqRows.length}
                        pageSize={recordsPerPage}
                      />
                    </>
                  ) : (
                    <div className="grid grid-cols-3 gap-3">
                      {pagedRows.map(row => (
                        <Card key={row.id} padding="md" className="border border-slate-200">
                          <div className="flex items-start justify-between gap-2">
                            <div>
                              <p className="text-sm font-semibold text-slate-800 leading-tight">{row.title}</p>
                              <p className="text-xs text-slate-500 mt-0.5">{row.rfqId} · {row.owner}</p>
                            </div>
                            <StatusBadge status={row.status} size="xs" />
                          </div>
                          <div className="grid grid-cols-2 gap-2 mt-3">
                            <div className="rounded border border-slate-200 px-2 py-1">
                              <p className="text-[10px] uppercase text-slate-400">Category</p>
                              <p className="text-xs text-slate-700">{row.category}</p>
                            </div>
                            <div className="rounded border border-slate-200 px-2 py-1">
                              <p className="text-[10px] uppercase text-slate-400">Deadline</p>
                              <p className="text-xs text-slate-700">{row.deadline}</p>
                            </div>
                            <div className="rounded border border-slate-200 px-2 py-1">
                              <p className="text-[10px] uppercase text-slate-400">Vendors</p>
                              <p className="text-xs text-slate-700">{row.vendors}</p>
                            </div>
                            <div className="rounded border border-slate-200 px-2 py-1">
                              <p className="text-[10px] uppercase text-slate-400">Est. Value</p>
                              <p className="text-xs text-slate-700">{row.estValue}</p>
                            </div>
                          </div>
                        </Card>
                      ))}
                    </div>
                  )}
                </Card>

                <Card padding="md">
                  <p className="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Variant: Grouped Rows</p>
                  <DataTable
                    columns={RFQ_COLUMNS}
                    rows={groupedRowsSource}
                    groupBy={row => row.category}
                    renderGroupHeader={(group, rows) => (
                      <div className="flex items-center justify-between text-xs">
                        <span className="font-semibold text-slate-700">{group}</span>
                        <span className="text-slate-500">{rows.length} records</span>
                      </div>
                    )}
                    showActions
                  />
                </Card>

                <Card padding="md">
                  <p className="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Variant: Footer Summaries</p>
                  <DataTable
                    columns={RFQ_COLUMNS}
                    rows={groupedRowsSource}
                    showTableSummary
                    renderTableSummary={rows => renderSummaryRow(rows, 'Table totals')}
                    showActions
                  />
                </Card>

                <Card padding="md">
                  <p className="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Variant: Grouping + Group Summaries + Table Summary</p>
                  <DataTable
                    columns={RFQ_COLUMNS}
                    rows={groupedRowsSource}
                    groupBy={row => row.category}
                    showGroupSummary={showGroupSummaryRow}
                    showTableSummary={showTableSummaryRow}
                    renderGroupSummary={(group, rows) => renderSummaryRow(rows, `${group} subtotal`)}
                    renderTableSummary={rows => renderSummaryRow(rows, 'Grand total')}
                    showActions
                  />
                </Card>
              </div>
            </SubSection>
          </Section>

          {/* ═══════════════════════════════════════════════════════════
              PROGRESS
          ════════════════════════════════════════════════════════════ */}
          <Section id="progress" title="Progress Indicators" subtitle="Linear bars, circular indicators, and mini in-cell progress.">
            <div className="grid grid-cols-2 gap-4">
              <Card padding="lg">
                <h4 className="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-4">Linear Progress</h4>
                <div className="flex flex-col gap-4">
                  <ProgressBar value={80} label="Quote Intake" showValue variant="indigo" size="sm" />
                  <ProgressBar value={60} label="Normalization" showValue variant="green" size="sm" />
                  <ProgressBar value={30} label="Approvals" showValue variant="amber" size="sm" />
                  <ProgressBar value={15} label="SLA remaining" showValue variant="red" size="sm" />
                  <ProgressBar
                    value={100}
                    label="Paid / Due / Overdue"
                    size="md"
                    segments={[
                      { value: 543, color: 'bg-green-500' },
                      { value: 22, color: 'bg-amber-400' },
                      { value: 76, color: 'bg-red-500' },
                    ]}
                    max={641}
                  />
                </div>
              </Card>

              <Card padding="lg">
                <h4 className="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-4">Circular Progress</h4>
                <div className="flex items-center gap-5">
                  <div className="flex flex-col items-center gap-1">
                    <CircularProgress value={82} size={56} variant="indigo" />
                    <span className="text-xs text-slate-500">Indigo</span>
                  </div>
                  <div className="flex flex-col items-center gap-1">
                    <CircularProgress value={65} size={56} variant="green" />
                    <span className="text-xs text-slate-500">Green</span>
                  </div>
                  <div className="flex flex-col items-center gap-1">
                    <CircularProgress value={40} size={56} variant="amber" />
                    <span className="text-xs text-slate-500">Amber</span>
                  </div>
                  <div className="flex flex-col items-center gap-1">
                    <CircularProgress value={18} size={56} variant="red" />
                    <span className="text-xs text-slate-500">Red</span>
                  </div>
                  <div className="flex flex-col items-center gap-1">
                    <CircularProgress value={75} size={72} strokeWidth={6} label="75%" />
                    <span className="text-xs text-slate-500">Large</span>
                  </div>
                </div>

                <div className="mt-5 pt-4 border-t border-slate-100">
                  <h5 className="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Mini Progress (table cell)</h5>
                  <div className="flex flex-col gap-2">
                    <MiniProgress value={92} variant="green" />
                    <MiniProgress value={55} variant="amber" />
                    <MiniProgress value={18} variant="red" />
                  </div>
                </div>
              </Card>
            </div>
          </Section>

          {/* ═══════════════════════════════════════════════════════════
              TIMELINE
          ════════════════════════════════════════════════════════════ */}
          <Section id="timeline" title="Timeline & Activity Feed" subtitle="Activity timelines for audit trails and event logs.">
            <div className="grid grid-cols-2 gap-4">
              <Card padding="lg">
                <h4 className="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-4">Compact Timeline</h4>
                <Timeline events={ACTIVITY_EVENTS} compact />
              </Card>
              <SectionCard title="Activity Feed" subtitle="Actor + action + timestamp">
                <ActivityFeed events={ACTIVITY_EVENTS} />
              </SectionCard>
            </div>
          </Section>

          {/* ═══════════════════════════════════════════════════════════
              ALERTS & BANNERS
          ════════════════════════════════════════════════════════════ */}
          <Section id="alerts" title="Alerts & Banners" subtitle="Inline alerts, dismissible alerts, banners, and validation checklists.">
            <div className="flex flex-col gap-3 mb-4">
              <Alert variant="success" title="Auto-approved" onClose={() => {}}>
                Comparison Run #005 has been automatically approved. Proceed to award.
              </Alert>
              <Alert variant="warning" title="Approval Gate Triggered" list={[
                'High risk detected for vendor Cisco Systems.',
                'Top vendor score 68.5 is below auto-approval threshold 70.0.',
              ]} />
              <Alert variant="error" title="Parse error" onClose={() => {}}>
                Document could not be parsed. Detected format: image-only PDF (no OCR layer).
              </Alert>
              <Alert variant="info" compact>
                SLA timer has been reset. Decision required within <strong>2d 4h</strong>.
              </Alert>
              <Alert variant="neutral">
                Comparison Run #004 is marked <strong>Stale</strong> — scoring model has been updated since it was generated.
              </Alert>
            </div>

            <SubSection title="Banners">
              <div className="flex flex-col gap-2">
                <Banner variant="success">
                  Auto-approved. Proceed to Award or Negotiation.
                  <a href="#" className="ml-2 underline font-medium">Go to Award →</a>
                </Banner>
                <Banner variant="warning">
                  Standstill period active — 10 days remaining before award can be finalised.
                </Banner>
                <Banner variant="info" action={<button className="text-xs underline text-white/80">Dismiss</button>}>
                  Atomy-Q v2.0 is available. Release notes include new AI Insights features.
                </Banner>
              </div>
            </SubSection>

            <SubSection title="Validation Checklist">
              <Card padding="md" className="max-w-sm">
                <Checklist items={[
                  { label: 'Vendor details verified', status: 'pass' },
                  { label: 'All line items matched', status: 'pass' },
                  { label: 'Currency conversion applied', status: 'pass' },
                  { label: 'Lead time detected', status: 'warning', detail: 'Warning: lead time not detected in document.' },
                  { label: 'Digital signature present', status: 'fail', detail: 'Document is not digitally signed.' },
                ]} />
              </Card>
            </SubSection>
          </Section>

          {/* ═══════════════════════════════════════════════════════════
              SLIDE-OVER
          ════════════════════════════════════════════════════════════ */}
          <Section id="slideover" title="Slide-Over" subtitle="Right-edge modals in 4 widths: Small (30%), Medium (40%), Large (50%), Extra-Large (60%).">
            <Card padding="lg">
              <p className="text-sm text-slate-600 mb-4">
                Slide-overs animate in from the right edge, overlaying the current view. They have a sticky header, scrollable body, and optional sticky footer.
              </p>
              <div className="flex gap-3 flex-wrap">
                {(['sm', 'md', 'lg', 'xl'] as const).map(w => (
                  <Button
                    key={w}
                    variant="outline"
                    size="sm"
                    onClick={() => { setSlideOverWidth(w); setSlideOverOpen(true); }}
                  >
                    Open {w.toUpperCase()} ({w === 'sm' ? '30%' : w === 'md' ? '40%' : w === 'lg' ? '50%' : '60%'})
                  </Button>
                ))}
              </div>

              <div className="mt-4 bg-slate-50 border border-dashed border-slate-300 rounded-lg p-4">
                <p className="text-xs text-slate-500 font-medium mb-2">Width variants:</p>
                <div className="flex items-end gap-2">
                  {[
                    { w: 'sm', label: '30% — Form / Quick edit' },
                    { w: 'md', label: '40% — Detail / Detail form' },
                    { w: 'lg', label: '50% — Complex form / Preview' },
                    { w: 'xl', label: '60% — Document / Comparison' },
                  ].map(item => (
                    <div key={item.w} className="flex flex-col items-center">
                      <div
                        className="bg-indigo-200 border border-indigo-300 rounded"
                        style={{
                          width: item.w === 'sm' ? 60 : item.w === 'md' ? 80 : item.w === 'lg' ? 100 : 120,
                          height: 48,
                        }}
                      />
                      <span className="text-[10px] text-slate-500 mt-1 text-center w-28">{item.label}</span>
                    </div>
                  ))}
                </div>
              </div>
            </Card>

            <Card padding="lg" className="mt-4">
              <p className="text-sm text-slate-600 mb-3">
                Stack manager supports nested slide-overs up to depth 2 (parent + child).
              </p>
              <div className="flex items-center gap-2">
                <Button
                  size="sm"
                  variant="outline"
                  onClick={() => {
                    setStackPanels([
                      {
                        id: 'stack-1',
                        title: 'Upload & Parse Quote',
                        subtitle: 'Primary panel (depth 1)',
                        width: 'md',
                        content: (
                          <div className="p-5 space-y-3">
                            <p className="text-sm text-slate-600">
                              Select files and vendor, then confirm parse settings.
                            </p>
                            <Button
                              variant="secondary"
                              size="sm"
                              onClick={() => {
                                setStackPanels(prev => {
                                  if (prev.length >= 2) return prev;
                                  return [
                                    ...prev,
                                    {
                                      id: 'stack-2',
                                      title: 'Confirm Parse',
                                      subtitle: 'Nested panel (depth 2)',
                                      width: 'sm',
                                      content: (
                                        <div className="p-5 text-sm text-slate-600">
                                          Confirm parsing for 3 documents from Dell Technologies?
                                        </div>
                                      ),
                                      footer: (
                                        <>
                                          <Button variant="ghost" onClick={() => setStackPanels(p => p.filter(x => x.id !== 'stack-2'))}>Cancel</Button>
                                          <Button variant="primary">Confirm</Button>
                                        </>
                                      ),
                                    },
                                  ];
                                });
                              }}
                            >
                              Open nested confirmation
                            </Button>
                          </div>
                        ),
                        footer: (
                          <>
                            <Button variant="ghost" onClick={() => setStackPanels([])}>Close</Button>
                            <Button variant="primary">Continue</Button>
                          </>
                        ),
                      },
                    ]);
                  }}
                >
                  Open stacked slide-over
                </Button>
                <Button size="sm" variant="ghost" onClick={() => setStackPanels([])}>
                  Reset stack
                </Button>
              </div>
            </Card>

            <SlideOver
              open={slideOverOpen}
              onClose={() => setSlideOverOpen(false)}
              width={slideOverWidth}
              title={`${slideOverWidth.toUpperCase()} Slide-Over — New RFQ`}
              subtitle="Complete the form to create a new requisition."
              footer={
                <>
                  <Button variant="ghost" onClick={() => setSlideOverOpen(false)}>Cancel</Button>
                  <Button variant="secondary">Save Draft</Button>
                  <Button variant="primary">Create RFQ</Button>
                </>
              }
            >
              <SlideOverSection title="Basic Information">
                <div className="flex flex-col gap-3">
                  <TextInput label="RFQ Title" placeholder="e.g. Server Infrastructure Refresh" required />
                  <SelectInput
                    label="Category"
                    options={[{ value: 'it', label: 'IT Hardware' }, { value: 'sw', label: 'Software' }]}
                    placeholder="Select category"
                  />
                  <div className="grid grid-cols-2 gap-3">
                    <TextInput label="Deadline" type="date" />
                    <TextInput label="Est. Value ($)" type="number" placeholder="0" />
                  </div>
                  <Textarea label="Description" placeholder="Provide context for vendors…" />
                </div>
              </SlideOverSection>
              <SlideOverSection title="Vendors">
                <Alert variant="neutral" compact>
                  You can add vendors after creating the RFQ.
                </Alert>
              </SlideOverSection>
              <SlideOverSection title="Scoring & Policy">
                <div className="flex flex-col gap-3">
                  <SelectInput
                    label="Scoring Model"
                    options={[{ value: 'v21', label: 'v2.1 (Current)' }, { value: 'v20', label: 'v2.0' }]}
                  />
                  <ToggleSwitch checked={false} onChange={() => {}} label="Apply automatic approval" />
                </div>
              </SlideOverSection>
            </SlideOver>

            <SlideOverStackManager
              stack={stackPanels}
              onClose={id => setStackPanels(prev => prev.filter(panel => panel.id !== id))}
            />
          </Section>

          {/* ═══════════════════════════════════════════════════════════
              P1 WORKFLOW BLOCKS
          ════════════════════════════════════════════════════════════ */}
          <Section id="p1-workflows" title="P1 Workflow Blocks" subtitle="Screen-critical reusable components for Auth, Create RFQ, Intake Detail, Normalization, Comparison, Approval, and Award.">
            <SubSection title="Authentication">
              <div className="grid grid-cols-2 gap-4">
                <SignInCard error="Invalid credentials. Please try again." sessionExpired />
                <MfaPromptPanel />
              </div>
            </SubSection>

            <SubSection title="Create RFQ">
              <div className="space-y-3">
                <Card padding="md">
                  <Stepper
                    steps={[
                      { id: 'meta', label: 'Metadata' },
                      { id: 'line-items', label: 'Line Items' },
                      { id: 'terms', label: 'Terms' },
                      { id: 'attachments', label: 'Attachments' },
                    ]}
                    activeStepId="line-items"
                  />
                </Card>
                <LineItemEditor items={lineItems} onChange={setLineItems} currency="USD" locale="en-US" />
                <UploadDropzoneWithProgress uploads={uploadItems} />
                <div className="rounded-md border border-slate-200 overflow-hidden bg-white">
                  <div className="h-16 px-4 py-3 text-xs text-slate-500">Form content area (demo)</div>
                  <StickyActionBar />
                </div>
              </div>
            </SubSection>

            <SubSection title="Quote Intake Detail">
              <div className="space-y-3">
                <ValidationCallout
                  issues={[
                    'Lead time not detected in document.',
                    'Two lines require manual mapping.',
                  ]}
                />
                <Card padding="md">
                  <div className="flex items-center gap-2 text-xs text-slate-600">
                    Overridden line indicator:
                    <OverrideChip />
                    <RevertControl />
                  </div>
                </Card>
                <div className="rounded-md border border-slate-200 overflow-hidden">
                  <div className="h-12 px-4 py-3 text-xs text-slate-500 bg-white">Quote detail content area (demo)</div>
                  <QuoteDetailActionBar />
                </div>
              </div>
            </SubSection>

            <SubSection title="Normalization Workspace">
              <div className="space-y-3">
                <NormalizationLockBar />
                <Card padding="md">
                  <div className="flex items-center gap-2 text-xs text-slate-600">
                    <ConversionBadge from="EUR 1,240" to="USD 1,330" />
                    <ConflictIndicator message="2 mapping conflicts" />
                  </div>
                </Card>
                <MappingGrid
                  rows={MAPPING_ROWS}
                  selectedIds={selectedMapIds}
                  onSelectedIdsChange={setSelectedMapIds}
                />
              </div>
            </SubSection>

            <SubSection title="Comparison Matrix">
              <div className="space-y-3">
                <ApprovalGateBanner mode="pending" />
                <ReadinessBanner issues={['3 lines have unresolved taxonomy mappings']} />
                <Card padding="md">
                  <div className="flex items-center gap-2 text-xs text-slate-600">
                    Delta example: <DeltaBadge value="+8.3%" />
                  </div>
                </Card>
                <ComparisonMatrixGrid
                  vendors={[
                    { name: 'Dell Technologies', total: '$1,043,250', rank: 1 },
                    { name: 'HP Enterprise', total: '$1,118,334', rank: 2 },
                    { name: 'Lenovo', total: '$1,146,110', rank: 3 },
                  ]}
                  rows={COMPARISON_ROWS}
                />
                <RecommendationCard
                  vendor="Dell Technologies"
                  confidence={82}
                  factors={[
                    'Best total normalized cost',
                    'Strong lead time compliance',
                    'Policy risk score below threshold',
                  ]}
                />
              </div>
            </SubSection>

            <SubSection title="Approvals">
              <div className="grid grid-cols-2 gap-4">
                <Card padding="md">
                  <div className="space-y-3">
                    <div className="flex items-center gap-2 text-xs text-slate-600">
                      Priority: <PriorityMarker priority="high" />
                    </div>
                    <AssignmentControl
                      value={approvalAssignee}
                      onChange={setApprovalAssignee}
                      options={[
                        { value: 'marcus', label: 'Marcus Webb' },
                        { value: 'priya', label: 'Priya Nair' },
                      ]}
                    />
                    <SnoozeControl />
                  </div>
                </Card>
                <DecisionPanel
                  reason={decisionReason}
                  onReasonChange={setDecisionReason}
                />
              </div>
              <div className="mt-3">
                <EvidenceTabsPanel
                  tabs={[
                    { id: 'documents', label: 'Documents', count: 3 },
                    { id: 'screening', label: 'Screening Results', count: 1 },
                    { id: 'audit', label: 'Audit Trail', count: 5 },
                  ]}
                  activeTab={evidenceTab}
                  onChange={setEvidenceTab}
                >
                  <p className="text-xs text-slate-600">
                    Evidence content for: <span className="font-medium text-slate-800">{evidenceTab}</span>
                  </p>
                </EvidenceTabsPanel>
              </div>
            </SubSection>

            <SubSection title="Award Decision">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-3">
                  <AwardDecisionSummary winner="Dell Technologies" total="$1,043,250" savings="$156,487 (13.1%)" confidence={82} />
                  <SplitAllocationEditor vendors={splitAlloc} onChange={setSplitAlloc} />
                  <SignOffChecklist
                    items={signoffs}
                    onToggle={(id, checked) => setSignoffs(prev => prev.map(item => item.id === id ? { ...item, checked } : item))}
                  />
                </div>
                <div className="space-y-3">
                  <DebriefStatusList
                    vendors={[
                      { id: 'hp', name: 'HP Enterprise', status: 'not-sent' },
                      { id: 'lenovo', name: 'Lenovo', status: 'sent' },
                      { id: 'cisco', name: 'Cisco Systems', status: 'not-sent' },
                      { id: 'ibm', name: 'IBM', status: 'na' },
                    ]}
                  />
                  <PayloadPreviewPanel />
                  <HandoffStatusTimeline
                    items={[
                      { id: 'h1', label: 'Payload generated', timestamp: '09:12', state: 'done' },
                      { id: 'h2', label: 'Schema validated', timestamp: '09:14', state: 'done' },
                      { id: 'h3', label: 'ERP handoff', timestamp: 'Pending', state: 'pending' },
                    ]}
                  />
                  <Card padding="md">
                    <div className="flex items-center justify-between">
                      <ProtestTimerBadge />
                      <Button variant="ghost" size="sm">Record Protest</Button>
                    </div>
                  </Card>
                </div>
              </div>
            </SubSection>
          </Section>

          {/* ═══════════════════════════════════════════════════════════
              LAYOUTS
          ════════════════════════════════════════════════════════════ */}
          <Section id="layouts" title="Layouts" subtitle="Scaled previews of the two application layout variants.">
            <SubSection title="TopBar + User Menu + Notification Center (interactive)">
              <Card padding="none">
                <TopBar />
              </Card>
            </SubSection>

            <SubSection title="Layout 1 — Default Layout (200px sidebar + TopBar + Content + Footer)">
              <p className="text-sm text-slate-500 mb-4">
                Used for list views, reports, approval queues, and settings. The sidebar expands navigation groups in accordion fashion.
              </p>
              <div className="border border-slate-300 rounded-xl overflow-hidden shadow-lg">
                <div className="origin-top-left" style={{ transform: 'scale(0.62)', width: '161.3%', height: 480, transformOrigin: '0 0' }}>
                  <div className="flex h-full overflow-hidden bg-slate-100" style={{ height: 774 }}>
                    {/* Sidebar */}
                    <div className="w-[200px] shrink-0 flex flex-col bg-white border-r border-slate-200">
                      <div className="flex items-center gap-2.5 h-14 px-4 border-b border-slate-200">
                        <div className="w-7 h-7 bg-indigo-600 rounded-md flex items-center justify-center">
                          <span className="text-white text-xs font-bold">AQ</span>
                        </div>
                        <span className="text-sm font-semibold text-slate-800">Atomy-Q</span>
                      </div>
                      <nav className="flex-1 flex flex-col py-3 px-2 gap-0.5">
                        <span className="block px-2.5 pt-1 pb-1 text-[10px] font-semibold tracking-widest uppercase text-slate-400">Main</span>
                        <div className="flex items-center gap-2.5 px-2.5 py-1.5 rounded-md text-slate-600 text-sm">
                          <LayoutGrid size={14} className="text-slate-400" /> Dashboard
                        </div>
                        <div className="flex flex-col">
                          <div className="flex items-center gap-2.5 px-2.5 py-1.5 rounded-md text-slate-900 font-medium text-sm">
                            <FileText size={14} className="text-indigo-500" /> Requisition
                            <ChevronRight size={12} className="text-slate-400 rotate-90 ml-auto" />
                          </div>
                          <div className="ml-4 pl-2 border-l border-slate-200 flex flex-col gap-0.5">
                            {[
                              { l: 'Active', b: 12, active: true },
                              { l: 'Closed', b: 5 },
                              { l: 'Awarded', b: 3 },
                              { l: 'Archived' },
                              { l: 'Draft', b: 2 },
                            ].map(item => (
                              <div key={item.l} className={`flex items-center justify-between px-2 py-1 rounded text-xs ${item.active ? 'text-indigo-700 font-medium bg-indigo-50' : 'text-slate-500'}`}>
                                {item.l} {item.b && <span className={`text-[10px] px-1 rounded-full ${item.active ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-200 text-slate-600'}`}>{item.b}</span>}
                              </div>
                            ))}
                          </div>
                        </div>
                        <div className="flex items-center gap-2.5 px-2.5 py-1.5 rounded-md text-slate-600 text-sm">
                          <FolderArchive size={14} className="text-slate-400" /> Documents
                        </div>
                        <div className="flex items-center gap-2.5 px-2.5 py-1.5 rounded-md text-slate-600 text-sm">
                          <BarChart2 size={14} className="text-slate-400" /> Reporting
                        </div>
                      </nav>
                    </div>
                    {/* Content */}
                    <div className="flex-1 flex flex-col overflow-hidden">
                      {/* TopBar */}
                      <div className="h-14 flex items-center justify-between px-5 bg-white border-b border-slate-200 gap-4">
                        <div className="flex-1 max-w-xs h-8 rounded-md border border-slate-200 bg-slate-50 flex items-center px-3 gap-2">
                          <Search size={12} className="text-slate-400" />
                          <span className="text-xs text-slate-400">Search RFQs…</span>
                        </div>
                        <div className="flex items-center gap-2">
                          <div className="text-xs px-2.5 h-7 rounded border border-transparent text-slate-500 flex items-center gap-1"><Sparkles size={11} /> AI Insights</div>
                          <div className="text-xs px-2.5 h-7 rounded bg-indigo-600 text-white flex items-center gap-1"><Plus size={11} /> New RFQ</div>
                          <div className="relative p-1.5"><Bell size={14} className="text-slate-400" /><span className="absolute top-0 right-0 w-3 h-3 bg-red-500 rounded-full text-[8px] text-white flex items-center justify-center font-bold">3</span></div>
                          <div className="w-6 h-6 rounded-full bg-indigo-500 flex items-center justify-center text-[9px] font-semibold text-white">AK</div>
                        </div>
                      </div>
                      {/* Page content */}
                      <div className="flex-1 p-6 bg-slate-100">
                        <div className="flex items-start justify-between mb-4">
                          <div>
                            <h1 className="text-lg font-semibold text-slate-900">Requisitions</h1>
                            <p className="text-sm text-slate-500">12 active requisitions</p>
                          </div>
                          <div className="bg-indigo-600 text-white text-xs px-3 h-8 rounded flex items-center gap-1.5"><Plus size={12} /> Create RFQ</div>
                        </div>
                        {/* Table preview */}
                        <div className="bg-white border border-slate-200 rounded-lg overflow-hidden">
                          <div className="flex items-center gap-3 px-4 py-2 bg-indigo-50 border-b border-indigo-200">
                            <span className="text-xs font-semibold text-indigo-700">2 selected</span>
                            <div className="flex gap-2">
                              {['Close Selected', 'Archive Selected', 'Assign Owner', 'Export'].map(a => (
                                <button key={a} className="text-xs px-2 py-1 rounded border border-indigo-300 bg-white text-indigo-700">{a}</button>
                              ))}
                            </div>
                          </div>
                          <table className="w-full text-xs">
                            <thead>
                              <tr className="bg-slate-50 border-b border-slate-200">
                                <th className="w-8 pl-3 py-2.5 text-left text-slate-500 font-medium"></th>
                                <th className="w-8 py-2.5"></th>
                                <th className="px-3 py-2.5 text-left text-slate-500 font-medium">ID</th>
                                <th className="px-3 py-2.5 text-left text-slate-500 font-medium">RFQ</th>
                                <th className="px-3 py-2.5 text-left text-slate-500 font-medium">Status</th>
                                <th className="px-3 py-2.5 text-left text-slate-500 font-medium">Deadline</th>
                                <th className="px-3 py-2.5 text-right text-slate-500 font-medium">Est. Value</th>
                              </tr>
                            </thead>
                            <tbody>
                              {RFQ_ROWS.slice(0, 5).map((row, i) => (
                                <React.Fragment key={row.id}>
                                  <tr className={`border-b border-slate-100 ${i < 2 ? 'bg-indigo-50/50' : ''}`}>
                                    <td className="pl-3 py-0 h-11"><div className="w-3 h-3 rounded border border-slate-300 bg-white" /></td>
                                    <td className="py-0 pl-2"><div className={`w-4 h-4 flex items-center justify-center text-slate-400`}><ChevronRight size={11} className={i === 0 ? 'rotate-90' : ''} /></div></td>
                                    <td className="px-3 py-0 font-mono text-slate-600">{row.rfqId}</td>
                                    <td className="px-3 py-0">
                                      <div className="text-slate-800 font-medium">{row.title}</div>
                                      <div className="text-slate-400">{row.owner}</div>
                                    </td>
                                    <td className="px-3 py-0"><StatusBadge status={row.status} size="xs" /></td>
                                    <td className="px-3 py-0 text-slate-600">{row.deadline}</td>
                                    <td className="px-3 py-0 text-right font-medium text-slate-800">{row.estValue}</td>
                                  </tr>
                                  {i === 0 && (
                                    <tr className="border-b border-slate-200">
                                      <td colSpan={7}>
                                        <div className="grid grid-cols-6 gap-3 px-4 py-2.5 bg-slate-50 text-[10px]">
                                          {[
                                            { l: 'Category', v: row.category },
                                            { l: 'Deadline', v: row.deadline },
                                            { l: 'Vendors', v: row.vendors },
                                            { l: 'Quotes', v: row.quotes },
                                            { l: 'Est. Value', v: row.estValue },
                                            { l: 'Savings %', v: row.savings },
                                          ].map(it => (
                                            <div key={it.l}>
                                              <span className="block text-slate-400 uppercase tracking-wide font-medium">{it.l}</span>
                                              <span className="block text-slate-700 font-medium">{it.v}</span>
                                            </div>
                                          ))}
                                        </div>
                                      </td>
                                    </tr>
                                  )}
                                </React.Fragment>
                              ))}
                            </tbody>
                          </table>
                          <div className="flex items-center justify-between px-4 py-2.5 border-t border-slate-200">
                            <span className="text-xs text-slate-500">Page 1 of 3</span>
                            <div className="flex gap-1.5">
                              <button className="text-xs px-2.5 h-6 rounded border border-slate-200 text-slate-500">Previous</button>
                              {[1, 2, 3].map(p => (
                                <button key={p} className={`text-xs w-6 h-6 rounded border ${p === 1 ? 'bg-indigo-600 text-white border-indigo-600' : 'border-slate-200 text-slate-600'}`}>{p}</button>
                              ))}
                              <button className="text-xs px-2.5 h-6 rounded border border-slate-200 text-slate-600">Next</button>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div className="h-8 flex items-center justify-between px-5 bg-white border-t border-slate-200 text-[10px] text-slate-400">
                        <span>Atomy-Q · v1.0.0 · Production</span>
                        <span className="flex gap-3"><a href="#">Privacy</a><a href="#">Terms</a><a href="#">Support</a></span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </SubSection>

            <SubSection title="Layout 2 — Workspace Layout (Icon rail + Active Record Menu + Work Surface)">
              <p className="text-sm text-slate-500 mb-4">
                Used for RFQ workspace views (Overview, Quote Intake, Comparison, Approval, Award). The icon rail expands on hover.
              </p>
              <div className="border border-slate-300 rounded-xl overflow-hidden shadow-lg">
                <div className="origin-top-left" style={{ transform: 'scale(0.62)', width: '161.3%', height: 480, transformOrigin: '0 0' }}>
                  <div className="flex h-full overflow-hidden bg-slate-100" style={{ height: 774 }}>
                    {/* Rail */}
                    <div className="w-12 shrink-0 flex flex-col bg-white border-r border-slate-200 items-center py-3 gap-2">
                      <div className="w-7 h-7 bg-indigo-600 rounded-md flex items-center justify-center mb-1">
                        <span className="text-white text-xs font-bold">AQ</span>
                      </div>
                      {[LayoutGrid, FileText, FolderArchive, BarChart2].map((Icon, i) => (
                        <div key={i} className={`w-9 h-9 flex items-center justify-center rounded-md text-slate-400 ${i === 1 ? 'bg-indigo-50 text-indigo-600' : 'hover:bg-slate-100'}`}>
                          <Icon size={15} />
                        </div>
                      ))}
                    </div>
                    {/* Active Record Menu */}
                    <div className="w-[360px] shrink-0 bg-white border-r border-slate-200 flex flex-col overflow-hidden">
                      {/* Snippet */}
                      <div className="px-4 py-4 border-b border-slate-200">
                        <span className="text-[11px] font-mono text-slate-400">RFQ-2401</span>
                        <div className="flex items-start gap-2 mt-1">
                          <h2 className="text-sm font-semibold text-slate-900 flex-1">Server Infrastructure Refresh</h2>
                          <StatusBadge status="active" size="xs" />
                        </div>
                        <div className="grid grid-cols-4 gap-1.5 mt-2 mb-3">
                          {[{ l: 'Vendors', v: '5' }, { l: 'Quotes', v: '8' }, { l: 'Est. Val', v: '$1.2M' }, { l: 'Savings', v: '12%' }].map(m => (
                            <div key={m.l} className="flex flex-col items-center bg-slate-50 border border-slate-200 rounded px-1.5 py-1">
                              <span className="text-[9px] font-medium text-slate-400 uppercase">{m.l}</span>
                              <span className="text-xs font-semibold text-slate-800">{m.v}</span>
                            </div>
                          ))}
                        </div>
                        <div className="h-7 bg-indigo-600 text-white text-xs flex items-center justify-center rounded font-medium">
                          Close for Submissions
                        </div>
                      </div>
                      {/* Nav */}
                      <div className="px-3 py-3 overflow-y-auto flex-1">
                        <span className="block px-3 mb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400">RFQ</span>
                        <div className="flex flex-col gap-0.5 mb-3">
                          {[
                            { l: 'Overview', active: true, icon: Eye },
                            { l: 'Details', icon: FileText },
                            { l: 'Line Items', icon: List },
                            { l: 'Vendors', icon: Users },
                            { l: 'Award', icon: Award },
                          ].map(item => {
                            const Icon = item.icon;
                            return (
                              <div key={item.l} className={`flex items-center gap-2.5 px-3 py-1.5 rounded-md text-sm ${item.active ? 'bg-indigo-50 text-indigo-700 font-medium border-l-2 border-indigo-500 pl-[10px]' : 'text-slate-600'}`}>
                                <Icon size={13} className={item.active ? 'text-indigo-500' : 'text-slate-400'} /> {item.l}
                              </div>
                            );
                          })}
                        </div>
                        <div className="border-t border-slate-200 mb-3" />
                        <span className="block px-3 mb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400">Child Records</span>
                        <div className="flex flex-col gap-0.5">
                          {[
                            { l: 'Quote Intake', b: 8, icon: FileCheck },
                            { l: 'Comparison Runs', b: 3, icon: BarChart2 },
                            { l: 'Approvals', b: 2, icon: ShieldCheck },
                            { l: 'Negotiations', b: 1, icon: GitBranch },
                            { l: 'Documents', b: 12, icon: FileText },
                            { l: 'Risk & Compliance', dot: 'amber', icon: AlertTriangle },
                            { l: 'Decision Trail', icon: List },
                          ].map(item => {
                            const Icon = item.icon;
                            return (
                              <div key={item.l} className="flex items-center gap-2.5 px-3 py-1.5 rounded-md text-sm text-slate-600">
                                <Icon size={13} className="text-slate-400" />
                                <span className="flex-1">{item.l}</span>
                                {item.b && <span className="text-[10px] px-1 py-0 rounded-full bg-slate-200 text-slate-600">{item.b}</span>}
                                {item.dot && <span className="w-2 h-2 rounded-full bg-amber-500" />}
                              </div>
                            );
                          })}
                        </div>
                      </div>
                    </div>
                    {/* Work surface */}
                    <div className="flex-1 flex flex-col overflow-hidden">
                      {/* TopBar */}
                      <div className="h-14 flex items-center justify-between px-5 bg-white border-b border-slate-200 gap-4">
                        <div className="flex-1 max-w-xs h-8 rounded-md border border-slate-200 bg-slate-50 flex items-center px-3 gap-2">
                          <Search size={12} className="text-slate-400" />
                          <span className="text-xs text-slate-400">Search RFQs…</span>
                        </div>
                        <div className="flex items-center gap-2">
                          <div className="text-xs px-2.5 h-7 rounded border border-transparent text-slate-500 flex items-center gap-1"><Sparkles size={11} /> AI Insights</div>
                          <div className="text-xs px-2.5 h-7 rounded bg-indigo-600 text-white flex items-center gap-1"><Plus size={11} /> New RFQ</div>
                          <div className="relative p-1.5"><Bell size={14} className="text-slate-400" /></div>
                          <div className="w-6 h-6 rounded-full bg-indigo-500 flex items-center justify-center text-[9px] font-semibold text-white">AK</div>
                        </div>
                      </div>
                      {/* Breadcrumb */}
                      <div className="flex items-center gap-1 px-5 py-2.5 border-b border-slate-200 bg-white text-xs text-slate-500">
                        <span className="hover:text-indigo-600">RFQs</span><ChevronRight size={11} className="text-slate-300" />
                        <span className="hover:text-indigo-600">Server Infrastructure Refresh</span><ChevronRight size={11} className="text-slate-300" />
                        <span className="text-slate-800 font-medium">Overview</span>
                      </div>
                      {/* Content */}
                      <div className="flex-1 p-5 bg-slate-100 overflow-y-auto">
                        <div className="grid grid-cols-4 gap-3 mb-5">
                          {[
                            { t: 'Quotes Received', v: '8', s: 'of 10 expected', bar: 80 },
                            { t: 'Normalization', v: '75%', s: '18 of 24 lines', circ: 75 },
                            { t: 'Comparison Status', v: 'Preview', badge: 'preview' },
                            { t: 'Approval Status', v: 'None', badge: 'draft' },
                          ].map(kpi => (
                            <div key={kpi.t} className="bg-white border border-slate-200 rounded-lg p-3 shadow-sm">
                              <span className="text-[10px] font-medium text-slate-400 uppercase tracking-wide">{kpi.t}</span>
                              <div className="mt-1 text-xl font-semibold text-slate-900">{kpi.v}</div>
                              {kpi.s && <span className="text-[11px] text-slate-400">{kpi.s}</span>}
                              {kpi.badge && <div className="mt-1"><StatusBadge status={kpi.badge as StatusVariant} size="xs" /></div>}
                              {kpi.bar && (
                                <div className="mt-2 h-1 rounded-full bg-slate-200 overflow-hidden">
                                  <div className="h-full bg-indigo-500 rounded-full" style={{ width: `${kpi.bar}%` }} />
                                </div>
                              )}
                            </div>
                          ))}
                        </div>
                        <div className="bg-white border border-slate-200 rounded-lg overflow-hidden">
                          <div className="px-4 py-3 border-b border-slate-100">
                            <h3 className="text-sm font-semibold text-slate-800">Activity Timeline</h3>
                          </div>
                          <div className="divide-y divide-slate-100">
                            {ACTIVITY_EVENTS.slice(0, 4).map(ev => (
                              <div key={ev.id} className="flex items-start gap-3 px-4 py-3">
                                <div className="w-6 h-6 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center shrink-0">
                                  <span className="w-1.5 h-1.5 rounded-full bg-slate-400" />
                                </div>
                                <div className="flex-1">
                                  <p className="text-xs text-slate-700"><span className="font-medium">{ev.actor}</span> {ev.action}</p>
                                </div>
                                <span className="text-[11px] text-slate-400">{ev.timestamp}</span>
                              </div>
                            ))}
                          </div>
                        </div>
                      </div>
                      <div className="h-8 flex items-center justify-between px-5 bg-white border-t border-slate-200 text-[10px] text-slate-400">
                        <span>Atomy-Q · v1.0.0</span>
                        <span className="flex gap-3"><a href="#">Privacy</a><a href="#">Terms</a></span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </SubSection>

            {/* Active Record Menu Component */}
            <SubSection title="Active Record Menu Component (standalone)">
              <div className="flex gap-4">
                <div className="w-[320px] border border-slate-200 rounded-lg overflow-hidden shadow-sm">
                  <ActiveRecordMenu
                    rfqId="RFQ-2401"
                    rfqTitle="Server Infrastructure Refresh"
                    status="active"
                    metrics={{ vendors: 5, quotes: 8, estValue: '$1.2M', savings: '12%' }}
                    activeNavId="quote-intake"
                  />
                </div>
                <div className="flex-1">
                  <Alert variant="info" compact className="mb-3">
                    The Active Record Menu is the left panel in Workspace Layout. It contains two zones:
                  </Alert>
                  <div className="flex flex-col gap-2 text-sm text-slate-600">
                    <div className="flex gap-2">
                      <span className="w-5 h-5 bg-indigo-100 text-indigo-700 rounded text-xs font-bold flex items-center justify-center shrink-0">1</span>
                      <p><strong>Record Snippet</strong> — RFQ ID, title, status badge, 4 metric chips, primary lifecycle action button.</p>
                    </div>
                    <div className="flex gap-2">
                      <span className="w-5 h-5 bg-indigo-100 text-indigo-700 rounded text-xs font-bold flex items-center justify-center shrink-0">2</span>
                      <p><strong>Navigation</strong> — RFQ group (5 links) + Child Records group (7 links with count badges). Active link has indigo left border + bg tint.</p>
                    </div>
                  </div>
                </div>
              </div>
            </SubSection>
          </Section>

        </div>
      </main>
    </div>
  );
}



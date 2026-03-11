import React from 'react';
import {
  createBrowserRouter,
  createRoutesFromElements,
  Link,
  Navigate,
  Outlet,
  Route,
  RouterProvider,
  useLocation,
  useNavigate,
  useParams,
  useSearchParams,
} from 'react-router';
import {
  AlertCircle,
  ArrowRight,
  BarChart2,
  Bell,
  Bot,
  Calendar,
  CheckCircle2,
  ChevronRight,
  CircleAlert,
  Clock3,
  FileText,
  Filter,
  FolderArchive,
  GitCompareArrows,
  HandCoins,
  History,
  Inbox,
  LayoutPanelTop,
  Lock,
  Mail,
  Plus,
  RefreshCw,
  Scale,
  Search,
  ShieldAlert,
  ShieldCheck,
  Sparkles,
  SquarePen,
  Users,
} from 'lucide-react';
import { DefaultLayout, WorkspaceLayout } from './components/ds/Layout';
import { ActiveRecordMenu } from './components/ds/ActiveRecordMenu';
import { WorkspaceBreadcrumbs } from './components/ds/WorkspaceBreadcrumbs';
import { PageHeader, FilterBar, SectionHeader } from './components/ds/FilterBar';
import { Card, DocPreview, EmptyState, InfoGrid, InlineDetailPanel, SectionCard, UploadZone } from './components/ds/Card';
import { KPIScorecard } from './components/ds/KPIScorecard';
import { StatusBadge, ConfidenceBadge, CountBadge, SLATimerBadge, VersionChip } from './components/ds/Badge';
import { Button } from './components/ds/Button';
import { Checkbox, SearchInput, SelectInput, TextInput, Textarea, ToggleSwitch } from './components/ds/Input';
import { SecondaryTabs, VerticalTabs } from './components/ds/Tabs';
import { RecordHeader } from './components/ds/RecordHeader';
import { Alert, Banner, Checklist } from './components/ds/Alert';
import { Stepper, StickyActionBar, LineItemEditor, UploadDropzoneWithProgress } from './components/ds/CreateRFQComponents';
import { ValidationCallout, OverrideChip, QuoteDetailActionBar, RevertControl } from './components/ds/QuoteIntakeComponents';
import { MappingGrid, NormalizationLockBar } from './components/ds/NormalizationComponents';
import { ApprovalGateBanner, DeltaBadge, MissingValuePlaceholder, ReadinessBanner, RecommendationCard, VendorSummaryHeader } from './components/ds/ComparisonComponents';
import { AssignmentControl, DecisionPanel, EvidenceTabsPanel, PriorityMarker, SnoozeControl } from './components/ds/ApprovalComponents';
import { AwardDecisionSummary, DebriefStatusList, HandoffStatusTimeline, PayloadPreviewPanel, ProtestTimerBadge, SignOffChecklist, SplitAllocationEditor } from './components/ds/AwardComponents';
import {
  PipelineStatCard,
  SavingsHighlightCard,
  SLAAlertCard,
  ActivitySummaryCard,
  PendingApprovalsCard,
  CategoryBreakdownCard,
  QuickActionCard,
  type ActivitySummaryItem,
  type PendingApprovalItem,
} from './components/ds/DashboardCards';
import { SpotlightSearch, type SpotlightResult } from './components/ds/SpotlightSearch';
import { SlideOver, SlideOverSection } from './components/ds/SlideOver';
import { SignInCard, MfaPromptPanel } from './components/ds/AuthComponents';
import { Avatar, AvatarLabel, AvatarStack } from './components/ds/Avatar';
import { DataTable, type ColumnDef, type TableSort } from './components/ds/DataTable';
import {
  cloneMockData,
  type ApprovalRecord,
  type AppSeed,
  type DecisionTrailRecord,
  type DocumentRecord,
  type NegotiationRecord,
  type NotificationRecord,
  type QuoteLineRecord,
  type QuoteSubmissionRecord,
  type RfqLineItemRecord,
  type RfqRecord,
  type RiskRecord,
  type UserSummary,
  type VendorRecord,
} from './data/mockData';

type CreateRfqDraft = {
  title: string;
  category: string;
  department: string;
  description: string;
  submissionDeadline: string;
  evaluationMethod: string;
  paymentTerms: string;
  lineItems: Array<{
    id: string;
    entryType?: 'line' | 'heading';
    heading?: string;
    subheading?: string;
    description: string;
    qty: number;
    unit: string;
    targetPrice: number;
  }>;
};

type AppContextValue = {
  data: AppSeed;
  markNotificationRead: (notificationId: string) => void;
  markAllNotificationsRead: () => void;
  createRfq: (draft: CreateRfqDraft, publish: boolean) => string;
  updateRfq: (rfqId: string, patch: Partial<RfqRecord>) => void;
  updateRfqLineItems: (rfqId: string, lineItems: RfqLineItemRecord[]) => void;
  updateQuote: (quoteId: string, patch: Partial<QuoteSubmissionRecord>) => void;
  updateQuoteLine: (quoteId: string, lineId: string, patch: Partial<QuoteLineRecord>) => void;
  addQuote: (rfqId: string, vendorId: string, fileName: string) => string;
  createComparisonRun: (rfqId: string, mode: 'preview' | 'final', scoringModel: string) => string;
  updateRun: (runId: string, updater: (run: AppSeed['comparisonRuns'][number]) => AppSeed['comparisonRuns'][number]) => void;
  updateApproval: (approvalId: string, patch: Partial<ApprovalRecord>) => void;
  decideApproval: (approvalId: string, outcome: 'approved' | 'rejected' | 'returned', reason: string) => void;
  createNegotiation: (rfqId: string, vendorId: string) => string;
  addNegotiationRound: (negotiationId: string, note: string, value: string) => void;
  finalizeAward: (rfqId: string) => void;
  updateAwardSplit: (rfqId: string, split: NonNullable<RfqRecord['award']>['splitAllocation']) => void;
  toggleAwardChecklist: (rfqId: string, checkId: string, checked: boolean) => void;
  sendDebrief: (rfqId: string, vendorId: string) => void;
  retryHandoff: (rfqId: string) => void;
  selectedVendorId: string | null;
  openVendorProfile: (vendorId: string) => void;
  closeVendorProfile: () => void;
  aiInsightsOpen: boolean;
  openAiInsights: () => void;
  closeAiInsights: () => void;
};

const AppContext = React.createContext<AppContextValue | null>(null);

function useAppStore(): AppContextValue {
  const value = React.useContext(AppContext);
  if (!value) {
    throw new Error('App context is not available.');
  }
  return value;
}

function getNextRfqId(data: AppSeed): string {
  const next = Math.max(...data.rfqs.map(item => Number(item.id.replace('RFQ-', ''))), 2400) + 1;
  return `RFQ-${next}`;
}

function getNextSequentialId(prefix: string, values: string[]): string {
  const next = Math.max(...values.map(value => Number(value.replace(`${prefix}-`, ''))), 0) + 1;
  return `${prefix}-${String(next).padStart(5, '0')}`.replace('-00000', '-00001');
}

function AppProvider({ children }: { children: React.ReactNode }) {
  const [data, setData] = React.useState<AppSeed>(() => cloneMockData());
  const [selectedVendorId, setSelectedVendorId] = React.useState<string | null>(null);
  const [aiInsightsOpen, setAiInsightsOpen] = React.useState(false);

  const markNotificationRead = React.useCallback((notificationId: string) => {
    setData(prev => ({
      ...prev,
      notifications: prev.notifications.map(notification =>
        notification.id === notificationId ? { ...notification, unread: false } : notification,
      ),
    }));
  }, []);

  const markAllNotificationsRead = React.useCallback(() => {
    setData(prev => ({
      ...prev,
      notifications: prev.notifications.map(notification => ({ ...notification, unread: false })),
    }));
  }, []);

  const createRfq = React.useCallback((draft: CreateRfqDraft, publish: boolean) => {
    const nextId = getNextRfqId(data);
    const estTotal = draft.lineItems.reduce((sum, item) => sum + item.qty * item.targetPrice, 0);
    const rfq: RfqRecord = {
      id: nextId,
      title: draft.title,
      status: publish ? 'active' : 'draft',
      category: draft.category,
      department: draft.department,
      ownerId: data.currentUser.id,
      description: draft.description,
      deadline: draft.submissionDeadline.slice(0, 10),
      deadlineLabel: draft.submissionDeadline || 'TBD',
      submissionDeadline: draft.submissionDeadline,
      evaluationMethod: draft.evaluationMethod,
      paymentTerms: draft.paymentTerms,
      estValue: `$${Math.max(estTotal, 1).toLocaleString()}`,
      savings: '0%',
      primaryAction: publish ? 'Close for Submissions' : 'Submit/Publish',
      expectedQuotes: 0,
      activity: publish
        ? [{ id: `${nextId}-activity`, timestamp: 'Just now', actor: data.currentUser.name, type: 'RFQ published', detail: 'Draft submitted to the RFQ workspace.' }]
        : [{ id: `${nextId}-activity`, timestamp: 'Just now', actor: data.currentUser.name, type: 'Draft saved', detail: 'RFQ draft stored for later editing.' }],
      lineItems: draft.lineItems.map(item => ({
        id: item.id,
        description: item.description,
        qty: item.qty,
        unit: item.unit,
        targetPrice: item.targetPrice,
        category: draft.category,
        entryType: item.entryType,
        heading: item.heading,
      })),
      vendorIds: [],
      quoteIds: [],
      comparisonRunIds: [],
      approvalIds: [],
      negotiationIds: [],
      documentIds: [],
      riskIds: [],
      decisionTrailIds: [],
      award: null,
    };

    setData(prev => ({
      ...prev,
      rfqs: [rfq, ...prev.rfqs],
    }));

    return nextId;
  }, [data]);

  const updateRfq = React.useCallback((rfqId: string, patch: Partial<RfqRecord>) => {
    setData(prev => ({
      ...prev,
      rfqs: prev.rfqs.map(rfq => (rfq.id === rfqId ? { ...rfq, ...patch } : rfq)),
    }));
  }, []);

  const updateRfqLineItems = React.useCallback((rfqId: string, lineItems: RfqLineItemRecord[]) => {
    setData(prev => ({
      ...prev,
      rfqs: prev.rfqs.map(rfq => (rfq.id === rfqId ? { ...rfq, lineItems } : rfq)),
    }));
  }, []);

  const updateQuote = React.useCallback((quoteId: string, patch: Partial<QuoteSubmissionRecord>) => {
    setData(prev => ({
      ...prev,
      quotes: prev.quotes.map(quote => (quote.id === quoteId ? { ...quote, ...patch } : quote)),
    }));
  }, []);

  const updateQuoteLine = React.useCallback((quoteId: string, lineId: string, patch: Partial<QuoteLineRecord>) => {
    setData(prev => ({
      ...prev,
      quotes: prev.quotes.map(quote => (
        quote.id === quoteId
          ? {
              ...quote,
              lineItems: quote.lineItems.map(line => (line.id === lineId ? { ...line, ...patch } : line)),
            }
          : quote
      )),
    }));
  }, []);

  const addQuote = React.useCallback((rfqId: string, vendorId: string, fileName: string) => {
    const nextId = `QT-${Math.max(...data.quotes.map(quote => Number(quote.id.replace('QT-', ''))), 1000) + 1}`;
    const submission: QuoteSubmissionRecord = {
      id: nextId,
      rfqId,
      vendorId,
      fileName,
      status: 'processing',
      parseConfidence: 45,
      uploadedAt: 'Just now',
      validationIssues: ['Parsing in progress.'],
      previewPageInfo: 'Processing upload',
      overviewNotes: ['Fresh upload pending extraction'],
      normalizedLocked: false,
      normalizedStale: false,
      lineItems: [],
    };

    setData(prev => ({
      ...prev,
      quotes: [submission, ...prev.quotes],
      rfqs: prev.rfqs.map(rfq => (
        rfq.id === rfqId
          ? {
              ...rfq,
              quoteIds: [nextId, ...rfq.quoteIds],
              activity: [
                { id: `${nextId}-activity`, timestamp: 'Just now', actor: prev.currentUser.name, type: 'Quote uploaded', detail: `${fileName} added to intake.` },
                ...rfq.activity,
              ],
            }
          : rfq
      )),
    }));

    return nextId;
  }, [data]);

  const createComparisonRun = React.useCallback((rfqId: string, mode: 'preview' | 'final', scoringModel: string) => {
    const baseRun = data.comparisonRuns.find(run => run.rfqId === rfqId) ?? data.comparisonRuns[0];
    const runNumber = Math.max(...data.comparisonRuns.map(run => Number(run.id.replace('RUN-', ''))), 0) + 1;
    const newRunId = `RUN-${String(runNumber).padStart(3, '0')}`;
    const approvalId = mode === 'final'
      ? `APR-${String(Math.max(...data.approvals.map(item => Number(item.id.replace('APR-', ''))), 0) + 1).padStart(5, '0')}`
      : undefined;

    setData(prev => ({
      ...prev,
      comparisonRuns: [
        {
          ...baseRun,
          id: newRunId,
          rfqId,
          createdAt: 'Just now',
          createdBy: prev.currentUser.name,
          type: mode,
          status: mode === 'final' ? 'locked' : 'generated',
          scoringModel,
          approvalState: mode === 'final' ? 'pending' : 'pending',
          readinessIssues: mode === 'preview' ? ['Resolve remaining normalization stale items before finalizing.'] : [],
          linkedApprovalId: approvalId,
        },
        ...prev.comparisonRuns,
      ],
      approvals: approvalId
        ? [
            {
              id: approvalId,
              rfqId,
              type: 'comparison approval',
              summary: 'New final comparison run requires approval.',
              priority: 'medium',
              sla: { variant: 'warning', value: '2d 0h' },
              assignee: prev.users[2]?.name ?? prev.currentUser.name,
              status: 'pending',
              createdAt: 'Just now',
              reasonRequired: 'Final run requires approval reasoning.',
              gateReasons: ['Final recommendation generated from comparison engine'],
              evidenceTabs: {
                documents: ['Latest comparison run evidence'],
                screening: ['No new screening alerts'],
                audit: ['Run generated'],
                comparison: ['Recommendation ready for review'],
              },
              history: [
                { id: `${approvalId}-history`, timestamp: 'Just now', actor: prev.currentUser.name, type: 'Approval created', detail: `${newRunId} submitted for approval.` },
              ],
              linkedRunId: newRunId,
              routeAfterApproval: 'award',
            },
            ...prev.approvals,
          ]
        : prev.approvals,
      rfqs: prev.rfqs.map(rfq => (
        rfq.id === rfqId
          ? {
              ...rfq,
              comparisonRunIds: [newRunId, ...rfq.comparisonRunIds],
              approvalIds: approvalId ? [approvalId, ...rfq.approvalIds] : rfq.approvalIds,
              activity: [
                { id: `${newRunId}-activity`, timestamp: 'Just now', actor: prev.currentUser.name, type: 'Comparison run created', detail: `${newRunId} generated as ${mode}.` },
                ...rfq.activity,
              ],
            }
          : rfq
      )),
    }));

    return newRunId;
  }, [data]);

  const updateRun = React.useCallback((runId: string, updater: (run: AppSeed['comparisonRuns'][number]) => AppSeed['comparisonRuns'][number]) => {
    setData(prev => ({
      ...prev,
      comparisonRuns: prev.comparisonRuns.map(run => (run.id === runId ? updater(run) : run)),
    }));
  }, []);

  const updateApproval = React.useCallback((approvalId: string, patch: Partial<ApprovalRecord>) => {
    setData(prev => ({
      ...prev,
      approvals: prev.approvals.map(approval => (approval.id === approvalId ? { ...approval, ...patch } : approval)),
    }));
  }, []);

  const decideApproval = React.useCallback((approvalId: string, outcome: 'approved' | 'rejected' | 'returned', reason: string) => {
    setData(prev => {
      const approval = prev.approvals.find(item => item.id === approvalId);
      if (!approval) {
        return prev;
      }

      const nextStatus = outcome === 'returned' ? 'returned' : outcome;

      return {
        ...prev,
        approvals: prev.approvals.map(item => (
          item.id === approvalId
            ? {
                ...item,
                status: nextStatus,
                history: [
                  { id: `${approvalId}-${Date.now()}`, timestamp: 'Just now', actor: prev.currentUser.name, type: nextStatus, detail: reason },
                  ...item.history,
                ],
              }
            : item
        )),
        comparisonRuns: prev.comparisonRuns.map(run => (
          run.linkedApprovalId === approvalId
            ? {
                ...run,
                approvalState: outcome === 'approved' ? 'approved' : outcome === 'rejected' ? 'rejected' : 'pending',
              }
            : run
        )),
        rfqs: prev.rfqs.map(rfq => (
          rfq.id === approval.rfqId
            ? {
                ...rfq,
                activity: [
                  { id: `${approvalId}-decision`, timestamp: 'Just now', actor: prev.currentUser.name, type: `Approval ${nextStatus}`, detail: reason },
                  ...rfq.activity,
                ],
              }
            : rfq
        )),
      };
    });
  }, []);

  const createNegotiation = React.useCallback((rfqId: string, vendorId: string) => {
    const nextId = `NEG-${Math.max(...data.negotiations.map(item => Number(item.id.replace('NEG-', ''))), 0) + 1}`;

    setData(prev => ({
      ...prev,
      negotiations: [
        {
          id: nextId,
          rfqId,
          vendorId,
          status: 'active',
          strategy: 'Counter commercial terms',
          bafoDue: 'In 2 days',
          owner: prev.currentUser.name,
          rounds: [{ id: `${nextId}-round-1`, label: 'Round 1', timestamp: 'Just now', note: 'Negotiation launched.', value: 'Pending' }],
        },
        ...prev.negotiations,
      ],
      rfqs: prev.rfqs.map(rfq => (
        rfq.id === rfqId ? { ...rfq, negotiationIds: [nextId, ...rfq.negotiationIds] } : rfq
      )),
    }));

    return nextId;
  }, [data]);

  const addNegotiationRound = React.useCallback((negotiationId: string, note: string, value: string) => {
    setData(prev => ({
      ...prev,
      negotiations: prev.negotiations.map(negotiation => (
        negotiation.id === negotiationId
          ? {
              ...negotiation,
              rounds: [
                {
                  id: `${negotiationId}-${Date.now()}`,
                  label: `Round ${negotiation.rounds.length + 1}`,
                  timestamp: 'Just now',
                  note,
                  value,
                },
                ...negotiation.rounds,
              ],
            }
          : negotiation
      )),
    }));
  }, []);

  const finalizeAward = React.useCallback((rfqId: string) => {
    setData(prev => ({
      ...prev,
      rfqs: prev.rfqs.map(rfq => (
        rfq.id === rfqId && rfq.award
          ? {
              ...rfq,
              status: 'awarded',
              primaryAction: 'View Award',
              award: { ...rfq.award, finalized: true },
            }
          : rfq
      )),
    }));
  }, []);

  const updateAwardSplit = React.useCallback((rfqId: string, split: NonNullable<RfqRecord['award']>['splitAllocation']) => {
    setData(prev => ({
      ...prev,
      rfqs: prev.rfqs.map(rfq => (
        rfq.id === rfqId && rfq.award ? { ...rfq, award: { ...rfq.award, splitAllocation: split } } : rfq
      )),
    }));
  }, []);

  const toggleAwardChecklist = React.useCallback((rfqId: string, checkId: string, checked: boolean) => {
    setData(prev => ({
      ...prev,
      rfqs: prev.rfqs.map(rfq => (
        rfq.id === rfqId && rfq.award
          ? {
              ...rfq,
              award: {
                ...rfq.award,
                signOffChecklist: rfq.award.signOffChecklist.map(item => (
                  item.id === checkId ? { ...item, checked } : item
                )),
              },
            }
          : rfq
      )),
    }));
  }, []);

  const sendDebrief = React.useCallback((rfqId: string, vendorId: string) => {
    setData(prev => ({
      ...prev,
      rfqs: prev.rfqs.map(rfq => (
        rfq.id === rfqId && rfq.award
          ? {
              ...rfq,
              award: {
                ...rfq.award,
                debriefs: rfq.award.debriefs.map(item => (
                  item.id === vendorId ? { ...item, status: 'sent' } : item
                )),
              },
            }
          : rfq
      )),
    }));
  }, []);

  const retryHandoff = React.useCallback((rfqId: string) => {
    setData(prev => ({
      ...prev,
      rfqs: prev.rfqs.map(rfq => (
        rfq.id === rfqId && rfq.award
          ? {
              ...rfq,
              award: {
                ...rfq.award,
                handoffTimeline: rfq.award.handoffTimeline.map(item => (
                  item.state === 'failed' ? { ...item, state: 'pending', timestamp: 'Retry queued' } : item
                )),
              },
            }
          : rfq
      )),
    }));
  }, []);

  const value = React.useMemo<AppContextValue>(() => ({
    data,
    markNotificationRead,
    markAllNotificationsRead,
    createRfq,
    updateRfq,
    updateRfqLineItems,
    updateQuote,
    updateQuoteLine,
    addQuote,
    createComparisonRun,
    updateRun,
    updateApproval,
    decideApproval,
    createNegotiation,
    addNegotiationRound,
    finalizeAward,
    updateAwardSplit,
    toggleAwardChecklist,
    sendDebrief,
    retryHandoff,
    selectedVendorId,
    openVendorProfile: setSelectedVendorId,
    closeVendorProfile: () => setSelectedVendorId(null),
    aiInsightsOpen,
    openAiInsights: () => setAiInsightsOpen(true),
    closeAiInsights: () => setAiInsightsOpen(false),
  }), [
    data,
    markNotificationRead,
    markAllNotificationsRead,
    createRfq,
    updateRfq,
    updateRfqLineItems,
    updateQuote,
    updateQuoteLine,
    addQuote,
    createComparisonRun,
    updateRun,
    updateApproval,
    decideApproval,
    createNegotiation,
    addNegotiationRound,
    finalizeAward,
    updateAwardSplit,
    toggleAwardChecklist,
    sendDebrief,
    retryHandoff,
    selectedVendorId,
    aiInsightsOpen,
  ]);

  const selectedVendor = data.vendors.find(vendor => vendor.id === selectedVendorId) ?? null;
  const selectedVendorRfqs = selectedVendor
    ? data.rfqs.filter(rfq => rfq.vendorIds.includes(selectedVendor.id))
    : [];

  return (
    <AppContext.Provider value={value}>
      {children}
      <SlideOver
        open={Boolean(selectedVendor)}
        onClose={() => setSelectedVendorId(null)}
        title={selectedVendor?.name ?? 'Vendor profile'}
        subtitle="Performance, risk, and sourcing history"
        width="lg"
      >
        {selectedVendor && (
          <>
            <SlideOverSection>
              <InfoGrid
                cols={4}
                items={[
                  { label: 'Risk', value: <StatusBadge status={selectedVendor.risk === 'high' ? 'rejected' : selectedVendor.risk === 'medium' ? 'pending' : 'approved'} label={selectedVendor.risk.toUpperCase()} /> },
                  { label: 'Lead Time', value: selectedVendor.leadTime },
                  { label: 'On-time Rate', value: selectedVendor.onTimeRate },
                  { label: 'Spend', value: selectedVendor.totalSpend },
                ]}
              />
            </SlideOverSection>
            <SlideOverSection title="Supplier profile">
              <p className="text-sm text-slate-600">{selectedVendor.notes}</p>
              <div className="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
                <p className="text-xs uppercase tracking-wide text-slate-400">Contact</p>
                <p className="mt-1 text-sm font-medium text-slate-800">{selectedVendor.contact}</p>
                <p className="mt-2 text-xs text-slate-500">{selectedVendor.diversity}</p>
              </div>
            </SlideOverSection>
            <SlideOverSection title="Recent RFQ history">
              <div className="space-y-2">
                {selectedVendorRfqs.map(rfq => (
                  <div key={rfq.id} className="rounded-lg border border-slate-200 px-4 py-3">
                    <div className="flex items-center justify-between gap-3">
                      <div>
                        <p className="text-sm font-medium text-slate-800">{rfq.title}</p>
                        <p className="text-xs text-slate-500">{rfq.id} · {rfq.category}</p>
                      </div>
                      <StatusBadge status={rfq.status === 'active' ? 'active' : rfq.status === 'closed' ? 'closed' : rfq.status === 'awarded' ? 'awarded' : rfq.status === 'draft' ? 'draft' : 'closed'} />
                    </div>
                  </div>
                ))}
              </div>
            </SlideOverSection>
          </>
        )}
      </SlideOver>
      <SlideOver
        open={aiInsightsOpen}
        onClose={() => setAiInsightsOpen(false)}
        title="AI Insights"
        subtitle="Mock intelligence digest powered by the design shell"
        width="md"
        footer={<Button onClick={() => setAiInsightsOpen(false)}>Close</Button>}
      >
        <SlideOverSection title="Today">
          <div className="space-y-3">
            {data.aiInsights.map(insight => (
              <div key={insight} className="rounded-lg border border-indigo-100 bg-indigo-50 px-4 py-3">
                <div className="flex items-start gap-2">
                  <Sparkles size={14} className="mt-0.5 shrink-0 text-indigo-600" />
                  <p className="text-sm text-indigo-900">{insight}</p>
                </div>
              </div>
            ))}
          </div>
        </SlideOverSection>
      </SlideOver>
    </AppContext.Provider>
  );
}

function useEntityLookups() {
  const { data } = useAppStore();

  const getRfq = React.useCallback((rfqId?: string) => data.rfqs.find(item => item.id === rfqId), [data.rfqs]);
  const getVendor = React.useCallback((vendorId?: string) => data.vendors.find(item => item.id === vendorId), [data.vendors]);
  const getUser = React.useCallback((userId?: string) => data.users.find(item => item.id === userId), [data.users]);
  const getQuote = React.useCallback((quoteId?: string) => data.quotes.find(item => item.id === quoteId), [data.quotes]);
  const getRun = React.useCallback((runId?: string) => data.comparisonRuns.find(item => item.id === runId), [data.comparisonRuns]);
  const getApproval = React.useCallback((approvalId?: string) => data.approvals.find(item => item.id === approvalId), [data.approvals]);
  const getNegotiation = React.useCallback((negotiationId?: string) => data.negotiations.find(item => item.id === negotiationId), [data.negotiations]);

  return { getRfq, getVendor, getUser, getQuote, getRun, getApproval, getNegotiation };
}

function useSpotlightSearch() {
  const navigate = useNavigate();
  const { data } = useAppStore();
  const [open, setOpen] = React.useState(false);
  const [value, setValue] = React.useState('');
  const [selectedIndex, setSelectedIndex] = React.useState(0);

  const results = React.useMemo((): SpotlightResult[] => {
    const q = value.trim().toLowerCase();
    if (!q) {
      return [
        ...data.rfqs.slice(0, 5).map(r => ({
          id: r.id,
          type: 'rfq' as const,
          label: r.title,
          path: `/rfqs/${r.id}`,
          description: r.description,
          metrics: [
            { label: 'Status', value: r.status },
            { label: 'Est. Value', value: r.estValue },
            { label: 'Savings', value: r.savings },
          ],
        })),
        ...data.vendors.slice(0, 4).map(v => ({
          id: v.id,
          type: 'vendor' as const,
          label: v.name,
          path: `/rfqs/RFQ-2401/vendors`, // simplified
          description: v.notes,
          metrics: [
            { label: 'Total Spend', value: v.totalSpend },
            { label: 'On-time', value: v.onTimeRate },
          ],
        })),
        ...data.users.slice(0, 3).map(u => ({
          id: u.id,
          type: 'person' as const,
          label: u.name,
          path: '/settings/users',
          metrics: [
            { label: 'Role', value: u.role },
            { label: 'Email', value: u.email },
          ],
        })),
        ...data.documents.slice(0, 3).map(d => ({
          id: d.id,
          type: 'document' as const,
          label: d.title,
          path: d.rfqId ? `/rfqs/${d.rfqId}/documents` : '/documents',
          metrics: [
            { label: 'Size', value: d.size },
            { label: 'Updated', value: d.updatedAt },
          ],
        })),
      ];
    }
    const out: SpotlightResult[] = [];
    data.rfqs.forEach(r => {
      if (`${r.id} ${r.title} ${r.category}`.toLowerCase().includes(q)) {
        out.push({
          id: r.id,
          type: 'rfq',
          label: r.title,
          path: `/rfqs/${r.id}`,
          description: r.description,
          metrics: [{ label: 'Status', value: r.status }, { label: 'Est. Value', value: r.estValue }],
        });
      }
    });
    data.vendors.forEach(v => {
      if (`${v.name} ${v.contact} ${v.diversity}`.toLowerCase().includes(q)) {
        out.push({
          id: v.id,
          type: 'vendor',
          label: v.name,
          path: `/rfqs/RFQ-2401/vendors`,
          metrics: [{ label: 'Total Spend', value: v.totalSpend }],
        });
      }
    });
    data.users.forEach(u => {
      if (`${u.name} ${u.role} ${u.email}`.toLowerCase().includes(q)) {
        out.push({
          id: u.id,
          type: 'person',
          label: u.name,
          path: '/settings/users',
          metrics: [{ label: 'Role', value: u.role }],
        });
      }
    });
    data.documents.forEach(d => {
      if (`${d.title} ${d.tag}`.toLowerCase().includes(q)) {
        out.push({
          id: d.id,
          type: 'document',
          label: d.title,
          path: d.rfqId ? `/rfqs/${d.rfqId}/documents` : '/documents',
          metrics: [{ label: 'Size', value: d.size }],
        });
      }
    });
    return out;
  }, [value, data.rfqs, data.vendors, data.users, data.documents]);

  React.useEffect(() => {
    setSelectedIndex(0);
  }, [value]);

  React.useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes((e.target as HTMLElement)?.tagName ?? '')) {
        e.preventDefault();
        setOpen(true);
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, []);

  return {
    open,
    setOpen,
    value,
    setValue,
    selectedIndex,
    setSelectedIndex,
    results,
    onSelect: (r: SpotlightResult) => {
      navigate(r.path);
      setOpen(false);
    },
  };
}

function useTopBarProps() {
  const navigate = useNavigate();
  const { data, markNotificationRead, markAllNotificationsRead, openAiInsights } = useAppStore();
  const spotlight = useSpotlightSearch();

  return {
    user: { name: data.currentUser.name, role: data.currentUser.role },
    notificationCount: data.notifications.filter(notification => notification.unread).length,
    notifications: data.notifications,
    searchValue: spotlight.value,
    onSearch: (v: string) => {
      spotlight.setValue(v);
      spotlight.setOpen(true);
    },
    onSearchFocus: () => spotlight.setOpen(true),
    onNewRFQ: () => navigate('/rfqs/create'),
    onAIInsights: openAiInsights,
    onNotificationClick: (notificationId: string) => {
      const notification = data.notifications.find(item => item.id === notificationId);
      if (!notification) return;
      markNotificationRead(notificationId);
      navigate(notification.targetPath);
    },
    onMarkAllNotificationsRead: markAllNotificationsRead,
    onUserSettings: () => navigate('/settings/users'),
    onLogout: () => navigate('/signin'),
    spotlight,
  };
}

function mapNavIdToPath(id: string): string {
  switch (id) {
    case 'dashboard':
      return '/dashboard';
    case 'rfq-closed':
      return '/rfqs?status=closed';
    case 'rfq-awarded':
      return '/rfqs?status=awarded';
    case 'rfq-archived':
      return '/rfqs?status=archived';
    case 'rfq-draft':
      return '/rfqs?status=draft';
    case 'documents':
      return '/documents';
    case 'reporting':
      return '/reporting';
    case 'settings-users':
      return '/settings/users';
    case 'settings-scoring':
      return '/settings/scoring-policies';
    case 'settings-templates':
      return '/settings/templates';
    case 'settings-integrations':
      return '/settings/integrations';
    case 'settings-flags':
      return '/settings/feature-flags';
    default:
      return '/rfqs?status=active';
  }
}

function getDefaultActiveNav(pathname: string, searchParams: URLSearchParams): string {
  if (pathname.startsWith('/dashboard')) return 'dashboard';
  if (pathname.startsWith('/approvals') || pathname.startsWith('/notifications')) return '';
  if (pathname.startsWith('/documents')) return 'documents';
  if (pathname.startsWith('/reporting')) return 'reporting';
  if (pathname.startsWith('/settings/users')) return 'settings-users';
  if (pathname.startsWith('/settings/scoring-policies')) return 'settings-scoring';
  if (pathname.startsWith('/settings/templates')) return 'settings-templates';
  if (pathname.startsWith('/settings/integrations')) return 'settings-integrations';
  if (pathname.startsWith('/settings/feature-flags')) return 'settings-flags';
  if (pathname.startsWith('/rfqs')) {
    const status = searchParams.get('status') ?? 'active';
    return `rfq-${status}`;
  }
  return 'dashboard';
}

function getWorkspaceActivePath(pathname: string): string {
  if (pathname.includes('/details')) return 'details';
  if (pathname.includes('/line-items')) return 'line-items';
  if (pathname.includes('/vendors')) return 'vendors';
  if (pathname.includes('/award')) return 'award';
  if (pathname.includes('/intake')) return 'quote-intake';
  if (pathname.includes('/runs')) return 'comparison-runs';
  if (pathname.includes('/approvals')) return 'approvals';
  if (pathname.includes('/negotiations')) return 'negotiations';
  if (pathname.includes('/documents')) return 'documents';
  if (pathname.includes('/risk')) return 'risk';
  if (pathname.includes('/decision-trail')) return 'decision-trail';
  return 'overview';
}

function WorkspaceCanvas({
  segments,
  children,
}: {
  segments: Array<{ label: string; path: string }>;
  children: React.ReactNode;
}) {
  const navigate = useNavigate();
  return (
    <>
      <WorkspaceBreadcrumbs segments={segments} onNavigate={path => navigate(path)} />
      <div className="flex-1 overflow-y-auto">
        <div className="space-y-5 p-5">{children}</div>
      </div>
    </>
  );
}

function DefaultRouteLayout() {
  const navigate = useNavigate();
  const location = useLocation();
  const [searchParams] = useSearchParams();
  const topBarProps = useTopBarProps();
  const { spotlight } = topBarProps;

  return (
    <>
      <DefaultLayout
        activeNav={getDefaultActiveNav(location.pathname, searchParams)}
        onNavChange={id => navigate(mapNavIdToPath(id))}
        topBarProps={{
          ...topBarProps,
          onSearchFocus: spotlight ? () => spotlight.setOpen(true) : undefined,
        }}
      >
        <Outlet />
      </DefaultLayout>
      {spotlight && (
        <SpotlightSearch
          open={spotlight.open}
          onClose={() => spotlight.setOpen(false)}
          value={spotlight.value}
          onChange={spotlight.setValue}
          results={spotlight.results}
          selectedIndex={spotlight.selectedIndex}
          onSelectedIndexChange={spotlight.setSelectedIndex}
          onSelect={spotlight.onSelect}
        />
      )}
    </>
  );
}

function WorkspaceRouteLayout() {
  const navigate = useNavigate();
  const location = useLocation();
  const { rfqId = '' } = useParams();
  const [lifecycleOpen, setLifecycleOpen] = React.useState(false);
  const { data, updateRfq } = useAppStore();
  const { getRfq } = useEntityLookups();
  const topBarProps = useTopBarProps();
  const rfq = getRfq(rfqId);

  if (!rfq) {
    return (
      <WorkspaceLayout topBarProps={topBarProps}>
        <div className="flex flex-1 items-center justify-center">
          <EmptyState title="RFQ not found" description="The requested workspace record does not exist in the mock dataset." />
        </div>
      </WorkspaceLayout>
    );
  }

  const quoteCount = data.quotes.filter(quote => quote.rfqId === rfq.id).length;
  const activeRisk = data.risks.filter(risk => risk.rfqId === rfq.id);
  const riskDot = activeRisk.some(risk => risk.status === 'flagged')
    ? 'red'
    : activeRisk.some(risk => risk.status === 'pending')
      ? 'amber'
      : 'green';

  const primaryTabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'details', label: 'Details' },
    { id: 'line-items', label: 'Line Items' },
    { id: 'vendors', label: 'Vendors' },
    ...(rfq.award ? [{ id: 'award', label: 'Award' }] : []),
  ];

  const childLinks = [
    { id: 'quote-intake', label: 'Quote Intake', badge: quoteCount },
    { id: 'comparison-runs', label: 'Comparison Runs', badge: rfq.comparisonRunIds.length },
    { id: 'approvals', label: 'Approvals', badge: rfq.approvalIds.length },
    { id: 'negotiations', label: 'Negotiations', badge: rfq.negotiationIds.length },
    { id: 'documents', label: 'Documents', badge: rfq.documentIds.length },
    { id: 'risk', label: 'Risk & Compliance', statusDot: riskDot },
    { id: 'decision-trail', label: 'Decision Trail' },
  ];

  const { spotlight } = topBarProps;

  return (
    <>
      <WorkspaceLayout
        activeNav={`rfq-${rfq.status}`}
        onNavChange={id => navigate(mapNavIdToPath(id))}
        topBarProps={{
          ...topBarProps,
          onSearchFocus: spotlight ? () => spotlight.setOpen(true) : undefined,
        }}
        activeRecordMenu={(
          <ActiveRecordMenu
            rfqId={rfq.id}
            rfqTitle={rfq.title}
            status={rfq.status === 'active' ? 'active' : rfq.status === 'closed' ? 'closed' : rfq.status === 'awarded' ? 'awarded' : rfq.status === 'draft' ? 'draft' : 'closed'}
            metrics={{
              vendors: rfq.vendorIds.length,
              quotes: quoteCount,
              estValue: rfq.estValue,
              savings: rfq.savings,
            }}
            primaryAction={rfq.primaryAction}
            onPrimaryAction={() => setLifecycleOpen(true)}
            primaryTabs={primaryTabs}
            childRecordLinks={childLinks}
            activeNavId={getWorkspaceActivePath(location.pathname)}
            onNavChange={id => {
              const base = `/rfqs/${rfq.id}`;
              const target =
                id === 'overview' ? `${base}/overview`
                  : id === 'details' ? `${base}/details`
                    : id === 'line-items' ? `${base}/line-items`
                      : id === 'vendors' ? `${base}/vendors`
                        : id === 'award' ? `${base}/award`
                          : id === 'quote-intake' ? `${base}/intake`
                            : id === 'comparison-runs' ? `${base}/runs`
                              : id === 'approvals' ? `${base}/approvals`
                                : id === 'negotiations' ? `${base}/negotiations`
                                  : id === 'documents' ? `${base}/documents`
                                    : id === 'risk' ? `${base}/risk`
                                      : `${base}/decision-trail`;
              navigate(target);
            }}
          />
        )}
      >
        <Outlet />
      </WorkspaceLayout>
      {spotlight && (
        <SpotlightSearch
          open={spotlight.open}
          onClose={() => spotlight.setOpen(false)}
          value={spotlight.value}
          onChange={spotlight.setValue}
          results={spotlight.results}
          selectedIndex={spotlight.selectedIndex}
          onSelectedIndexChange={spotlight.setSelectedIndex}
          onSelect={spotlight.onSelect}
        />
      )}
      <SlideOver
        open={lifecycleOpen}
        onClose={() => setLifecycleOpen(false)}
        title={rfq.primaryAction}
        subtitle="Lifecycle action confirmation"
        width="sm"
        footer={(
          <>
            <Button variant="ghost" onClick={() => setLifecycleOpen(false)}>Cancel</Button>
            <Button
              onClick={() => {
                if (rfq.status === 'draft') {
                  updateRfq(rfq.id, { status: 'active', primaryAction: 'Close for Submissions' });
                } else if (rfq.status === 'active') {
                  updateRfq(rfq.id, { status: 'closed', primaryAction: 'Reopen' });
                } else if (rfq.status === 'closed') {
                  updateRfq(rfq.id, { status: 'active', primaryAction: 'Close for Submissions' });
                }
                setLifecycleOpen(false);
              }}
            >
              Confirm
            </Button>
          </>
        )}
      >
        <SlideOverSection title="Summary">
          <p className="text-sm text-slate-600">
            This updates the mock RFQ status and the active record menu state so the workspace flow continues naturally.
          </p>
        </SlideOverSection>
        <SlideOverSection title="Current record">
          <InfoGrid
            cols={2}
            items={[
              { label: 'RFQ', value: rfq.id },
              { label: 'Current status', value: <StatusBadge status={rfq.status === 'active' ? 'active' : rfq.status === 'closed' ? 'closed' : rfq.status === 'awarded' ? 'awarded' : rfq.status === 'draft' ? 'draft' : 'closed'} /> },
            ]}
          />
        </SlideOverSection>
      </SlideOver>
    </>
  );
}

function DashboardPage() {
  const navigate = useNavigate();
  const { data } = useAppStore();

  const pipeline = React.useMemo(() => ({
    active: data.rfqs.filter(r => r.status === 'active').length,
    pending: data.rfqs.filter(r => r.status === 'closed' && !r.award?.finalized).length,
    awarded: data.rfqs.filter(r => r.status === 'awarded' || r.award?.finalized).length,
    draft: data.rfqs.filter(r => r.status === 'draft').length,
    closed: data.rfqs.filter(r => r.status === 'closed').length,
  }), [data.rfqs]);

  const pendingApprovalsList: PendingApprovalItem[] = React.useMemo(() =>
    data.approvals
      .filter(a => a.status === 'pending')
      .map(a => {
        const rfq = data.rfqs.find(r => r.id === a.rfqId);
        return {
          id: a.id,
          rfqId: a.rfqId,
          rfqTitle: rfq?.title ?? a.rfqId,
          type: a.type.replace(/\b\w/g, c => c.toUpperCase()),
          assignee: a.assignee,
          submittedAt: a.createdAt,
        };
      }),
    [data.approvals, data.rfqs]
  );

  const activityItems: ActivitySummaryItem[] = React.useMemo(() => {
    const items: ActivitySummaryItem[] = [];
    data.rfqs.slice(0, 4).forEach(rfq => {
      rfq.activity?.slice(0, 2).forEach((act, i) => {
        items.push({
          id: `${rfq.id}-act-${i}`,
          timestamp: act.timestamp,
          actor: act.actor,
          action: `${act.type}: ${act.detail}`,
          rfqId: rfq.id,
        });
      });
    });
    return items.sort((a, b) => (a.timestamp < b.timestamp ? 1 : -1)).slice(0, 6);
  }, [data.rfqs]);

  const recentRuns = data.comparisonRuns.slice(0, 3);
  const { savingsMetrics, slaAlerts, categoryBreakdown } = data.dashboard;

  return (
    <div className="max-w-[1280px] space-y-6">
      {/* ─ Header ─ */}
      <PageHeader
        title="Operational dashboard"
        subtitle="Role-aware overview of sourcing workload, approvals, and risk"
        actions={<Button icon={<Plus size={14} />} onClick={() => navigate('/rfqs/create')}>Create RFQ</Button>}
      />

      {/* ─ Row 1: Pipeline stats (5 cols) ─ */}
      <div className="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
        <PipelineStatCard label="Active" count={pipeline.active} icon={<FileText size={18} />} onClick={() => navigate('/rfqs?status=active')} />
        <PipelineStatCard label="Pending" count={pipeline.pending} icon={<Clock3 size={18} />} onClick={() => navigate('/rfqs?status=closed')} />
        <PipelineStatCard label="Awarded" count={pipeline.awarded} icon={<CheckCircle2 size={18} />} onClick={() => navigate('/rfqs?status=awarded')} />
        <PipelineStatCard label="Draft" count={pipeline.draft} icon={<FileText size={18} />} onClick={() => navigate('/rfqs?status=draft')} />
        <PipelineStatCard label="Closed" count={pipeline.closed} onClick={() => navigate('/rfqs?status=closed')} />
      </div>

      {/* ─ Row 2: Savings metrics (3 cols) ─ */}
      <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
        <SavingsHighlightCard
          title="Total realized savings"
          value={savingsMetrics.totalRealized}
          trend={{ value: savingsMetrics.yoyTrend, label: `+${savingsMetrics.yoyTrend}% YoY` }}
          onClick={() => navigate('/reporting')}
        />
        <SavingsHighlightCard
          title="Pipeline value"
          value={savingsMetrics.totalPipeline}
          subtitle="In active RFQs"
          onClick={() => navigate('/rfqs?status=active')}
        />
        <SavingsHighlightCard
          title="Avg. savings"
          value={savingsMetrics.avgSavingsPct}
          subtitle="Across awarded events"
        />
      </div>

      {/* ─ Row 3: Main content (12-col grid) ─ */}
      <div className="grid grid-cols-1 gap-5 lg:grid-cols-12">
        {/* My tasks — 5 cols */}
        <div className="lg:col-span-5">
          <SectionCard title="My tasks" subtitle="Deep links into active work">
            <div className="space-y-2">
              {data.dashboard.tasks.map(task => (
                <button
                  key={task.id}
                  onClick={() => navigate(task.targetPath)}
                  className="flex w-full items-center justify-between rounded-lg border border-slate-200 px-4 py-2.5 text-left transition-colors hover:border-indigo-300 hover:bg-indigo-50/40"
                >
                  <div className="min-w-0">
                    <p className="text-sm font-medium text-slate-800 truncate">{task.title}</p>
                    <p className="text-xs text-slate-500 truncate">{task.subtitle}</p>
                  </div>
                  <span className="shrink-0 ml-2"><PriorityMarker priority={task.priority} /></span>
                </button>
              ))}
            </div>
          </SectionCard>
        </div>

        {/* SLA alerts — 4 cols */}
        <div className="lg:col-span-4">
          <SectionCard title="SLA alerts" subtitle="Time-sensitive items">
            <div className="space-y-2">
              {slaAlerts.map(sla => (
                <SLAAlertCard
                  key={sla.id}
                  title={sla.title}
                  rfqId={sla.rfqId}
                  timeRemaining={sla.timeRemaining}
                  urgency={sla.urgency}
                  assignee={sla.assignee}
                  onClick={() => navigate(sla.targetPath)}
                />
              ))}
            </div>
          </SectionCard>
        </div>

        {/* Pending approvals — 3 cols */}
        <div className="lg:col-span-3">
          <PendingApprovalsCard
            items={pendingApprovalsList}
            onItemClick={id => {
              const a = data.approvals.find(x => x.id === id);
              if (a) navigate(`/rfqs/${a.rfqId}/approvals/${a.id}`);
            }}
            onViewAll={() => navigate('/approvals')}
          />
        </div>
      </div>

      {/* ─ Row 4: Activity + Category + Quick actions (12-col) ─ */}
      <div className="grid grid-cols-1 gap-5 lg:grid-cols-12">
        <div className="lg:col-span-6">
          <ActivitySummaryCard
            items={activityItems}
            title="Recent activity"
            maxItems={5}
            onViewAll={() => navigate('/rfqs')}
          />
        </div>
        <div className="lg:col-span-4">
          <CategoryBreakdownCard
            items={categoryBreakdown}
            title="Spend by category"
            maxItems={5}
            onViewAll={() => navigate('/rfqs')}
          />
        </div>
        <div className="lg:col-span-2 space-y-3">
          <QuickActionCard
            icon={<Plus size={18} />}
            title="Create RFQ"
            description="Start new requisition"
            actionLabel="New RFQ"
            onAction={() => navigate('/rfqs/create')}
          />
          <QuickActionCard
            icon={<Inbox size={18} />}
            title="Intake queue"
            description="Review submissions"
            actionLabel="Open"
            onAction={() => navigate('/rfqs')}
          />
          <QuickActionCard
            icon={<BarChart2 size={18} />}
            title="Reports"
            description="Analytics & exports"
            actionLabel="View"
            onAction={() => navigate('/reporting')}
          />
        </div>
      </div>

      {/* ─ Row 5: Recent comparison runs ─ */}
      <SectionCard title="Recent comparison runs" subtitle="Latest generated and locked evaluations">
        <div className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
          {recentRuns.map(run => (
            <Card key={run.id} hover onClick={() => navigate(`/rfqs/${run.rfqId}/runs/${run.id}`)}>
              <div className="flex items-center justify-between gap-3">
                <div className="min-w-0">
                  <p className="text-sm font-semibold text-slate-800 truncate">{run.id}</p>
                  <p className="text-xs text-slate-500 truncate">{run.rfqId} · {run.scoringModel}</p>
                </div>
                <StatusBadge status={run.status === 'locked' ? 'locked' : run.status === 'stale' ? 'stale' : run.type === 'preview' ? 'preview' : 'generated'} label={run.type === 'preview' ? 'Preview' : run.status === 'locked' ? 'Locked' : 'Generated'} className="shrink-0" />
              </div>
              <div className="mt-3 flex items-center justify-between text-xs text-slate-500">
                <span>{run.createdBy}</span>
                <span>{run.createdAt}</span>
              </div>
            </Card>
          ))}
        </div>
      </SectionCard>
    </div>
  );
}

function RfqListPage() {
  const navigate = useNavigate();
  const { data } = useAppStore();
  const { getUser } = useEntityLookups();
  const [searchParams] = useSearchParams();
  const [searchValue, setSearchValue] = React.useState('');
  const [ownerFilter, setOwnerFilter] = React.useState('');
  const [categoryFilter, setCategoryFilter] = React.useState('');
  const [selectedIds, setSelectedIds] = React.useState<Array<string | number>>([]);
  const [expandedId, setExpandedId] = React.useState<string | number | null>(null);
  const [page, setPage] = React.useState(1);
  const [actionRfq, setActionRfq] = React.useState<RfqRecord | null>(null);
  const [bulkAction, setBulkAction] = React.useState<string>('');
  const statusFilter = searchParams.get('status') ?? 'active';

  const filtered = data.rfqs.filter(rfq => {
    const owner = getUser(rfq.ownerId);
    return rfq.status === statusFilter
      && (!searchValue || `${rfq.id} ${rfq.title}`.toLowerCase().includes(searchValue.toLowerCase()))
      && (!ownerFilter || rfq.ownerId === ownerFilter)
      && (!categoryFilter || rfq.category === categoryFilter)
      && Boolean(owner);
  });

  const pageSize = 4;
  const totalPages = Math.max(1, Math.ceil(filtered.length / pageSize));
  const pageRows = filtered.slice((page - 1) * pageSize, page * pageSize);

  React.useEffect(() => {
    setPage(1);
  }, [statusFilter, searchValue, ownerFilter, categoryFilter]);

  const columns: Array<ColumnDef<RfqRecord>> = [
    { key: 'id', label: 'ID', width: '110px', render: row => <span className="font-mono text-xs text-slate-500">{row.id}</span> },
    {
      key: 'title',
      label: 'RFQ',
      minWidth: '260px',
      render: row => {
        const owner = getUser(row.ownerId)!;
        return <AvatarLabel name={row.title} subtitle={owner.name} size="sm" />;
      },
    },
    { key: 'status', label: 'Status', width: '120px', render: row => <StatusBadge status={row.status === 'active' ? 'active' : row.status === 'closed' ? 'closed' : row.status === 'awarded' ? 'awarded' : row.status === 'draft' ? 'draft' : 'closed'} /> },
    { key: 'deadlineLabel', label: 'Deadline', width: '150px', render: row => <span className="text-sm text-slate-700">{row.deadlineLabel}</span> },
    { key: 'estValue', label: 'Est. Value', width: '140px', align: 'right', render: row => <span className="text-sm font-medium text-slate-800">{row.estValue}</span> },
  ];

  const activeFilters = [
    ownerFilter ? { key: 'owner', label: 'Owner', value: getUser(ownerFilter)?.name ?? '' } : null,
    categoryFilter ? { key: 'category', label: 'Category', value: categoryFilter } : null,
  ].filter(Boolean) as Array<{ key: string; label: string; value: string }>;

  return (
    <div className="space-y-5">
      <PageHeader
        title="RFQ pipeline"
        subtitle={`Browsing ${statusFilter} requisitions with compact review actions and expandable summaries`}
        actions={<Button icon={<Plus size={14} />} onClick={() => navigate('/rfqs/create')}>Create RFQ</Button>}
      />

      <FilterBar
        searchValue={searchValue}
        onSearchChange={setSearchValue}
        searchPlaceholder="Search RFQ ID or title"
        filters={[
          {
            key: 'owner',
            label: 'Owner',
            options: [{ value: '', label: 'All owners' }, ...data.users.map(user => ({ value: user.id, label: user.name }))],
            value: ownerFilter,
            onChange: setOwnerFilter,
          },
          {
            key: 'category',
            label: 'Category',
            options: [{ value: '', label: 'All categories' }, ...Array.from(new Set(data.rfqs.map(item => item.category))).map(category => ({ value: category, label: category }))],
            value: categoryFilter,
            onChange: setCategoryFilter,
          },
        ]}
        activeFilters={activeFilters}
        onRemoveFilter={key => {
          if (key === 'owner') setOwnerFilter('');
          if (key === 'category') setCategoryFilter('');
        }}
        onClearAll={() => {
          setOwnerFilter('');
          setCategoryFilter('');
        }}
      />

      <DataTable
        columns={columns}
        rows={pageRows}
        selectable
        expandable
        expandedId={expandedId}
        onExpandChange={setExpandedId}
        renderExpanded={row => (
          <InlineDetailPanel
            items={[
              { label: 'Category', value: row.category },
              { label: 'Deadline', value: row.deadlineLabel },
              { label: 'Vendors', value: row.vendorIds.length },
              { label: 'Quotes', value: row.quoteIds.length },
              { label: 'Savings', value: row.savings },
              { label: 'Method', value: row.evaluationMethod },
            ]}
          />
        )}
        selectedIds={selectedIds}
        onSelectChange={setSelectedIds}
        expandedIndentColumns={2}
        bulkActions={[
          { label: 'Close selected', onClick: () => setBulkAction('close') },
          { label: 'Archive selected', onClick: () => setBulkAction('archive') },
          { label: 'Assign owner', onClick: () => setBulkAction('assign') },
          { label: 'Export selected', onClick: () => setBulkAction('export') },
        ]}
        showActions
        onRowAction={row => setActionRfq(row)}
        onRowClick={row => navigate(`/rfqs/${row.id}`)}
        emptyState={<EmptyState title="No RFQs match this filter" description="Try adjusting the status or the active filters." icon={<FolderArchive size={20} />} />}
      />

      <div className="flex items-center justify-between text-sm text-slate-500">
        <span>Showing {(page - 1) * pageSize + 1}-{Math.min(page * pageSize, filtered.length)} of {filtered.length}</span>
        <div className="flex items-center gap-2">
          <Button variant="ghost" size="sm" disabled={page === 1} onClick={() => setPage(current => current - 1)}>Previous</Button>
          <span>Page {page} / {totalPages}</span>
          <Button variant="ghost" size="sm" disabled={page === totalPages} onClick={() => setPage(current => current + 1)}>Next</Button>
        </div>
      </div>

      <SlideOver
        open={Boolean(actionRfq)}
        onClose={() => setActionRfq(null)}
        title={actionRfq?.id ?? 'RFQ actions'}
        subtitle="Context actions from the row overflow menu"
        width="sm"
      >
        <SlideOverSection>
          <div className="space-y-2">
            {actionRfq && (
              <>
                <Button fullWidth variant="ghost" onClick={() => navigate(`/rfqs/${actionRfq.id}`)}>Open workspace</Button>
                <Button fullWidth variant="ghost" onClick={() => navigate(`/rfqs/create?duplicate=${actionRfq.id}`)}>Duplicate into new RFQ</Button>
                <Button fullWidth variant="ghost">Export PDF</Button>
                <Button fullWidth variant="destructive">Archive</Button>
              </>
            )}
          </div>
        </SlideOverSection>
      </SlideOver>

      <SlideOver
        open={Boolean(bulkAction)}
        onClose={() => setBulkAction('')}
        title="Bulk action confirmation"
        subtitle={`${selectedIds.length} selected RFQs`}
        width="sm"
        footer={(
          <>
            <Button variant="ghost" onClick={() => setBulkAction('')}>Cancel</Button>
            <Button onClick={() => {
              setSelectedIds([]);
              setBulkAction('');
            }}>Apply</Button>
          </>
        )}
      >
        <SlideOverSection title="Action">
          <p className="text-sm text-slate-600">
            This shell confirms the bulk action flow for <strong>{bulkAction}</strong> without calling a live backend.
          </p>
        </SlideOverSection>
      </SlideOver>
    </div>
  );
}

function CreateRfqPage() {
  const navigate = useNavigate();
  const { data, createRfq } = useAppStore();
  const { getRfq } = useEntityLookups();
  const [searchParams] = useSearchParams();
  const duplicateId = searchParams.get('duplicate');
  const duplicateSource = getRfq(duplicateId ?? undefined);
  const [activeStep, setActiveStep] = React.useState('metadata');
  const [showTemplatePicker, setShowTemplatePicker] = React.useState(false);
  const [discardOpen, setDiscardOpen] = React.useState(false);
  const [uploads, setUploads] = React.useState([{ id: 'up-1', fileName: 'scope_of_work.pdf', progress: 100 }]);
  const [draft, setDraft] = React.useState<CreateRfqDraft>(() => ({
    title: duplicateSource ? `${duplicateSource.title} Copy` : '',
    category: duplicateSource?.category ?? 'IT Infrastructure',
    department: duplicateSource?.department ?? 'Technology Services',
    description: duplicateSource?.description ?? '',
    submissionDeadline: duplicateSource?.submissionDeadline ?? '2026-04-10 17:00',
    evaluationMethod: duplicateSource?.evaluationMethod ?? 'Weighted commercial + technical score',
    paymentTerms: duplicateSource?.paymentTerms ?? 'Net 45',
    lineItems: duplicateSource
      ? duplicateSource.lineItems.map(item => ({
          id: `${item.id}-dup`,
          description: item.description,
          qty: item.qty,
          unit: item.unit,
          targetPrice: item.targetPrice,
          entryType: item.entryType,
          heading: item.heading,
        }))
      : [
          { id: 'draft-1', description: 'Core server node', qty: 12, unit: 'EA', targetPrice: 14500 },
          { id: 'draft-2', description: 'Storage shelf', qty: 2, unit: 'EA', targetPrice: 88000 },
        ],
  }));

  const steps = [
    { id: 'metadata', label: 'Metadata' },
    { id: 'line-items', label: 'Line items' },
    { id: 'commercial', label: 'Commercial' },
    { id: 'attachments', label: 'Attachments' },
  ];

  function publish(publishNow: boolean) {
    if (publishNow && (!draft.title || !draft.category || draft.lineItems.length === 0)) {
      const firstFail = !draft.title ? 'metadata' : !draft.lineItems.length ? 'line-items' : 'commercial';
      setActiveStep(firstFail);
      return;
    }
    const nextId = createRfq(draft, publishNow);
    if (publishNow) {
      navigate(`/rfqs/${nextId}`);
      return;
    }
  }

  return (
    <div className="space-y-5">
      <PageHeader
        title="Create requisition"
        subtitle="Multi-step RFQ creation with draft save, template usage, and sticky actions"
        actions={<Button variant="outline" onClick={() => setShowTemplatePicker(true)}>Create from Template</Button>}
      />

      <Card>
        <Stepper steps={steps} activeStepId={activeStep} />
      </Card>

      <div className="grid gap-5 xl:grid-cols-[1fr,320px]">
        <div className="space-y-5">
          <SectionCard title="RFQ metadata" subtitle="Core request identity and ownership" actions={<Button variant="ghost" size="sm" onClick={() => setActiveStep('metadata')}>Jump</Button>}>
            <div className="grid gap-4 md:grid-cols-2">
              <TextInput label="Title" value={draft.title} onChange={event => setDraft(current => ({ ...current, title: event.target.value }))} />
              <SelectInput
                label="Category"
                value={draft.category}
                onChange={event => setDraft(current => ({ ...current, category: event.target.value }))}
                options={Array.from(new Set(data.rfqs.map(item => item.category))).map(category => ({ value: category, label: category }))}
              />
              <TextInput label="Department" value={draft.department} onChange={event => setDraft(current => ({ ...current, department: event.target.value }))} />
              <TextInput label="Submission deadline" value={draft.submissionDeadline} onChange={event => setDraft(current => ({ ...current, submissionDeadline: event.target.value }))} />
              <Textarea label="Description" containerClassName="md:col-span-2" value={draft.description} onChange={event => setDraft(current => ({ ...current, description: event.target.value }))} />
            </div>
          </SectionCard>

          <SectionCard title="Line item editor" subtitle="Supports headings, ordering, and target pricing" actions={<Button variant="ghost" size="sm" onClick={() => setActiveStep('line-items')}>Jump</Button>}>
            <LineItemEditor items={draft.lineItems} onChange={lineItems => setDraft(current => ({ ...current, lineItems }))} />
          </SectionCard>

          <SectionCard title="Commercial details" subtitle="Terms, evaluation method, and payment profile" actions={<Button variant="ghost" size="sm" onClick={() => setActiveStep('commercial')}>Jump</Button>}>
            <div className="grid gap-4 md:grid-cols-2">
              <TextInput label="Evaluation method" value={draft.evaluationMethod} onChange={event => setDraft(current => ({ ...current, evaluationMethod: event.target.value }))} />
              <TextInput label="Payment terms" value={draft.paymentTerms} onChange={event => setDraft(current => ({ ...current, paymentTerms: event.target.value }))} />
            </div>
          </SectionCard>

          <SectionCard title="Attachments" subtitle="Supporting documents with progress tracking" actions={<Button variant="ghost" size="sm" onClick={() => setActiveStep('attachments')}>Jump</Button>}>
            <UploadDropzoneWithProgress
              uploads={uploads}
              onBrowse={() => setUploads(current => [...current, { id: `upload-${current.length + 1}`, fileName: `attachment_${current.length + 1}.pdf`, progress: 100 }])}
            />
          </SectionCard>
        </div>

        <div className="space-y-5">
          <SectionCard title="Summary" subtitle="Used to keep multi-step flow low-friction">
            <InfoGrid
              items={[
                { label: 'Line items', value: draft.lineItems.length },
                { label: 'Category', value: draft.category || 'Unassigned' },
                { label: 'Department', value: draft.department || 'Unassigned' },
                { label: 'Deadline', value: draft.submissionDeadline || 'Not set' },
              ]}
            />
          </SectionCard>
          <Alert variant="info" title="Keyboard-friendly pattern">
            Consistent save controls stay pinned at the bottom while template selection and discard are handled in right-edge slide-overs.
          </Alert>
        </div>
      </div>

      <StickyActionBar
        onCancel={() => setDiscardOpen(true)}
        onSaveDraft={() => publish(false)}
        onSubmit={() => publish(true)}
      />

      <SlideOver open={showTemplatePicker} onClose={() => setShowTemplatePicker(false)} title="RFQ template picker" subtitle="Select a starting structure" width="md">
        <SlideOverSection title="Templates">
          <div className="space-y-3">
            {data.settings.templates.map(template => (
              <button
                key={template.id}
                onClick={() => {
                  setDraft(current => ({
                    ...current,
                    title: template.name,
                    category: template.category,
                  }));
                  setShowTemplatePicker(false);
                }}
                className="flex w-full items-center justify-between rounded-lg border border-slate-200 px-4 py-3 text-left hover:border-indigo-300 hover:bg-indigo-50/50"
              >
                <div>
                  <p className="text-sm font-medium text-slate-800">{template.name}</p>
                  <p className="text-xs text-slate-500">{template.category} · {template.lineItemCount} lines</p>
                </div>
                <ChevronRight size={14} className="text-slate-400" />
              </button>
            ))}
          </div>
        </SlideOverSection>
      </SlideOver>

      <SlideOver
        open={discardOpen}
        onClose={() => setDiscardOpen(false)}
        title="Discard changes?"
        subtitle="Unsaved edits will be lost"
        width="sm"
        footer={(
          <>
            <Button variant="ghost" onClick={() => setDiscardOpen(false)}>Continue editing</Button>
            <Button variant="destructive" onClick={() => navigate('/rfqs?status=draft')}>Discard</Button>
          </>
        )}
      >
        <SlideOverSection>
          <p className="text-sm text-slate-600">This mirrors the slide-over confirmation pattern defined in the blueprint for navigation away from a dirty multi-step form.</p>
        </SlideOverSection>
      </SlideOver>
    </div>
  );
}

function useWorkspaceRfq(required = true) {
  const { rfqId = '' } = useParams();
  const { getRfq } = useEntityLookups();
  const rfq = getRfq(rfqId);

  if (!rfq && required) {
    throw new Error(`RFQ ${rfqId} not found`);
  }

  return rfq;
}

function WorkspaceOverviewPage() {
  const rfq = useWorkspaceRfq();
  if (!rfq) return null;

  const { data } = useAppStore();
  const quoteCount = data.quotes.filter(quote => quote.rfqId === rfq.id).length;
  const acceptedQuotes = data.quotes.filter(quote => quote.rfqId === rfq.id && quote.status === 'accepted').length;
  const latestRun = data.comparisonRuns.find(run => run.rfqId === rfq.id);
  const pendingApproval = data.approvals.find(approval => approval.rfqId === rfq.id && approval.status === 'pending');

  return (
    <WorkspaceCanvas segments={[{ label: 'RFQs', path: '/rfqs' }, { label: rfq.title, path: `/rfqs/${rfq.id}/overview` }, { label: 'Overview', path: `/rfqs/${rfq.id}/overview` }]}>
      <div className="grid gap-4 xl:grid-cols-4 md:grid-cols-2">
        <KPIScorecard title="Quotes received" value={quoteCount} subtitle={`Expected ${rfq.expectedQuotes || 'n/a'}`} progress={{ value: rfq.expectedQuotes ? Math.round((quoteCount / Math.max(rfq.expectedQuotes, 1)) * 100) : 0, type: 'bar' }} />
        <KPIScorecard title="Normalization progress" value={`${acceptedQuotes}/${quoteCount}`} subtitle="Accepted quotes ready for comparison" progress={{ value: quoteCount ? Math.round((acceptedQuotes / quoteCount) * 100) : 0, type: 'bar' }} />
        <KPIScorecard title="Comparison status" value={latestRun?.id ?? 'Not run'} subtitle={latestRun ? latestRun.status : 'No runs yet'} badge={latestRun ? <StatusBadge status={latestRun.status === 'stale' ? 'stale' : latestRun.type === 'preview' ? 'preview' : latestRun.status === 'locked' ? 'locked' : 'generated'} label={latestRun.type === 'preview' ? 'Preview' : latestRun.status === 'locked' ? 'Locked' : latestRun.status} /> : undefined} />
        <KPIScorecard title="Approval status" value={pendingApproval ? 'Pending' : 'None'} subtitle={pendingApproval?.id ?? 'No open approvals'} badge={pendingApproval ? <StatusBadge status="pending" /> : <StatusBadge status="draft" label="None" />} />
      </div>

      <SectionCard title="Activity timeline" subtitle="Chronological feed for the active RFQ">
        <div className="space-y-3">
          {rfq.activity.map(item => (
            <div key={item.id} className="flex gap-3 rounded-lg border border-slate-200 px-4 py-3">
              <div className="mt-1 h-2.5 w-2.5 rounded-full bg-indigo-500" />
              <div>
                <p className="text-sm font-medium text-slate-800">{item.type}</p>
                <p className="text-xs text-slate-500">{item.actor} · {item.timestamp}</p>
                <p className="mt-1 text-sm text-slate-600">{item.detail}</p>
              </div>
            </div>
          ))}
        </div>
      </SectionCard>
    </WorkspaceCanvas>
  );
}

function WorkspaceDetailsPage() {
  const rfq = useWorkspaceRfq();
  const { updateRfq } = useAppStore();
  const [editOpen, setEditOpen] = React.useState(false);
  const [draftValues, setDraftValues] = React.useState({ title: rfq?.title ?? '', description: rfq?.description ?? '', paymentTerms: rfq?.paymentTerms ?? '' });
  if (!rfq) return null;

  return (
    <>
      <WorkspaceCanvas segments={[{ label: 'RFQs', path: '/rfqs' }, { label: rfq.title, path: `/rfqs/${rfq.id}/overview` }, { label: 'Details', path: `/rfqs/${rfq.id}/details` }]}>
        <PageHeader title="RFQ details" subtitle="Metadata, terms, and deadlines" actions={<Button variant="outline" onClick={() => setEditOpen(true)} icon={<SquarePen size={14} />}>Edit</Button>} />
        <div className="grid gap-5 xl:grid-cols-[1fr,360px]">
          <SectionCard title="Commercial metadata">
            <InfoGrid
              cols={2}
              items={[
                { label: 'Category', value: rfq.category },
                { label: 'Department', value: rfq.department },
                { label: 'Deadline', value: rfq.deadlineLabel },
                { label: 'Evaluation', value: rfq.evaluationMethod },
                { label: 'Payment terms', value: rfq.paymentTerms },
                { label: 'Estimated value', value: rfq.estValue },
              ]}
            />
          </SectionCard>
          <SectionCard title="Description">
            <p className="text-sm leading-6 text-slate-600">{rfq.description}</p>
          </SectionCard>
        </div>
      </WorkspaceCanvas>
      <SlideOver
        open={editOpen}
        onClose={() => setEditOpen(false)}
        title="Edit RFQ details"
        subtitle={rfq.id}
        width="md"
        footer={(
          <>
            <Button variant="ghost" onClick={() => setEditOpen(false)}>Cancel</Button>
            <Button
              onClick={() => {
                updateRfq(rfq.id, {
                  title: draftValues.title,
                  description: draftValues.description,
                  paymentTerms: draftValues.paymentTerms,
                });
                setEditOpen(false);
              }}
            >
              Save changes
            </Button>
          </>
        )}
      >
        <SlideOverSection title="Fields">
          <div className="space-y-3">
            <TextInput label="Title" value={draftValues.title} onChange={event => setDraftValues(current => ({ ...current, title: event.target.value }))} />
            <Textarea label="Description" value={draftValues.description} onChange={event => setDraftValues(current => ({ ...current, description: event.target.value }))} />
            <TextInput label="Payment terms" value={draftValues.paymentTerms} onChange={event => setDraftValues(current => ({ ...current, paymentTerms: event.target.value }))} />
          </div>
        </SlideOverSection>
      </SlideOver>
    </>
  );
}

function WorkspaceLineItemsPage() {
  const rfq = useWorkspaceRfq();
  const { updateRfqLineItems } = useAppStore();
  const [localItems, setLocalItems] = React.useState(() => (rfq?.lineItems ?? []).map(item => ({
    id: item.id,
    description: item.description,
    qty: item.qty,
    unit: item.unit,
    targetPrice: item.targetPrice,
    entryType: item.entryType,
    heading: item.heading,
    subheading: '',
  })));
  if (!rfq) return null;

  return (
    <WorkspaceCanvas segments={[{ label: 'RFQs', path: '/rfqs' }, { label: rfq.title, path: `/rfqs/${rfq.id}/overview` }, { label: 'Line Items', path: `/rfqs/${rfq.id}/line-items` }]}>
      <PageHeader title="Line items" subtitle={rfq.status === 'draft' ? 'Editable because the RFQ is still in draft' : 'Read-only operational view of target line items'} />
      {rfq.status === 'draft' ? (
        <>
          <LineItemEditor items={localItems} onChange={setLocalItems} />
          <div className="flex justify-end">
            <Button onClick={() => updateRfqLineItems(rfq.id, localItems.map(item => ({
              id: item.id,
              description: item.description,
              qty: item.qty,
              unit: item.unit,
              targetPrice: item.targetPrice,
              category: rfq.category,
              entryType: item.entryType,
              heading: item.heading,
            })))}>
              Save line items
            </Button>
          </div>
        </>
      ) : (
        <SectionCard title="Requested items" subtitle="Structured evaluation baseline">
          <div className="space-y-3">
            {rfq.lineItems.map(item => (
              <div key={item.id} className="rounded-lg border border-slate-200 px-4 py-3">
                <div className="flex items-center justify-between gap-3">
                  <div>
                    <p className="text-sm font-medium text-slate-800">{item.heading || item.description}</p>
                    {!item.heading && <p className="text-xs text-slate-500">{item.qty} {item.unit} · {item.category}</p>}
                  </div>
                  {!item.heading && <span className="text-sm font-semibold text-slate-700">${item.targetPrice.toLocaleString()}</span>}
                </div>
              </div>
            ))}
          </div>
        </SectionCard>
      )}
    </WorkspaceCanvas>
  );
}

function WorkspaceVendorsPage() {
  const rfq = useWorkspaceRfq();
  const { data, openVendorProfile } = useAppStore();
  const [inviteOpen, setInviteOpen] = React.useState(false);
  if (!rfq) return null;
  const vendors = data.vendors.filter(vendor => rfq.vendorIds.includes(vendor.id));

  return (
    <>
      <WorkspaceCanvas segments={[{ label: 'RFQs', path: '/rfqs' }, { label: rfq.title, path: `/rfqs/${rfq.id}/overview` }, { label: 'Vendors', path: `/rfqs/${rfq.id}/vendors` }]}>
        <PageHeader
          title="Invited vendors"
          subtitle="Roster, invitation state, and quick outreach actions"
          actions={(
            <>
              <Button variant="outline">Send reminder</Button>
              <Button onClick={() => setInviteOpen(true)}>Invite vendors</Button>
            </>
          )}
        />
        <div className="grid gap-4 md:grid-cols-2">
          {vendors.map(vendor => (
            <Card key={vendor.id}>
              <div className="flex items-start justify-between gap-3">
                <button onClick={() => openVendorProfile(vendor.id)} className="text-left">
                  <AvatarLabel name={vendor.name} subtitle={vendor.contact} />
                </button>
                <StatusBadge
                  status={vendor.inviteStatus === 'responded' ? 'approved' : vendor.inviteStatus === 'declined' ? 'rejected' : vendor.inviteStatus === 'invited' ? 'pending' : 'draft'}
                  label={vendor.inviteStatus}
                />
              </div>
              <div className="mt-4 grid grid-cols-3 gap-2 text-xs">
                <div className="rounded-md bg-slate-50 p-2">
                  <p className="text-slate-400">Risk</p>
                  <p className="mt-1 font-semibold text-slate-800">{vendor.risk}</p>
                </div>
                <div className="rounded-md bg-slate-50 p-2">
                  <p className="text-slate-400">Lead Time</p>
                  <p className="mt-1 font-semibold text-slate-800">{vendor.leadTime}</p>
                </div>
                <div className="rounded-md bg-slate-50 p-2">
                  <p className="text-slate-400">On-time</p>
                  <p className="mt-1 font-semibold text-slate-800">{vendor.onTimeRate}</p>
                </div>
              </div>
            </Card>
          ))}
        </div>
      </WorkspaceCanvas>
      <SlideOver open={inviteOpen} onClose={() => setInviteOpen(false)} title="Send invitations" subtitle="Mock vendor selection flow" width="md">
        <SlideOverSection title="Recommended vendors">
          <div className="space-y-2">
            {data.vendors.map(vendor => (
              <label key={vendor.id} className="flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-3">
                <Checkbox checked={rfq.vendorIds.includes(vendor.id)} readOnly />
                <div>
                  <p className="text-sm font-medium text-slate-800">{vendor.name}</p>
                  <p className="text-xs text-slate-500">{vendor.contact}</p>
                </div>
              </label>
            ))}
          </div>
        </SlideOverSection>
      </SlideOver>
    </>
  );
}

function QuoteIntakeListPage() {
  const navigate = useNavigate();
  const rfq = useWorkspaceRfq();
  const { data, addQuote, updateQuote } = useAppStore();
  const { getVendor } = useEntityLookups();
  const [statusFilter, setStatusFilter] = React.useState('');
  const [vendorFilter, setVendorFilter] = React.useState('');
  const [selectedIds, setSelectedIds] = React.useState<Array<string | number>>([]);
  const [uploadOpen, setUploadOpen] = React.useState(false);
  const [newFileName, setNewFileName] = React.useState('new_supplier_quote.pdf');
  const [newVendorId, setNewVendorId] = React.useState(rfq?.vendorIds[0] ?? '');
  if (!rfq) return null;

  const rows = data.quotes.filter(quote => quote.rfqId === rfq.id)
    .filter(quote => (!statusFilter || quote.status === statusFilter) && (!vendorFilter || quote.vendorId === vendorFilter));

  const acceptedCount = rows.filter(row => row.status === 'accepted').length;

  const columns: Array<ColumnDef<QuoteSubmissionRecord>> = [
    { key: 'fileName', label: 'File name', minWidth: '220px', render: row => <span className="text-sm font-medium text-slate-800">{row.fileName}</span> },
    { key: 'vendor', label: 'Vendor', minWidth: '180px', render: row => <span className="text-sm text-slate-600">{getVendor(row.vendorId)?.name}</span> },
    { key: 'status', label: 'Status', width: '130px', render: row => <StatusBadge status={row.status === 'processing' ? 'processing' : row.status === 'parsed' ? 'preview' : row.status === 'accepted' ? 'approved' : row.status === 'rejected' ? 'rejected' : 'error'} label={row.status} /> },
    { key: 'confidence', label: 'Parse confidence', width: '170px', render: row => <ConfidenceBadge variant={row.parseConfidence >= 85 ? 'high' : row.parseConfidence >= 65 ? 'medium' : 'low'} showBar percentage={row.parseConfidence} /> },
    { key: 'uploadedAt', label: 'Uploaded', width: '150px', render: row => <span className="text-sm text-slate-600">{row.uploadedAt}</span> },
  ];

  return (
    <>
      <WorkspaceCanvas segments={[{ label: 'RFQs', path: '/rfqs' }, { label: rfq.title, path: `/rfqs/${rfq.id}/overview` }, { label: 'Quote Intake', path: `/rfqs/${rfq.id}/intake` }]}>
        <PageHeader title="Quote intake" subtitle="Triage and parse vendor submissions for this RFQ" actions={<Button onClick={() => setUploadOpen(true)} icon={<Plus size={14} />}>Upload quote</Button>} />
        {acceptedCount === rows.length && rows.length > 0 && (
          <Banner variant="success">All submissions are accepted. Normalization is ready for comparison.</Banner>
        )}
        <Card>
          <UploadZone compact onBrowse={() => setUploadOpen(true)} />
        </Card>
        <FilterBar
          filters={[
            {
              key: 'status',
              label: 'Status',
              options: [{ value: '', label: 'All statuses' }, { value: 'processing', label: 'Processing' }, { value: 'parsed', label: 'Parsed' }, { value: 'accepted', label: 'Accepted' }, { value: 'rejected', label: 'Rejected' }, { value: 'error', label: 'Error' }],
              value: statusFilter,
              onChange: setStatusFilter,
            },
            {
              key: 'vendor',
              label: 'Vendor',
              options: [{ value: '', label: 'All vendors' }, ...rfq.vendorIds.map(vendorId => ({ value: vendorId, label: getVendor(vendorId)?.name ?? vendorId }))],
              value: vendorFilter,
              onChange: setVendorFilter,
            },
          ]}
        />
        <DataTable
          columns={columns}
          rows={rows}
          selectable
          selectedIds={selectedIds}
          onSelectChange={setSelectedIds}
          bulkActions={[
            { label: 'Accept selected', onClick: ids => ids.forEach(id => updateQuote(String(id), { status: 'accepted' })) },
            { label: 'Reject selected', onClick: ids => ids.forEach(id => updateQuote(String(id), { status: 'rejected' })) },
          ]}
          onRowClick={row => navigate(`/rfqs/${rfq.id}/intake/${row.id}`)}
          emptyState={<EmptyState title="No submissions yet" description="Upload the first vendor quote to start parse and normalization workflow." icon={<Mail size={20} />} action={<Button onClick={() => setUploadOpen(true)}>Upload quote</Button>} />}
        />
      </WorkspaceCanvas>
      <SlideOver
        open={uploadOpen}
        onClose={() => setUploadOpen(false)}
        title="Upload & parse quote"
        subtitle={rfq.id}
        width="md"
        footer={(
          <>
            <Button variant="ghost" onClick={() => setUploadOpen(false)}>Cancel</Button>
            <Button onClick={() => {
              addQuote(rfq.id, newVendorId, newFileName);
              setUploadOpen(false);
            }}>Upload</Button>
          </>
        )}
      >
        <SlideOverSection title="Submission">
          <div className="space-y-3">
            <SelectInput label="Vendor" value={newVendorId} onChange={event => setNewVendorId(event.target.value)} options={rfq.vendorIds.map(vendorId => ({ value: vendorId, label: getVendor(vendorId)?.name ?? vendorId }))} />
            <TextInput label="File name" value={newFileName} onChange={event => setNewFileName(event.target.value)} />
          </div>
        </SlideOverSection>
      </SlideOver>
    </>
  );
}

function QuoteIntakeDetailPage() {
  const navigate = useNavigate();
  const { quoteId = '', rfqId = '' } = useParams();
  const { updateQuote, updateQuoteLine } = useAppStore();
  const { getQuote, getRfq, getVendor } = useEntityLookups();
  const [activeTab, setActiveTab] = React.useState('overview');
  const [overrideLineId, setOverrideLineId] = React.useState<string | null>(null);
  const [replaceOpen, setReplaceOpen] = React.useState(false);
  const [reparseOpen, setReparseOpen] = React.useState(false);
  const quote = getQuote(quoteId);
  const rfq = getRfq(rfqId);

  if (!quote) {
    return null;
  }

  const vendor = getVendor(quote.vendorId);

  return (
    <>
      <WorkspaceCanvas segments={[{ label: 'RFQs', path: '/rfqs' }, { label: rfq?.title ?? rfqId, path: `/rfqs/${rfqId}/overview` }, { label: 'Quote Intake', path: `/rfqs/${rfqId}/intake` }, { label: vendor?.name ?? quote.fileName, path: `/rfqs/${rfqId}/intake/${quote.id}` }]}>
        <RecordHeader
          title={quote.fileName}
          status={quote.status === 'processing' ? 'processing' : quote.status === 'parsed' ? 'preview' : quote.status === 'accepted' ? 'approved' : quote.status === 'rejected' ? 'rejected' : 'error'}
          metadata={[
            { label: 'Vendor', value: vendor?.name ?? 'Unknown vendor' },
            { label: 'Uploaded', value: quote.uploadedAt },
            { label: 'Parse confidence', value: `${quote.parseConfidence}%` },
            { label: 'Normalization', value: quote.normalizedLocked ? 'Locked' : quote.normalizedStale ? 'Stale' : 'Editable' },
          ]}
        />

        <SecondaryTabs
          tabs={[
            { id: 'overview', label: 'Overview' },
            { id: 'parsed-line-items', label: 'Parsed Line Items', count: quote.lineItems.length },
          ]}
          activeTab={activeTab}
          onChange={setActiveTab}
        />

        {activeTab === 'overview' ? (
          <div className="grid gap-5 xl:grid-cols-[1.15fr,0.85fr]">
            <SectionCard title="Document preview" noPadBody>
              <DocPreview fileName={quote.fileName} pageInfo={quote.previewPageInfo} className="m-4 h-[360px]" />
            </SectionCard>
            <div className="space-y-5">
              <SectionCard title="Parse summary">
                <div className="space-y-3">
                  <AvatarLabel name={vendor?.name ?? 'Unknown vendor'} subtitle={vendor?.contact} />
                  <ConfidenceBadge variant={quote.parseConfidence >= 85 ? 'high' : quote.parseConfidence >= 65 ? 'medium' : 'low'} showBar percentage={quote.parseConfidence} />
                  <ValidationCallout issues={quote.validationIssues} variant={quote.status === 'error' ? 'error' : 'warning'} />
                  {quote.rejectionReason && <Alert variant="error" title="Rejected reason">{quote.rejectionReason}</Alert>}
                </div>
              </SectionCard>
              <SectionCard title="Notes">
                <div className="space-y-2">
                  {quote.overviewNotes.map(note => (
                    <div key={note} className="rounded-md bg-slate-50 px-3 py-2 text-sm text-slate-600">{note}</div>
                  ))}
                </div>
              </SectionCard>
            </div>
          </div>
        ) : (
          <SectionCard title="Extracted line items" subtitle="Per-line mappings, overrides, and revert controls">
            <div className="space-y-3">
              {quote.lineItems.length === 0 && <EmptyState title="No parsed lines yet" description="Parsing is still running or the submission was rejected before extraction." />}
              {quote.lineItems.map(line => (
                <Card key={line.id} className="border-slate-200">
                  <div className="flex items-start justify-between gap-4">
                    <div>
                      <p className="text-sm font-medium text-slate-800">{line.description}</p>
                      <p className="mt-1 text-xs text-slate-500">{line.total} · mapped to {line.mappedLineLabel}</p>
                      <div className="mt-2 flex items-center gap-2">
                        <ConfidenceBadge variant={line.confidence} />
                        {line.overrideReason && <OverrideChip />}
                        {line.conflict && <StatusBadge status="pending" label="Conflict" size="xs" />}
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <Button variant="outline" size="sm" onClick={() => setOverrideLineId(line.id)}>Override</Button>
                      {line.overrideReason && <RevertControl onClick={() => updateQuoteLine(quote.id, line.id, { overrideReason: undefined })} />}
                    </div>
                  </div>
                </Card>
              ))}
            </div>
          </SectionCard>
        )}
      </WorkspaceCanvas>

      <QuoteDetailActionBar
        onReject={() => updateQuote(quote.id, { status: 'rejected', rejectionReason: 'Rejected from intake review.' })}
        onAccept={() => updateQuote(quote.id, { status: 'accepted' })}
        onAcceptNormalize={() => {
          updateQuote(quote.id, { status: 'accepted' });
          navigate(`/rfqs/${rfqId}/intake/${quote.id}/normalize`);
        }}
        onReplace={() => setReplaceOpen(true)}
        onReparse={() => setReparseOpen(true)}
      />

      <SlideOver
        open={Boolean(overrideLineId)}
        onClose={() => setOverrideLineId(null)}
        title="Manual override"
        subtitle="Map parsed line to a target RFQ line"
        width="sm"
        footer={(
          <>
            <Button variant="ghost" onClick={() => setOverrideLineId(null)}>Cancel</Button>
            <Button onClick={() => {
              if (overrideLineId) {
                updateQuoteLine(quote.id, overrideLineId, { overrideReason: 'Buyer override for mapping precision.' });
              }
              setOverrideLineId(null);
            }}>Apply override</Button>
          </>
        )}
      >
        <SlideOverSection>
          <p className="text-sm text-slate-600">The override flow persists a buyer reason and surfaces the info chip in the parsed line table.</p>
        </SlideOverSection>
      </SlideOver>

      <SlideOver open={replaceOpen} onClose={() => setReplaceOpen(false)} title="Replace & re-parse" subtitle="Upload a replacement file" width="sm">
        <SlideOverSection>
          <UploadZone />
        </SlideOverSection>
      </SlideOver>

      <SlideOver open={reparseOpen} onClose={() => setReparseOpen(false)} title="Confirm re-parse" subtitle="Re-run extraction for this document" width="sm" footer={<Button onClick={() => setReparseOpen(false)}>Done</Button>}>
        <SlideOverSection>
          <p className="text-sm text-slate-600">In the shell, this keeps the user flow visible without hitting a parser backend.</p>
        </SlideOverSection>
      </SlideOver>
    </>
  );
}

function NormalizationPage() {
  const { rfqId = '', quoteId = '' } = useParams();
  const { updateQuote } = useAppStore();
  const { getQuote, getRfq, getVendor } = useEntityLookups();
  const [selectedIds, setSelectedIds] = React.useState<string[]>([]);
  const [bulkOpen, setBulkOpen] = React.useState(false);
  const [conflictOpen, setConflictOpen] = React.useState(false);
  const quote = getQuote(quoteId);
  const rfq = getRfq(rfqId);

  if (!quote) return null;

  return (
    <>
      <WorkspaceCanvas segments={[{ label: 'RFQs', path: '/rfqs' }, { label: rfq?.title ?? rfqId, path: `/rfqs/${rfqId}/overview` }, { label: 'Quote Intake', path: `/rfqs/${rfqId}/intake` }, { label: getVendor(quote.vendorId)?.name ?? quote.fileName, path: `/rfqs/${rfqId}/intake/${quote.id}` }, { label: 'Normalize', path: `/rfqs/${rfqId}/intake/${quote.id}/normalize` }]}>
        <RecordHeader
          title={`Normalize ${quote.fileName}`}
          status={quote.normalizedLocked ? 'locked' : quote.normalizedStale ? 'stale' : 'active'}
          metadata={[
            { label: 'Vendor', value: getVendor(quote.vendorId)?.name ?? 'Unknown vendor' },
            { label: 'Accepted lines', value: quote.lineItems.length },
            { label: 'State', value: quote.normalizedLocked ? 'Locked for comparison' : quote.normalizedStale ? 'Stale review required' : 'Editable' },
            { label: 'Conflicts', value: quote.lineItems.filter(line => line.conflict).length },
          ]}
        />
        <NormalizationLockBar
          locked={quote.normalizedLocked}
          progressLabel={`Mapped ${quote.lineItems.length - quote.lineItems.filter(line => line.conflict).length}/${quote.lineItems.length} lines`}
          onBulkApply={() => setBulkOpen(true)}
          onLock={() => updateQuote(quote.id, { normalizedLocked: true, normalizedStale: false })}
          onUnlock={() => updateQuote(quote.id, { normalizedLocked: false, normalizedStale: true })}
        />
        {quote.normalizedStale && <Banner variant="warning">This quote became stale after the source document changed. Review conflicts before the next final run.</Banner>}
        <MappingGrid
          rows={quote.lineItems.map((line, index) => ({
            id: line.id,
            lineNumber: index + 1,
            vendorDescription: line.description,
            confidence: line.confidence,
            mappedLine: line.mappedLineLabel,
            taxonomyCode: `TX-${index + 101}`,
            normalizedQty: line.normalizedQty,
            normalizedUnit: line.normalizedUnit,
            normalizedPrice: line.normalizedPrice,
            currency: line.currency,
            conflict: line.conflict,
            overridden: Boolean(line.overrideReason),
          }))}
          selectedIds={selectedIds}
          onSelectedIdsChange={setSelectedIds}
        />
        {quote.lineItems.some(line => line.conflict) && (
          <div className="flex justify-end">
            <Button variant="outline" onClick={() => setConflictOpen(true)} icon={<CircleAlert size={14} />}>Resolve line mapping conflict</Button>
          </div>
        )}
      </WorkspaceCanvas>
      <SlideOver open={bulkOpen} onClose={() => setBulkOpen(false)} title="Bulk mapping" subtitle={`${selectedIds.length} selected rows`} width="sm">
        <SlideOverSection>
          <p className="text-sm text-slate-600">Bulk mapping is stubbed with mock data but the selection workflow and lock-state behavior match the blueprint.</p>
        </SlideOverSection>
      </SlideOver>
      <SlideOver open={conflictOpen} onClose={() => setConflictOpen(false)} title="Resolve line mapping conflict" subtitle="Review ambiguous mappings" width="md">
        <SlideOverSection title="Flagged rows">
          <div className="space-y-2">
            {quote.lineItems.filter(line => line.conflict).map(line => (
              <div key={line.id} className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                <p className="text-sm font-medium text-amber-900">{line.description}</p>
                <p className="mt-1 text-xs text-amber-800">Currently mapped to {line.mappedLineLabel}</p>
              </div>
            ))}
          </div>
        </SlideOverSection>
      </SlideOver>
    </>
  );
}

function ComparisonRunsListPage() {
  const navigate = useNavigate();
  const rfq = useWorkspaceRfq();
  const { data, createComparisonRun } = useAppStore();
  const [runOpen, setRunOpen] = React.useState(false);
  const [mode, setMode] = React.useState<'preview' | 'final'>('preview');
  const [model, setModel] = React.useState('Balanced Procurement v3.2');
  if (!rfq) return null;
  const rows = data.comparisonRuns.filter(run => run.rfqId === rfq.id);

  const columns: Array<ColumnDef<AppSeed['comparisonRuns'][number]>> = [
    { key: 'id', label: 'Run ID', width: '120px', render: row => <span className="font-mono text-xs text-slate-500">{row.id}</span> },
    { key: 'createdAt', label: 'Date', width: '160px' },
    { key: 'type', label: 'Type', width: '120px', render: row => <StatusBadge status={row.type === 'preview' ? 'preview' : 'final'} label={row.type} /> },
    { key: 'status', label: 'Status', width: '120px', render: row => <StatusBadge status={row.status === 'locked' ? 'locked' : row.status === 'stale' ? 'stale' : 'generated'} label={row.status} /> },
    { key: 'scoringModel', label: 'Scoring Model', minWidth: '220px', render: row => <VersionChip version={row.scoringModel} /> },
    { key: 'createdBy', label: 'Created By', width: '150px' },
  ];

  return (
    <>
      <WorkspaceCanvas segments={[{ label: 'RFQs', path: '/rfqs' }, { label: rfq.title, path: `/rfqs/${rfq.id}/overview` }, { label: 'Comparison Runs', path: `/rfqs/${rfq.id}/runs` }]}>
        <PageHeader title="Comparison runs" subtitle="Preview and final evaluations for this RFQ" actions={<Button onClick={() => setRunOpen(true)} icon={<GitCompareArrows size={14} />}>New comparison run</Button>} />
        <DataTable
          columns={columns}
          rows={rows}
          onRowClick={row => navigate(`/rfqs/${rfq.id}/runs/${row.id}`)}
          rowClassName={row => row.id === rows[0]?.id ? 'ring-1 ring-indigo-200' : ''}
          emptyState={<EmptyState title="No runs yet" description="Generate the first preview or final run from this RFQ." action={<Button onClick={() => setRunOpen(true)}>New comparison run</Button>} />}
        />
      </WorkspaceCanvas>
      <SlideOver
        open={runOpen}
        onClose={() => setRunOpen(false)}
        title="Run comparison engine"
        subtitle="Generate preview or final matrix"
        width="sm"
        footer={(
          <>
            <Button variant="ghost" onClick={() => setRunOpen(false)}>Cancel</Button>
            <Button onClick={() => {
              const newRunId = createComparisonRun(rfq.id, mode, model);
              setRunOpen(false);
              navigate(`/rfqs/${rfq.id}/runs/${newRunId}`);
            }}>Generate run</Button>
          </>
        )}
      >
        <SlideOverSection title="Mode">
          <div className="space-y-3">
            <ToggleSwitch checked={mode === 'final'} onChange={checked => setMode(checked ? 'final' : 'preview')} label={mode === 'final' ? 'Final run' : 'Preview run'} />
            <SelectInput label="Scoring model" value={model} onChange={event => setModel(event.target.value)} options={data.settings.scoringPolicies.map(policy => ({ value: policy.name, label: `${policy.name} ${policy.version}` }))} />
          </div>
        </SlideOverSection>
      </SlideOver>
    </>
  );
}

function ComparisonMatrixPage() {
  const navigate = useNavigate();
  const { rfqId = '', runId = '' } = useParams();
  const { data, createComparisonRun, updateRun, updateApproval } = useAppStore();
  const { getRun, getRfq, getVendor } = useEntityLookups();
  const [modeView, setModeView] = React.useState<'normalized' | 'original'>('normalized');
  const [cellNote, setCellNote] = React.useState<string | null>(null);
  const [overrideOpen, setOverrideOpen] = React.useState(false);
  const [explainOpen, setExplainOpen] = React.useState(false);
  const run = getRun(runId);
  const rfq = getRfq(rfqId);

  if (!run || !rfq) return null;

  const groupedRows = Array.from(new Set(run.rows.map(row => row.category))).map(group => ({
    name: group,
    rows: run.rows.filter(row => row.category === group),
  }));

  return (
    <>
      <WorkspaceCanvas segments={[{ label: 'RFQs', path: '/rfqs' }, { label: rfq.title, path: `/rfqs/${rfq.id}/overview` }, { label: 'Comparison Runs', path: `/rfqs/${rfq.id}/runs` }, { label: `Run #${run.id}`, path: `/rfqs/${rfq.id}/runs/${run.id}` }]}>
        <RecordHeader
          title={`Comparison matrix ${run.id}`}
          status={run.status === 'locked' ? 'locked' : run.status === 'stale' ? 'stale' : run.type === 'preview' ? 'preview' : 'generated'}
          metadata={[
            { label: 'Mode', value: run.type },
            { label: 'Generated', value: run.createdAt },
            { label: 'Scoring', value: run.scoringModel },
            { label: 'Created by', value: run.createdBy },
          ]}
          actions={(
            <>
              <Button variant="outline" onClick={() => {
                const rerunId = createComparisonRun(rfq.id, run.type, run.scoringModel);
                navigate(`/rfqs/${rfq.id}/runs/${rerunId}`);
              }}>Run / Recalculate</Button>
              <Button variant="outline" onClick={() => updateRun(run.id, current => ({ ...current, status: current.status === 'locked' ? 'stale' : 'locked' }))}>
                {run.status === 'locked' ? 'Unlock Matrix' : 'Lock Matrix'}
              </Button>
            </>
          )}
        />

        <div className="flex items-center justify-between rounded-lg border border-slate-200 bg-white px-4 py-3">
          <div className="flex items-center gap-3">
            <span className="text-sm text-slate-600">View mode</span>
            <SecondaryTabs
              tabs={[
                { id: 'normalized', label: 'Normalized' },
                { id: 'original', label: 'Original' },
              ]}
              activeTab={modeView}
              onChange={id => setModeView(id as 'normalized' | 'original')}
            />
          </div>
          <div className="flex items-center gap-3">
            <SelectInput
              compact
              value={run.scoringModel}
              onChange={event => updateRun(run.id, current => ({ ...current, scoringModel: event.target.value, status: 'stale' }))}
              options={data.settings.scoringPolicies.map(policy => ({ value: policy.name, label: `${policy.name} ${policy.version}` }))}
            />
            <StatusBadge status={run.type === 'preview' ? 'preview' : run.status === 'locked' ? 'locked' : 'generated'} label={run.type === 'preview' ? 'Preview' : 'Final'} />
          </div>
        </div>

        <ApprovalGateBanner mode={run.approvalState} />
        <ReadinessBanner issues={run.readinessIssues} />

        <div className="grid gap-5 xl:grid-cols-[1.2fr,0.8fr]">
          <SectionCard title="Matrix" subtitle="Grouped line rows and vendor totals" noPadBody>
            <div className="overflow-x-auto">
              <table className="min-w-[900px] w-full border-collapse">
                <thead className="bg-slate-50">
                  <tr className="border-b border-slate-200">
                    <th className="sticky left-0 bg-slate-50 px-3 py-3 text-left text-xs font-medium text-slate-600">Line item</th>
                    {run.vendors.map(vendor => (
                      <th key={vendor.vendorId} className="px-3 py-3">
                        <VendorSummaryHeader vendor={getVendor(vendor.vendorId)?.name ?? vendor.vendorId} total={vendor.total} rank={vendor.rank} />
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {groupedRows.map(group => (
                    <React.Fragment key={group.name}>
                      <tr className="border-y border-slate-200 bg-slate-50/70">
                        <td colSpan={run.vendors.length + 1} className="px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{group.name}</td>
                      </tr>
                      {group.rows.map(row => (
                        <tr key={row.id} className="border-b border-slate-100">
                          <td className="sticky left-0 bg-white px-3 py-3 text-sm font-medium text-slate-700">{row.lineItem}</td>
                          {row.values.map((value, index) => (
                            <td key={`${row.id}-${index}`} className={`px-3 py-3 text-right ${index === row.bestVendorIndex ? 'bg-green-50/50' : ''}`}>
                              <button className="inline-flex items-center gap-2 text-right" onClick={() => setCellNote(`${row.lineItem} · ${getVendor(run.vendors[index]?.vendorId)?.name ?? 'Vendor'}`)}>
                                {value ? <span className="text-sm font-medium text-slate-800">{value}</span> : <MissingValuePlaceholder />}
                                {value && <DeltaBadge value="-2.1%" best={index === row.bestVendorIndex} />}
                              </button>
                            </td>
                          ))}
                        </tr>
                      ))}
                    </React.Fragment>
                  ))}
                </tbody>
              </table>
            </div>
          </SectionCard>

          <div className="space-y-5">
            <RecommendationCard
              vendor={getVendor(run.recommendation.vendorId)?.name ?? run.recommendation.vendorId}
              confidence={run.recommendation.confidence}
              factors={run.recommendation.factors}
              onOverride={() => setOverrideOpen(true)}
            />
            <div className="flex items-center justify-end">
              <Button variant="outline" onClick={() => setExplainOpen(true)}>View full recommendation</Button>
            </div>
            <SectionCard title="Terms comparison">
              <div className="space-y-3">
                {run.termsComparison.map(term => (
                  <div key={term.label} className="rounded-lg border border-slate-200 px-4 py-3">
                    <p className="text-xs uppercase tracking-wide text-slate-400">{term.label}</p>
                    <div className="mt-2 grid grid-cols-2 gap-2 text-sm text-slate-700">
                      {term.values.map((value, index) => (
                        <span key={`${term.label}-${index}`}>{getVendor(run.vendors[index]?.vendorId)?.name}: {value}</span>
                      ))}
                    </div>
                  </div>
                ))}
              </div>
            </SectionCard>
            {run.linkedApprovalId && (
              <Button variant="ghost" onClick={() => navigate(`/rfqs/${rfq.id}/approvals/${run.linkedApprovalId}`)}>View in Approval Queue</Button>
            )}
          </div>
        </div>
      </WorkspaceCanvas>

      <SlideOver open={Boolean(cellNote)} onClose={() => setCellNote(null)} title="Line detail note" subtitle={modeView === 'normalized' ? 'Normalized evidence' : 'Original submitted value'} width="md">
        <SlideOverSection>
          <p className="text-sm text-slate-600">{cellNote}</p>
          <p className="mt-3 text-sm text-slate-500">This slide-over is where extraction evidence, normalized conversions, and buyer notes would surface from the live API.</p>
        </SlideOverSection>
      </SlideOver>

      <SlideOver
        open={overrideOpen}
        onClose={() => setOverrideOpen(false)}
        title="Override recommendation"
        subtitle="Mandatory reason triggers approval gate"
        width="sm"
        footer={(
          <>
            <Button variant="ghost" onClick={() => setOverrideOpen(false)}>Cancel</Button>
            <Button
              onClick={() => {
                if (run.linkedApprovalId) {
                  updateApproval(run.linkedApprovalId, { status: 'pending' });
                }
                setOverrideOpen(false);
              }}
            >
              Submit override
            </Button>
          </>
        )}
      >
        <SlideOverSection title="Reason">
          <Textarea placeholder="Document why the recommendation needs to change." />
        </SlideOverSection>
      </SlideOver>

      <SlideOver open={explainOpen} onClose={() => setExplainOpen(false)} title="Recommendation & explainability" subtitle="MCDA factor breakdown" width="lg">
        <SlideOverSection title="Top factors">
          <Checklist items={run.recommendation.factors.map(factor => ({ label: factor, status: 'pass' as const }))} />
        </SlideOverSection>
      </SlideOver>
    </>
  );
}

function ApprovalQueuePage({ workspaceScoped = false }: { workspaceScoped?: boolean }) {
  const navigate = useNavigate();
  const { rfqId } = useParams();
  const { data, updateApproval } = useAppStore();
  const [selectedIds, setSelectedIds] = React.useState<Array<string | number>>([]);
  const [typeFilter, setTypeFilter] = React.useState('');
  const [assigneeFilter, setAssigneeFilter] = React.useState('');
  const [actionOpen, setActionOpen] = React.useState(false);

  const rows = data.approvals.filter(approval => (!rfqId || approval.rfqId === rfqId) && (!typeFilter || approval.type === typeFilter) && (!assigneeFilter || approval.assignee === assigneeFilter));

  const columns: Array<ColumnDef<ApprovalRecord>> = [
    {
      key: 'rfq',
      label: 'RFQ',
      minWidth: '180px',
      render: row => (
        <button className="text-left text-sm font-medium text-indigo-700 hover:underline" onClick={event => {
          event.stopPropagation();
          navigate(`/rfqs/${row.rfqId}/overview`);
        }}>
          {row.rfqId}
        </button>
      ),
    },
    { key: 'type', label: 'Type', minWidth: '180px', render: row => <span className="text-sm text-slate-700">{row.type}</span> },
    { key: 'summary', label: 'Summary', minWidth: '260px', render: row => <span className="text-sm text-slate-600">{row.summary}</span> },
    { key: 'priority', label: 'Priority', width: '120px', render: row => <PriorityMarker priority={row.priority} /> },
    { key: 'sla', label: 'SLA', width: '120px', render: row => <SLATimerBadge variant={row.sla.variant} value={row.sla.value} /> },
    { key: 'assignee', label: 'Assignee', width: '160px', render: row => <span className="text-sm text-slate-700">{row.assignee}</span> },
  ];

  const content = (
    <div className="space-y-5">
      <PageHeader title={workspaceScoped ? 'Approvals' : 'Approval queue'} subtitle={workspaceScoped ? 'RFQ-scoped approvals in workspace context' : 'Cross-RFQ gated decisions with SLA indicators'} />
      {rows.some(row => row.sla.variant !== 'safe' && row.status === 'pending') && (
        <Banner variant="warning">Urgent approvals are approaching SLA breach and are highlighted for action.</Banner>
      )}
      <FilterBar
        filters={[
          {
            key: 'type',
            label: 'Type',
            options: [{ value: '', label: 'All types' }, ...Array.from(new Set(data.approvals.map(item => item.type))).map(type => ({ value: type, label: type }))],
            value: typeFilter,
            onChange: setTypeFilter,
          },
          {
            key: 'assignee',
            label: 'Assignee',
            options: [{ value: '', label: 'All assignees' }, ...Array.from(new Set(data.approvals.map(item => item.assignee))).map(assignee => ({ value: assignee, label: assignee }))],
            value: assigneeFilter,
            onChange: setAssigneeFilter,
          },
        ]}
      />
      <DataTable
        columns={columns}
        rows={rows}
        selectable
        selectedIds={selectedIds}
        onSelectChange={setSelectedIds}
        bulkActions={[
          { label: 'Bulk approve', onClick: ids => ids.forEach(id => updateApproval(String(id), { status: 'approved' })) },
          { label: 'Bulk reject', onClick: ids => ids.forEach(id => updateApproval(String(id), { status: 'rejected' })) },
          { label: 'Bulk reassign', onClick: () => setActionOpen(true) },
        ]}
        onRowClick={row => navigate(`/rfqs/${row.rfqId}/approvals/${row.id}`)}
        expandable
        renderExpanded={row => (
          <div className="grid gap-4 bg-slate-50 p-4 md:grid-cols-[1fr,240px,240px]">
            <div>
              <p className="text-xs uppercase tracking-wide text-slate-400">Gate reasons</p>
              <ul className="mt-2 space-y-1">
                {row.gateReasons.map(reason => <li key={reason} className="text-sm text-slate-600">• {reason}</li>)}
              </ul>
            </div>
            <AssignmentControl value={row.assignee} onChange={value => updateApproval(row.id, { assignee: value })} options={data.users.map(user => ({ value: user.name, label: user.name }))} />
            <SnoozeControl />
          </div>
        )}
      />
    </div>
  );

  if (workspaceScoped) {
    const title = data.rfqs.find(item => item.id === rfqId)?.title ?? rfqId ?? 'Approvals';
    return (
      <>
        <WorkspaceCanvas segments={[{ label: 'RFQs', path: '/rfqs' }, { label: title, path: `/rfqs/${rfqId}/overview` }, { label: 'Approvals', path: `/rfqs/${rfqId}/approvals` }]}>
          {content}
        </WorkspaceCanvas>
        <ReassignSlideOver open={actionOpen} onClose={() => setActionOpen(false)} />
      </>
    );
  }

  return (
    <>
      {content}
      <ReassignSlideOver open={actionOpen} onClose={() => setActionOpen(false)} />
    </>
  );
}

function ReassignSlideOver({ open, onClose }: { open: boolean; onClose: () => void }) {
  const { data } = useAppStore();
  const [assignee, setAssignee] = React.useState(data.users[0]?.name ?? '');
  return (
    <SlideOver open={open} onClose={onClose} title="Reassign approval" subtitle="Delegation-aware mock workflow" width="sm">
      <SlideOverSection>
        <SelectInput label="Assignee" value={assignee} onChange={event => setAssignee(event.target.value)} options={data.users.map(user => ({ value: user.name, label: `${user.name} · ${user.role}` }))} />
      </SlideOverSection>
    </SlideOver>
  );
}

function ApprovalDetailPage() {
  const navigate = useNavigate();
  const { approvalId = '', rfqId = '' } = useParams();
  const { decideApproval, updateApproval } = useAppStore();
  const { getApproval, getRun, getRfq } = useEntityLookups();
  const [reason, setReason] = React.useState('');
  const [activeEvidenceTab, setActiveEvidenceTab] = React.useState('documents');
  const [confirmMode, setConfirmMode] = React.useState<'approved' | 'rejected' | 'returned' | null>(null);
  const approval = getApproval(approvalId);
  const rfq = getRfq(rfqId);
  const linkedRun = getRun(approval?.linkedRunId);

  if (!approval || !rfq) return null;

  const evidenceTabs = [
    { id: 'documents', label: 'Documents', count: approval.evidenceTabs.documents.length },
    { id: 'screening', label: 'Screening Results', count: approval.evidenceTabs.screening.length },
    { id: 'audit', label: 'Audit Trail', count: approval.evidenceTabs.audit.length },
    { id: 'comparison', label: 'Comparison Summary', count: approval.evidenceTabs.comparison.length },
  ];

  const tabContent = approval.evidenceTabs[activeEvidenceTab as keyof ApprovalRecord['evidenceTabs']];

  return (
    <>
      <WorkspaceCanvas segments={[{ label: 'RFQs', path: '/rfqs' }, { label: rfq.title, path: `/rfqs/${rfq.id}/overview` }, { label: 'Approvals', path: `/rfqs/${rfq.id}/approvals` }, { label: approval.id, path: `/rfqs/${rfq.id}/approvals/${approval.id}` }]}>
        <RecordHeader
          title={approval.id}
          status={approval.status === 'pending' ? 'pending' : approval.status === 'approved' ? 'approved' : approval.status === 'rejected' ? 'rejected' : approval.status === 'returned' ? 'due' : 'stale'}
          metadata={[
            { label: 'Type', value: approval.type },
            { label: 'Assignee', value: approval.assignee },
            { label: 'SLA', value: <SLATimerBadge variant={approval.sla.variant} value={approval.sla.value} /> },
            { label: 'Created', value: approval.createdAt },
          ]}
          actions={<Button variant="outline" onClick={() => updateApproval(approval.id, { status: 'awaiting-evidence' })}>Request More Evidence</Button>}
        />
        <div className="grid gap-5 xl:grid-cols-[1.15fr,0.85fr]">
          <div className="space-y-5">
            <SectionCard title="Recommended outcome">
              <p className="text-sm text-slate-600">{approval.summary}</p>
              {linkedRun && (
                <div className="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                  <p className="text-xs uppercase tracking-wide text-slate-400">Linked run</p>
                  <p className="mt-1 text-sm font-medium text-slate-800">{linkedRun.id} · {linkedRun.scoringModel}</p>
                  <Button variant="ghost" size="sm" className="mt-3" onClick={() => navigate(`/rfqs/${rfq.id}/runs/${linkedRun.id}`)}>Open comparison matrix</Button>
                </div>
              )}
            </SectionCard>

            <EvidenceTabsPanel tabs={evidenceTabs} activeTab={activeEvidenceTab} onChange={setActiveEvidenceTab}>
              <div className="space-y-2">
                {tabContent.map(item => (
                  <div key={item} className="rounded-md border border-slate-200 px-3 py-2 text-sm text-slate-600">{item}</div>
                ))}
              </div>
            </EvidenceTabsPanel>

            <SectionCard title="Prior approval history">
              <div className="space-y-3">
                {approval.history.map(entry => (
                  <div key={entry.id} className="flex gap-3">
                    <div className="mt-1 h-2 w-2 rounded-full bg-indigo-500" />
                    <div>
                      <p className="text-sm font-medium text-slate-800">{entry.type}</p>
                      <p className="text-xs text-slate-500">{entry.actor} · {entry.timestamp}</p>
                      <p className="mt-1 text-sm text-slate-600">{entry.detail}</p>
                    </div>
                  </div>
                ))}
              </div>
            </SectionCard>
          </div>

          <div className="space-y-5">
            <SectionCard title="Gate reasons">
              <Checklist items={approval.gateReasons.map(reason => ({ label: reason, status: 'warning' as const }))} />
            </SectionCard>
            <DecisionPanel
              reason={reason}
              onReasonChange={setReason}
              onApprove={() => setConfirmMode('approved')}
              onReject={() => setConfirmMode('rejected')}
              onReturn={() => setConfirmMode('returned')}
            />
          </div>
        </div>
      </WorkspaceCanvas>
      <SlideOver
        open={Boolean(confirmMode)}
        onClose={() => setConfirmMode(null)}
        title={confirmMode === 'approved' ? 'Confirm decision' : confirmMode === 'rejected' ? 'Reject approval' : 'Return for revision'}
        subtitle={approval.id}
        width="sm"
        footer={(
          <>
            <Button variant="ghost" onClick={() => setConfirmMode(null)}>Cancel</Button>
            <Button
              onClick={() => {
                if (!confirmMode) return;
                decideApproval(approval.id, confirmMode, reason || 'Decision recorded in UI shell.');
                setConfirmMode(null);
                navigate(confirmMode === 'approved' && approval.routeAfterApproval === 'award'
                  ? `/rfqs/${rfq.id}/award`
                  : confirmMode === 'approved'
                    ? `/rfqs/${rfq.id}/negotiations`
                    : `/rfqs/${rfq.id}/approvals`);
              }}
            >
              Confirm
            </Button>
          </>
        )}
      >
        <SlideOverSection title="Decision rationale">
          <Textarea value={reason} onChange={event => setReason(event.target.value)} placeholder="Document the decision reason..." />
        </SlideOverSection>
      </SlideOver>
    </>
  );
}

function NegotiationsListPage() {
  const navigate = useNavigate();
  const rfq = useWorkspaceRfq();
  const { data, createNegotiation } = useAppStore();
  const { getVendor } = useEntityLookups();
  const [launchOpen, setLaunchOpen] = React.useState(false);
  const [vendorId, setVendorId] = React.useState(rfq?.vendorIds[0] ?? '');
  if (!rfq) return null;
  const rows = data.negotiations.filter(item => item.rfqId === rfq.id);

  return (
    <>
      <WorkspaceCanvas segments={[{ label: 'RFQs', path: '/rfqs' }, { label: rfq.title, path: `/rfqs/${rfq.id}/overview` }, { label: 'Negotiations', path: `/rfqs/${rfq.id}/negotiations` }]}>
        <PageHeader title="Negotiations" subtitle="Round-based commercial discussions and BAFO controls" actions={<Button onClick={() => setLaunchOpen(true)}>Start negotiation</Button>} />
        <SectionCard title="Active negotiations">
          <div className="space-y-3">
            {rows.length === 0 && <EmptyState title="No negotiations launched" description="Create a negotiation to capture counter-offers and BAFO." />}
            {rows.map(item => (
              <Card key={item.id} hover onClick={() => navigate(`/rfqs/${rfq.id}/negotiations/${item.id}`)}>
                <div className="flex items-center justify-between gap-3">
                  <div>
                    <p className="text-sm font-semibold text-slate-800">{getVendor(item.vendorId)?.name}</p>
                    <p className="text-xs text-slate-500">{item.strategy} · BAFO {item.bafoDue}</p>
                  </div>
                  <StatusBadge status={item.status === 'active' ? 'active' : item.status === 'closed' ? 'closed' : 'pending'} label={item.status} />
                </div>
              </Card>
            ))}
          </div>
        </SectionCard>
      </WorkspaceCanvas>
      <SlideOver
        open={launchOpen}
        onClose={() => setLaunchOpen(false)}
        title="Launch negotiation"
        subtitle={rfq.id}
        width="sm"
        footer={(
          <>
            <Button variant="ghost" onClick={() => setLaunchOpen(false)}>Cancel</Button>
            <Button onClick={() => {
              const id = createNegotiation(rfq.id, vendorId);
              setLaunchOpen(false);
              navigate(`/rfqs/${rfq.id}/negotiations/${id}`);
            }}>Launch</Button>
          </>
        )}
      >
        <SlideOverSection>
          <SelectInput label="Vendor" value={vendorId} onChange={event => setVendorId(event.target.value)} options={rfq.vendorIds.map(id => ({ value: id, label: getVendor(id)?.name ?? id }))} />
        </SlideOverSection>
      </SlideOver>
    </>
  );
}

function NegotiationDetailPage() {
  const navigate = useNavigate();
  const { rfqId = '', negotiationId = '' } = useParams();
  const { addNegotiationRound } = useAppStore();
  const { getNegotiation, getRfq, getVendor } = useEntityLookups();
  const [note, setNote] = React.useState('');
  const [value, setValue] = React.useState('$1.09M');
  const negotiation = getNegotiation(negotiationId);
  const rfq = getRfq(rfqId);
  if (!negotiation) return null;

  return (
    <WorkspaceCanvas segments={[{ label: 'RFQs', path: '/rfqs' }, { label: rfq?.title ?? rfqId, path: `/rfqs/${rfqId}/overview` }, { label: 'Negotiations', path: `/rfqs/${rfqId}/negotiations` }, { label: negotiation.id, path: `/rfqs/${rfqId}/negotiations/${negotiation.id}` }]}>
      <RecordHeader title={`Negotiation ${negotiation.id}`} status={negotiation.status === 'active' ? 'active' : negotiation.status === 'closed' ? 'closed' : 'pending'} metadata={[{ label: 'Vendor', value: getVendor(negotiation.vendorId)?.name ?? negotiation.vendorId }, { label: 'Strategy', value: negotiation.strategy }, { label: 'BAFO', value: negotiation.bafoDue }, { label: 'Owner', value: negotiation.owner }]} actions={<Button onClick={() => navigate(`/rfqs/${rfqId}/award`)}>Close negotiation</Button>} />
      <div className="grid gap-5 xl:grid-cols-[1fr,340px]">
        <SectionCard title="Round timeline">
          <div className="space-y-4">
            {negotiation.rounds.map(round => (
              <div key={round.id} className="flex gap-3">
                <div className="mt-1.5 h-2.5 w-2.5 rounded-full bg-indigo-500" />
                <div>
                  <p className="text-sm font-medium text-slate-800">{round.label}</p>
                  <p className="text-xs text-slate-500">{round.timestamp} · {round.value}</p>
                  <p className="mt-1 text-sm text-slate-600">{round.note}</p>
                </div>
              </div>
            ))}
          </div>
        </SectionCard>
        <SectionCard title="Counter-offer form" subtitle="Mock BAFO controls">
          <div className="space-y-3">
            <Textarea label="Negotiation note" value={note} onChange={event => setNote(event.target.value)} />
            <TextInput label="Counter value" value={value} onChange={event => setValue(event.target.value)} />
            <Button fullWidth onClick={() => {
              addNegotiationRound(negotiation.id, note || 'Counter-offer submitted.', value);
              setNote('');
            }}>Add negotiation round</Button>
          </div>
        </SectionCard>
      </div>
    </WorkspaceCanvas>
  );
}

function DocumentsPage({ workspaceScoped = false }: { workspaceScoped?: boolean }) {
  const { rfqId } = useParams();
  const { data } = useAppStore();
  const { getRfq } = useEntityLookups();
  const documents = workspaceScoped ? data.documents.filter(document => document.rfqId === rfqId) : data.documents;

  const body = (
    <div className="space-y-5">
      <PageHeader title={workspaceScoped ? 'RFQ documents' : 'Document vault'} subtitle={workspaceScoped ? 'Evidence scoped to the active RFQ' : 'Global evidence vault with bundle tags and search-oriented metadata'} />
      <div className="grid gap-4 lg:grid-cols-2">
        {documents.map(document => (
          <Card key={document.id}>
            <div className="flex items-start justify-between gap-3">
              <div>
                <p className="text-sm font-medium text-slate-800">{document.title}</p>
                <p className="text-xs text-slate-500">{document.bundle} · {document.updatedAt}</p>
              </div>
              <StatusBadge status="draft" label={document.tag} />
            </div>
            <div className="mt-3 flex items-center justify-between text-xs text-slate-500">
              <span>{document.owner}</span>
              <span>{document.size}</span>
            </div>
          </Card>
        ))}
      </div>
    </div>
  );

  if (workspaceScoped) {
    return (
      <WorkspaceCanvas segments={[{ label: 'RFQs', path: '/rfqs' }, { label: getRfq(rfqId)?.title ?? rfqId ?? 'RFQ', path: `/rfqs/${rfqId}/overview` }, { label: 'Documents', path: `/rfqs/${rfqId}/documents` }]}>
        {body}
      </WorkspaceCanvas>
    );
  }

  return body;
}

function RiskPage() {
  const { rfqId = '' } = useParams();
  const { data } = useAppStore();
  const { getRfq } = useEntityLookups();
  const rfq = getRfq(rfqId);
  const risks = data.risks.filter(risk => risk.rfqId === rfqId);

  if (!rfq) return null;

  return (
    <WorkspaceCanvas segments={[{ label: 'RFQs', path: '/rfqs' }, { label: rfq.title, path: `/rfqs/${rfq.id}/overview` }, { label: 'Risk & Compliance', path: `/rfqs/${rfq.id}/risk` }]}>
      <PageHeader title="Risk & compliance" subtitle="Due diligence, sanctions, and operational review" />
      <div className="grid gap-5 xl:grid-cols-[1fr,360px]">
        <SectionCard title="Risk panels">
          <div className="space-y-3">
            {risks.map(risk => (
              <Alert key={risk.id} variant={risk.severity === 'high' ? 'error' : risk.severity === 'medium' ? 'warning' : 'info'} title={risk.title}>
                {risk.detail}
              </Alert>
            ))}
          </div>
        </SectionCard>
        <SectionCard title="Due diligence checklist">
          <Checklist
            items={[
              { label: 'Sanctions screening complete', status: 'pass' },
              { label: 'Insurance coverage validated', status: 'warning', detail: 'One certificate expires before shipment milestone.' },
              { label: 'Tax forms on file', status: 'pass' },
              { label: 'Warranty variance reviewed', status: 'warning' },
            ]}
          />
        </SectionCard>
      </div>
    </WorkspaceCanvas>
  );
}

function DecisionTrailPage() {
  const { rfqId = '' } = useParams();
  const { data } = useAppStore();
  const { getRfq } = useEntityLookups();
  const rfq = getRfq(rfqId);
  const entries = data.decisionTrail.filter(entry => entry.rfqId === rfqId);
  if (!rfq) return null;

  return (
    <WorkspaceCanvas segments={[{ label: 'RFQs', path: '/rfqs' }, { label: rfq.title, path: `/rfqs/${rfq.id}/overview` }, { label: 'Decision Trail', path: `/rfqs/${rfq.id}/decision-trail` }]}>
      <PageHeader title="Decision trail" subtitle="Audit ledger with explicit decision lineage and hash references" actions={<Button variant="outline">Export ledger</Button>} />
      <SectionCard title="Ledger entries">
        <div className="space-y-4">
          {entries.map(entry => (
            <div key={entry.id} className="rounded-lg border border-slate-200 px-4 py-3">
              <div className="flex items-center justify-between gap-3">
                <div>
                  <p className="text-sm font-medium text-slate-800">{entry.event}</p>
                  <p className="text-xs text-slate-500">{entry.actor} · {entry.timestamp}</p>
                </div>
                <span className="rounded bg-slate-100 px-2 py-1 font-mono text-[11px] text-slate-600">{entry.hash}</span>
              </div>
              <p className="mt-2 text-sm text-slate-600">{entry.detail}</p>
            </div>
          ))}
        </div>
      </SectionCard>
    </WorkspaceCanvas>
  );
}

function AwardPage() {
  const rfq = useWorkspaceRfq();
  const { updateAwardSplit, toggleAwardChecklist, finalizeAward, sendDebrief, retryHandoff } = useAppStore();
  const [confirmOpen, setConfirmOpen] = React.useState(false);
  if (!rfq || !rfq.award) return null;

  return (
    <>
      <WorkspaceCanvas segments={[{ label: 'RFQs', path: '/rfqs' }, { label: rfq.title, path: `/rfqs/${rfq.id}/overview` }, { label: 'Award', path: `/rfqs/${rfq.id}/award` }]}>
        <PageHeader title="Award decision" subtitle="Winner confirmation, split allocation, debrief, and downstream handoff" />
        <div className="grid gap-5 xl:grid-cols-[1fr,360px]">
          <div className="space-y-5">
            <AwardDecisionSummary winner={rfq.award.splitAllocation[0]?.name ?? 'TBD'} total={rfq.award.total} savings={rfq.award.savings} confidence={rfq.award.confidence} />
            <SplitAllocationEditor vendors={rfq.award.splitAllocation} onChange={split => updateAwardSplit(rfq.id, split)} />
            <DebriefStatusList vendors={rfq.award.debriefs} onSend={vendorId => sendDebrief(rfq.id, vendorId)} />
            <HandoffStatusTimeline items={rfq.award.handoffTimeline} />
            <PayloadPreviewPanel destination={rfq.award.handoffTimeline.some(item => item.label.includes('contract')) ? 'Oracle ERP' : 'SAP ERP'} payload={rfq.award.payloadPreview} />
            {rfq.award.handoffTimeline.some(item => item.state === 'failed') && <Button variant="outline" onClick={() => retryHandoff(rfq.id)}>Retry handoff</Button>}
          </div>
          <div className="space-y-5">
            <SectionCard title="Governance">
              <div className="space-y-3">
                {rfq.award.standstillDaysRemaining > 0 && <ProtestTimerBadge value={`Standstill: ${rfq.award.standstillDaysRemaining} days remaining`} />}
                <Alert variant="info" title="Savings impact">
                  Award scenario projects {rfq.award.savings} savings against original estimate.
                </Alert>
              </div>
            </SectionCard>
            <SignOffChecklist items={rfq.award.signOffChecklist} onToggle={(checkId, checked) => toggleAwardChecklist(rfq.id, checkId, checked)} onFinalize={() => setConfirmOpen(true)} />
          </div>
        </div>
      </WorkspaceCanvas>
      <SlideOver
        open={confirmOpen}
        onClose={() => setConfirmOpen(false)}
        title="Award confirmation"
        subtitle="Finalize winner and enable handoff"
        width="sm"
        footer={(
          <>
            <Button variant="ghost" onClick={() => setConfirmOpen(false)}>Cancel</Button>
            <Button onClick={() => {
              finalizeAward(rfq.id);
              setConfirmOpen(false);
            }}>Finalize award</Button>
          </>
        )}
      >
        <SlideOverSection>
          <p className="text-sm text-slate-600">If standstill is configured, the protest timer remains visible while the award is marked finalized.</p>
        </SlideOverSection>
      </SlideOver>
    </>
  );
}

function ReportingPage() {
  const { data } = useAppStore();
  return (
    <div className="space-y-5">
      <PageHeader title="Reports & analytics" subtitle="Executive and operational views retained in the v2 shell" />
      <div className="grid gap-4 xl:grid-cols-3 md:grid-cols-2">
        {data.reports.map(report => (
          <Card key={report.id}>
            <div className="flex items-center justify-between gap-3">
              <p className="text-sm font-semibold text-slate-800">{report.title}</p>
              <Button variant="ghost" size="sm">Open</Button>
            </div>
            <p className="mt-2 text-sm text-slate-600">{report.summary}</p>
            <div className="mt-4 flex items-center justify-between text-xs text-slate-500">
              <span>{report.owner}</span>
              <span>{report.lastGenerated}</span>
            </div>
          </Card>
        ))}
      </div>
    </div>
  );
}

function SettingsPage({ section }: { section: 'users' | 'scoring-policies' | 'templates' | 'integrations' | 'feature-flags' }) {
  const navigate = useNavigate();
  const { data } = useAppStore();

  const settingsTabs = [
    { id: 'users', label: 'Users & Roles' },
    { id: 'scoring-policies', label: 'Scoring Policies' },
    { id: 'templates', label: 'Templates' },
    { id: 'integrations', label: 'Integrations' },
    { id: 'feature-flags', label: 'Feature Flags' },
  ];

  return (
    <div className="space-y-5">
      <PageHeader title="Settings" subtitle="Default-layout admin modules retained from the blueprint" />
      <div className="grid gap-5 xl:grid-cols-[220px,1fr]">
        <Card>
          <VerticalTabs tabs={settingsTabs} activeTab={section} onChange={id => navigate(`/settings/${id}`)} />
        </Card>
        <div className="space-y-5">
          {section === 'users' && (
            <SectionCard title="Users & roles">
              <div className="space-y-3">
                {data.settings.users.map(user => (
                  <div key={user.id} className="rounded-lg border border-slate-200 px-4 py-3">
                    <div className="flex items-center justify-between gap-3">
                      <AvatarLabel name={user.name} subtitle={user.role} />
                      <StatusBadge status={user.status === 'active' ? 'active' : 'pending'} label={user.status} />
                    </div>
                    <p className="mt-2 text-xs text-slate-500">{user.permissions.join(' · ')}</p>
                  </div>
                ))}
              </div>
            </SectionCard>
          )}
          {section === 'scoring-policies' && (
            <div className="space-y-5">
              <SectionCard title="Scoring policies">
                <div className="space-y-3">
                  {data.settings.scoringPolicies.map(policy => (
                    <div key={policy.id} className="rounded-lg border border-slate-200 px-4 py-3">
                      <div className="flex items-center justify-between gap-3">
                        <div>
                          <p className="text-sm font-medium text-slate-800">{policy.name}</p>
                          <p className="text-xs text-slate-500">{policy.weights.join(' · ')}</p>
                        </div>
                        <StatusBadge status={policy.status === 'active' ? 'active' : 'draft'} label={policy.version} />
                      </div>
                    </div>
                  ))}
                </div>
              </SectionCard>
              <SectionCard title="Scoring model builder" subtitle="Retained from v1 and reachable from comparison matrix">
                <div className="grid gap-4 md:grid-cols-2">
                  <TextInput label="Model name" value="Balanced Procurement" readOnly />
                  <TextInput label="Version" value="v3.2" readOnly />
                  <TextInput label="Commercial weight" value="45%" readOnly />
                  <TextInput label="Technical weight" value="35%" readOnly />
                  <TextInput label="Risk weight" value="20%" readOnly />
                </div>
              </SectionCard>
            </div>
          )}
          {section === 'templates' && (
            <SectionCard title="RFQ templates">
              <div className="space-y-3">
                {data.settings.templates.map(template => (
                  <div key={template.id} className="rounded-lg border border-slate-200 px-4 py-3">
                    <div className="flex items-center justify-between gap-3">
                      <div>
                        <p className="text-sm font-medium text-slate-800">{template.name}</p>
                        <p className="text-xs text-slate-500">{template.category}</p>
                      </div>
                      <span className="text-xs text-slate-500">{template.lineItemCount} lines</span>
                    </div>
                  </div>
                ))}
              </div>
            </SectionCard>
          )}
          {section === 'integrations' && (
            <SectionCard title="Integrations configuration">
              <div className="grid gap-4 md:grid-cols-2">
                {data.settings.integrations.map(integration => (
                  <Card key={integration.id}>
                    <div className="flex items-center justify-between gap-3">
                      <div>
                        <p className="text-sm font-medium text-slate-800">{integration.name}</p>
                        <p className="text-xs text-slate-500">Last sync {integration.lastSync}</p>
                      </div>
                      <StatusBadge status={integration.status === 'connected' ? 'approved' : 'pending'} label={integration.status} />
                    </div>
                    <p className="mt-3 text-sm text-slate-600">Queue depth: {integration.queueDepth}</p>
                  </Card>
                ))}
              </div>
            </SectionCard>
          )}
          {section === 'feature-flags' && (
            <SectionCard title="Feature flags">
              <div className="space-y-3">
                {data.settings.featureFlags.map(flag => (
                  <div key={flag.id} className="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3">
                    <div>
                      <p className="text-sm font-medium text-slate-800">{flag.name}</p>
                      <p className="text-xs text-slate-500">{flag.description}</p>
                    </div>
                    <ToggleSwitch checked={flag.enabled} onChange={() => undefined} />
                  </div>
                ))}
              </div>
            </SectionCard>
          )}
        </div>
      </div>
    </div>
  );
}

function NotificationsPage() {
  const { data, markNotificationRead } = useAppStore();
  const navigate = useNavigate();
  return (
    <div className="space-y-5">
      <PageHeader title="Notification center" subtitle="Dedicated feed in addition to the top-bar slide-over" />
      <div className="space-y-3">
        {data.notifications.map(notification => (
          <button
            key={notification.id}
            onClick={() => {
              markNotificationRead(notification.id);
              navigate(notification.targetPath);
            }}
            className="flex w-full items-start justify-between rounded-lg border border-slate-200 px-4 py-3 text-left hover:border-indigo-300 hover:bg-indigo-50/40"
          >
            <div>
              <p className="text-sm font-medium text-slate-800">{notification.title}</p>
              <p className="mt-1 text-sm text-slate-600">{notification.description}</p>
            </div>
            <div className="flex items-center gap-2 text-xs text-slate-400">
              {notification.unread && <CountBadge count={1} variant="red" />}
              <span>{notification.timestamp}</span>
            </div>
          </button>
        ))}
      </div>
    </div>
  );
}

function SignInPage() {
  const navigate = useNavigate();
  const location = useLocation();
  const [searchParams] = useSearchParams();
  const [error, setError] = React.useState('');
  const [forgotOpen, setForgotOpen] = React.useState(false);
  const [mfaOpen, setMfaOpen] = React.useState(false);
  const sessionExpired = searchParams.get('session_expired') === '1';

  return (
    <>
      <div className="grid min-h-screen bg-slate-100 lg:grid-cols-[1.1fr,0.9fr]">
        <div className="hidden bg-indigo-950 px-16 py-20 text-white lg:flex lg:flex-col lg:justify-between">
          <div>
            <div className="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs uppercase tracking-wide">
              <Bot size={14} /> Atomy-Q v2 shell
            </div>
            <h1 className="mt-8 max-w-xl text-4xl font-semibold leading-tight">
              Operational intelligence workspace for governed quote comparison.
            </h1>
            <p className="mt-5 max-w-xl text-sm leading-7 text-indigo-100">
              The desktop-first shell mirrors the blueprint workflow: RFQ creation, intake, normalization, comparison, approvals, negotiation, award, reporting, and settings.
            </p>
          </div>
          <div className="grid gap-4 md:grid-cols-3">
            <Card className="border-white/10 bg-white/10 text-white shadow-none">
              <p className="text-2xl font-semibold">2 layouts</p>
              <p className="mt-2 text-xs text-indigo-100">Default and RFQ workspace layouts stay consistent across the app.</p>
            </Card>
            <Card className="border-white/10 bg-white/10 text-white shadow-none">
              <p className="text-2xl font-semibold">All screens</p>
              <p className="mt-2 text-xs text-indigo-100">Every documented screen is represented in the mock shell route tree.</p>
            </Card>
            <Card className="border-white/10 bg-white/10 text-white shadow-none">
              <p className="text-2xl font-semibold">Single dataset</p>
              <p className="mt-2 text-xs text-indigo-100">Mock records cover drafts, stale runs, pending approvals, and handoff failures.</p>
            </Card>
          </div>
        </div>
        <div className="flex items-center justify-center px-6 py-12">
          <div className="w-full max-w-md">
            <SignInCard
              error={error}
              sessionExpired={sessionExpired}
              onForgotPassword={() => setForgotOpen(true)}
              onSso={() => navigate('/dashboard')}
              onSubmit={({ email, password, rememberDevice }) => {
                if (email.includes('locked')) {
                  setError('This account is locked. Contact your administrator.');
                  return;
                }
                if (password !== 'demo123') {
                  setError('Invalid credentials. Use password demo123 for the mock shell.');
                  return;
                }
                setError('');
                if (!rememberDevice || email.includes('mfa')) {
                  setMfaOpen(true);
                  return;
                }
                const targetPath = (location.state as { from?: string } | null)?.from ?? '/dashboard';
                navigate(targetPath);
              }}
            />
            <p className="mt-4 text-center text-xs text-slate-500">Use any `@atomyq.com` email and password `demo123`.</p>
          </div>
        </div>
      </div>
      <SlideOver open={forgotOpen} onClose={() => setForgotOpen(false)} title="Reset password" subtitle="Recover workspace access" width="sm" footer={<Button onClick={() => setForgotOpen(false)}>Send reset link</Button>}>
        <SlideOverSection>
          <TextInput label="Email" placeholder="name@company.com" />
        </SlideOverSection>
      </SlideOver>
      <SlideOver
        open={mfaOpen}
        onClose={() => setMfaOpen(false)}
        title="Verify MFA"
        subtitle="Second factor required for this session"
        width="sm"
      >
        <SlideOverSection>
          <MfaPromptPanel
            onVerify={() => {
              setMfaOpen(false);
              navigate('/dashboard');
            }}
          />
        </SlideOverSection>
      </SlideOver>
    </>
  );
}

function MissingPage() {
  return (
    <div className="flex min-h-[60vh] items-center justify-center">
      <EmptyState title="Page not found" description="This route is not defined in the mock shell." icon={<AlertCircle size={24} />} />
    </div>
  );
}

const router = createBrowserRouter(
  createRoutesFromElements(
    <Route element={<AppProvider><Outlet /></AppProvider>}>
      <Route path="/signin" element={<SignInPage />} />
      <Route element={<DefaultRouteLayout />}>
        <Route index element={<Navigate to="/dashboard" replace />} />
        <Route path="/dashboard" element={<DashboardPage />} />
        <Route path="/rfqs" element={<RfqListPage />} />
        <Route path="/rfqs/create" element={<CreateRfqPage />} />
        <Route path="/approvals" element={<ApprovalQueuePage />} />
        <Route path="/documents" element={<DocumentsPage />} />
        <Route path="/reporting" element={<ReportingPage />} />
        <Route path="/settings/users" element={<SettingsPage section="users" />} />
        <Route path="/settings/scoring-policies" element={<SettingsPage section="scoring-policies" />} />
        <Route path="/settings/templates" element={<SettingsPage section="templates" />} />
        <Route path="/settings/integrations" element={<SettingsPage section="integrations" />} />
        <Route path="/settings/feature-flags" element={<SettingsPage section="feature-flags" />} />
        <Route path="/notifications" element={<NotificationsPage />} />
      </Route>
      <Route path="/rfqs/:rfqId" element={<WorkspaceRouteLayout />}>
        <Route index element={<Navigate to="overview" replace />} />
        <Route path="overview" element={<WorkspaceOverviewPage />} />
        <Route path="details" element={<WorkspaceDetailsPage />} />
        <Route path="line-items" element={<WorkspaceLineItemsPage />} />
        <Route path="vendors" element={<WorkspaceVendorsPage />} />
        <Route path="award" element={<AwardPage />} />
        <Route path="intake" element={<QuoteIntakeListPage />} />
        <Route path="intake/:quoteId" element={<QuoteIntakeDetailPage />} />
        <Route path="intake/:quoteId/normalize" element={<NormalizationPage />} />
        <Route path="runs" element={<ComparisonRunsListPage />} />
        <Route path="runs/:runId" element={<ComparisonMatrixPage />} />
        <Route path="approvals" element={<ApprovalQueuePage workspaceScoped />} />
        <Route path="approvals/:approvalId" element={<ApprovalDetailPage />} />
        <Route path="negotiations" element={<NegotiationsListPage />} />
        <Route path="negotiations/:negotiationId" element={<NegotiationDetailPage />} />
        <Route path="documents" element={<DocumentsPage workspaceScoped />} />
        <Route path="risk" element={<RiskPage />} />
        <Route path="decision-trail" element={<DecisionTrailPage />} />
      </Route>
      <Route path="*" element={<MissingPage />} />
    </Route>,
  ),
);

export default function App() {
  return <RouterProvider router={router} />;
}

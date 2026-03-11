/**
 * Atomy-Q Design System — Enriched Mock Data
 * Shared sample data for showcase and reusable UI design.
 */

// ─── RFQ / Requisition ────────────────────────────────────────────────────────

export type RFQStatus = 'active' | 'closed' | 'awarded' | 'draft' | 'pending' | 'approved' | 'rejected';

export interface RFQRow {
  id: string;
  rfqId: string;
  title: string;
  owner: string;
  status: RFQStatus;
  deadline: string;
  estValue: string;
  category: string;
  vendors: number;
  quotes: number;
  savings: string;
}

export const RFQ_ROWS: RFQRow[] = [
  { id: '1', rfqId: 'RFQ-2401', title: 'Server Infrastructure Refresh', owner: 'Alex Kumar', status: 'active', deadline: '2026-04-15', estValue: '$1,200,000', category: 'IT Hardware', vendors: 5, quotes: 8, savings: '12%' },
  { id: '2', rfqId: 'RFQ-2402', title: 'Office Furniture Q2', owner: 'Sarah Chen', status: 'pending', deadline: '2026-03-28', estValue: '$84,500', category: 'Facilities', vendors: 3, quotes: 3, savings: '—' },
  { id: '3', rfqId: 'RFQ-2403', title: 'Cloud Software Licenses', owner: 'Marcus Webb', status: 'active', deadline: '2026-04-02', estValue: '$340,000', category: 'Software', vendors: 4, quotes: 6, savings: '8%' },
  { id: '4', rfqId: 'RFQ-2404', title: 'Network Security Audit', owner: 'Priya Nair', status: 'awarded', deadline: '2026-03-10', estValue: '$95,000', category: 'Security', vendors: 2, quotes: 2, savings: '5%' },
  { id: '5', rfqId: 'RFQ-2405', title: 'Marketing Print Materials', owner: 'James Okonkwo', status: 'draft', deadline: '2026-05-01', estValue: '$12,000', category: 'Marketing', vendors: 0, quotes: 0, savings: '—' },
  { id: '6', rfqId: 'RFQ-2406', title: 'Fleet Vehicle Leasing', owner: 'Sarah Chen', status: 'closed', deadline: '2026-02-28', estValue: '$480,000', category: 'Transport', vendors: 6, quotes: 6, savings: '3%' },
  { id: '7', rfqId: 'RFQ-2407', title: 'Catering Services Contract', owner: 'Alex Kumar', status: 'active', deadline: '2026-04-20', estValue: '$72,000', category: 'Facilities', vendors: 4, quotes: 5, savings: '14%' },
  { id: '8', rfqId: 'RFQ-2408', title: 'IT Support Annual', owner: 'Marcus Webb', status: 'pending', deadline: '2026-03-31', estValue: '$220,000', category: 'IT Services', vendors: 2, quotes: 1, savings: '—' },
  { id: '9', rfqId: 'RFQ-2409', title: 'Data Center Cooling Upgrade', owner: 'Priya Nair', status: 'active', deadline: '2026-05-15', estValue: '$580,000', category: 'IT Hardware', vendors: 4, quotes: 4, savings: '9%' },
  { id: '10', rfqId: 'RFQ-2410', title: 'Cybersecurity Training Program', owner: 'James Okonkwo', status: 'draft', deadline: '2026-06-01', estValue: '$45,000', category: 'Security', vendors: 0, quotes: 0, savings: '—' },
  { id: '11', rfqId: 'RFQ-2411', title: 'ERP Integration Services', owner: 'Alex Kumar', status: 'awarded', deadline: '2026-02-15', estValue: '$320,000', category: 'Software', vendors: 3, quotes: 3, savings: '11%' },
  { id: '12', rfqId: 'RFQ-2412', title: 'Warehouse Shelving & Racking', owner: 'Sarah Chen', status: 'pending', deadline: '2026-04-08', estValue: '$156,000', category: 'Facilities', vendors: 5, quotes: 2, savings: '—' },
];

// ─── Owner / Vendor Contact Details ────────────────────────────────────────────

export interface PersonDetails {
  email: string;
  reportsTo: string;
  mobile: string;
  location: string;
  department?: string;
}

export const OWNER_DETAILS: Record<string, PersonDetails> = {
  'Alex Kumar': { email: 'alexk@ensemble.com', reportsTo: 'Jordan Lee', mobile: '+1 83773 48841', location: 'United States', department: 'Procurement' },
  'Sarah Chen': { email: 'sarahc@ensemble.com', reportsTo: 'Jordan Lee', mobile: '+1 83773 48842', location: 'United States', department: 'Operations' },
  'Marcus Webb': { email: 'marcusw@ensemble.com', reportsTo: 'Melomia Zeuski', mobile: '+1 83773 48843', location: 'United Kingdom', department: 'IT' },
  'Priya Nair': { email: 'priyan@ensemble.com', reportsTo: 'Melomia Zeuski', mobile: '+1 83773 48844', location: 'India', department: 'Security' },
  'James Okonkwo': { email: 'jameso@ensemble.com', reportsTo: 'Melomia Zeuski', mobile: '+1 83773 48845', location: 'Nigeria', department: 'Marketing' },
};

export const VENDOR_DETAILS: Record<string, PersonDetails> = {
  'Dell Technologies': { email: 'procurement@dell.com', reportsTo: 'Account Manager', mobile: '+1 800 624 9897', location: 'United States', department: 'Sales' },
  'HP Enterprise': { email: 'hpe-quotes@hpe.com', reportsTo: 'Regional Lead', mobile: '+1 650 857 1501', location: 'United States', department: 'Enterprise' },
  'Lenovo': { email: 'bid@lenovo.com', reportsTo: 'Account Director', mobile: '+1 855 253 6686', location: 'United States', department: 'Commercial' },
  'Microsoft': { email: 'licensing@microsoft.com', reportsTo: 'Channel Manager', mobile: '+1 800 642 7676', location: 'United States', department: 'Licensing' },
  'Cisco Systems': { email: 'tenders@cisco.com', reportsTo: 'Solutions Architect', mobile: '+1 800 553 6387', location: 'United States', department: 'Security' },
};

// ─── Activity / Timeline ──────────────────────────────────────────────────────

export interface ActivityEvent {
  id: string;
  timestamp: string;
  actor: string;
  action: string;
  iconColor?: 'indigo' | 'green' | 'amber' | 'red' | 'slate';
  rfqId?: string;
}

export const ACTIVITY_EVENTS: ActivityEvent[] = [
  { id: '1', timestamp: '2 hours ago', actor: 'Alex Kumar', action: 'uploaded quote from Dell Technologies', iconColor: 'indigo', rfqId: 'RFQ-2401' },
  { id: '2', timestamp: '4 hours ago', actor: 'Priya Nair', action: 'sent invitation to HP Enterprise', iconColor: 'slate', rfqId: 'RFQ-2403' },
  { id: '3', timestamp: '6 hours ago', actor: 'System', action: 'normalization completed for Lenovo quote (18/24 lines)', iconColor: 'green', rfqId: 'RFQ-2401' },
  { id: '4', timestamp: 'Yesterday', actor: 'Alex Kumar', action: 'ran comparison preview — Run #004', iconColor: 'indigo', rfqId: 'RFQ-2401' },
  { id: '5', timestamp: 'Yesterday', actor: 'System', action: 'RFQ published and vendors notified', iconColor: 'green', rfqId: 'RFQ-2402' },
  { id: '6', timestamp: '3 days ago', actor: 'Marcus Webb', action: 'created RFQ-2401', iconColor: 'slate', rfqId: 'RFQ-2401' },
  { id: '7', timestamp: '4 days ago', actor: 'Sarah Chen', action: 'awarded RFQ-2404 to Cisco Systems', iconColor: 'green', rfqId: 'RFQ-2404' },
  { id: '8', timestamp: '5 days ago', actor: 'James Okonkwo', action: 'submitted for approval', iconColor: 'amber', rfqId: 'RFQ-2405' },
  { id: '9', timestamp: '1 week ago', actor: 'Priya Nair', action: 'completed risk review for RFQ-2403', iconColor: 'green', rfqId: 'RFQ-2403' },
];

// ─── Dashboard Metrics ────────────────────────────────────────────────────────

export interface DashboardPipelineMetrics {
  active: number;
  pending: number;
  awarded: number;
  draft: number;
  closed: number;
}

export const DASHBOARD_PIPELINE: DashboardPipelineMetrics = {
  active: 5,
  pending: 3,
  awarded: 3,
  draft: 2,
  closed: 4,
};

export interface DashboardSavingsMetrics {
  totalRealized: string;
  totalPipeline: string;
  avgSavingsPct: string;
  yoyTrend: number; // e.g. 12 = +12%
}

export const DASHBOARD_SAVINGS: DashboardSavingsMetrics = {
  totalRealized: '$2.4M',
  totalPipeline: '$847K',
  avgSavingsPct: '8.2%',
  yoyTrend: 12,
};

export interface SLAAlert {
  id: string;
  title: string;
  rfqId: string;
  type: 'approval' | 'deadline' | 'submission';
  urgency: 'high' | 'medium' | 'low';
  timeRemaining: string;
  assignee?: string;
}

export const DASHBOARD_SLA_ALERTS: SLAAlert[] = [
  { id: 'sla-1', title: 'Comparison approval required', rfqId: 'RFQ-2401', type: 'approval', urgency: 'high', timeRemaining: '4h 32m', assignee: 'Marcus Webb' },
  { id: 'sla-2', title: 'Quote submission closes', rfqId: 'RFQ-2402', type: 'submission', urgency: 'medium', timeRemaining: '2d 14h' },
  { id: 'sla-3', title: 'Award decision due', rfqId: 'RFQ-2403', type: 'deadline', urgency: 'medium', timeRemaining: '5d 8h', assignee: 'Alex Kumar' },
];

export interface CategoryMetric {
  category: string;
  count: number;
  estValue: string;
  pct: number;
}

export const DASHBOARD_CATEGORY_BREAKDOWN: CategoryMetric[] = [
  { category: 'IT Hardware', count: 3, estValue: '$1.96M', pct: 42 },
  { category: 'Software', count: 2, estValue: '$660K', pct: 14 },
  { category: 'Facilities', count: 3, estValue: '$313K', pct: 7 },
  { category: 'Security', count: 2, estValue: '$140K', pct: 3 },
  { category: 'Transport', count: 1, estValue: '$480K', pct: 10 },
  { category: 'Other', count: 1, estValue: '$57K', pct: 1 },
];

export interface PendingApproval {
  id: string;
  rfqId: string;
  rfqTitle: string;
  type: string;
  assignee: string;
  submittedAt: string;
}

export const DASHBOARD_PENDING_APPROVALS: PendingApproval[] = [
  { id: 'pa-1', rfqId: 'RFQ-2401', rfqTitle: 'Server Infrastructure Refresh', type: 'Comparison', assignee: 'Marcus Webb', submittedAt: '2 days ago' },
  { id: 'pa-2', rfqId: 'RFQ-2403', rfqTitle: 'Cloud Software Licenses', type: 'Risk Review', assignee: 'Priya Nair', submittedAt: '1 day ago' },
  { id: 'pa-3', rfqId: 'RFQ-2412', rfqTitle: 'Warehouse Shelving & Racking', type: 'Award', assignee: 'Sarah Chen', submittedAt: '4 hours ago' },
];

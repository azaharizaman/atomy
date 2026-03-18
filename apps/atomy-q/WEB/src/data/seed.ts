/**
 * Central seed data for mock mode: 100+ RFQs with a coherent lifecycle.
 * Rules: draft → no quotes; awarded → has approved run + award; approvals match run status.
 */

import type { RfqStatus } from '@/hooks/use-rfqs';

const CATEGORIES = [
  'IT Hardware',
  'Facilities',
  'Software',
  'Security',
  'Marketing',
  'Transport',
  'IT Services',
  'Professional Services',
  'Office Supplies',
  'Cloud Services',
];

const DEPARTMENTS = ['Procurement', 'Finance', 'IT', 'Operations', 'Facilities', 'HR'];

const OWNERS = [
  { name: 'Alex Kumar', email: 'alex@example.com' },
  { name: 'Sarah Chen', email: 'sarah@example.com' },
  { name: 'Marcus Webb', email: 'marcus@example.com' },
  { name: 'Priya Nair', email: 'priya@example.com' },
  { name: 'James Okonkwo', email: 'james@example.com' },
  { name: 'Elena Vasquez', email: 'elena@example.com' },
  { name: 'David Park', email: 'david@example.com' },
  { name: 'Rachel Green', email: 'rachel@example.com' },
];

const VENDOR_NAMES = [
  'Dell Technologies',
  'HP Enterprise',
  'Lenovo',
  'Cisco Systems',
  'IBM',
  'Fujitsu',
  'Supermicro',
  'Juniper Networks',
  'Microsoft',
  'Oracle',
  'SAP',
  'Amazon Web Services',
  'Google Cloud',
  'VMware',
  'Accenture',
  'Deloitte',
  'Capgemini',
  'Office Depot',
  'Staples',
  'CDW',
  'Insight',
  'SHI',
  'Carahsoft',
  'Tech Data',
  'Ingram Micro',
];

const TITLE_TEMPLATES = [
  'Server Infrastructure Refresh',
  'Office Furniture Q2',
  'Cloud Software Licenses',
  'Network Security Audit',
  'Marketing Print Materials',
  'Fleet Vehicle Leasing',
  'Catering Services Contract',
  'IT Support Annual',
  'Data Center Hardware',
  'Endpoint Security Suite',
  'ERP Module Upgrade',
  'Consulting Services Retainer',
  'Backup and DR Solution',
  'Unified Communications',
  'Warehouse Equipment',
  'Facility Maintenance',
  'Training and Development',
  'Legal Services Panel',
  'Travel Management',
  'Temporary Staffing',
];

/** Deterministic hash from number to 0..1 */
function hash(n: number): number {
  const x = Math.sin(n * 9999) * 10000;
  return x - Math.floor(x);
}

function pick<T>(arr: T[], index: number): T {
  return arr[Math.floor(hash(index) * arr.length) % arr.length];
}

export interface SeedRfq {
  id: string;
  rfqNumber: string;
  title: string;
  status: RfqStatus;
  projectId?: string | null;
  category: string;
  department: string;
  owner: { name: string; email: string };
  estimatedValue: number;
  savingsPercent: number;
  submissionDeadline: string;
  vendorsCount: number;
  quotesCount: number;
  description?: string;
}

export interface SeedVendor {
  id: string;
  rfqId: string;
  name: string;
  email: string;
  status: 'invited' | 'responded';
}

export interface SeedQuote {
  id: string;
  rfqId: string;
  vendorId: string;
  vendorName: string;
  fileName: string;
  status: 'processing' | 'parsed' | 'accepted' | 'rejected' | 'error';
  confidence: 'high' | 'medium' | 'low';
  uploadedAt: string;
}

export interface SeedComparisonRun {
  id: string;
  rfqId: string;
  runId: string;
  date: string;
  type: 'preview' | 'final';
  status: 'generated' | 'stale' | 'locked';
  scoringModel: string;
  createdBy: string;
}

export interface SeedApproval {
  id: string;
  rfqId: string;
  comparisonRunId: string;
  type: string;
  summary: string;
  priority: 'high' | 'medium' | 'low';
  status: 'pending' | 'approved' | 'rejected';
  assignee: string;
}

export interface SeedAward {
  rfqId: string;
  winnerVendorId: string;
  winnerVendorName: string;
  amount: number;
  savingsPercent: number;
}

const SEED_RFQ_COUNT = 105;
let cachedSeed: {
  rfqs: SeedRfq[];
  vendorsByRfq: Map<string, SeedVendor[]>;
  quotesByRfq: Map<string, SeedQuote[]>;
  runsByRfq: Map<string, SeedComparisonRun[]>;
  approvalsByRfq: Map<string, SeedApproval[]>;
  awardByRfq: Map<string, SeedAward>;
} | null = null;

function buildSeed(): NonNullable<typeof cachedSeed> {
  if (cachedSeed) return cachedSeed;

  const rfqs: SeedRfq[] = [];
  const vendorsByRfq = new Map<string, SeedVendor[]>();
  const quotesByRfq = new Map<string, SeedQuote[]>();
  const runsByRfq = new Map<string, SeedComparisonRun[]>();
  const approvalsByRfq = new Map<string, SeedApproval[]>();
  const awardByRfq = new Map<string, SeedAward>();

  // Status distribution: ~12 draft, ~18 pending, ~25 active, ~22 closed, ~20 awarded, ~8 archived
  const statusByIndex = (i: number): RfqStatus => {
    const h = hash(i);
    if (h < 0.12) return 'draft';
    if (h < 0.30) return 'pending';
    if (h < 0.55) return 'active';
    if (h < 0.77) return 'closed';
    if (h < 0.97) return 'awarded';
    return 'archived';
  };

  for (let i = 1; i <= SEED_RFQ_COUNT; i++) {
    const rfqNumber = `RFQ-2026-${String(i).padStart(4, '0')}`;
    const id = rfqNumber;
    const status = statusByIndex(i);
    const owner = pick(OWNERS, i);
    const category = pick(CATEGORIES, i);
    const department = pick(DEPARTMENTS, i);
    const title = pick(TITLE_TEMPLATES, i) + (i > 20 ? ` #${i}` : '');
    const estimatedValue = 15000 + (i * 8000) + Math.floor(hash(i + 1) * 50000);
    const savingsPercent = 3 + Math.floor(hash(i + 2) * 15);
    const daysFromNow = 5 + (i % 60);
    const submissionDeadline = new Date(Date.now() + daysFromNow * 24 * 60 * 60 * 1000).toISOString().slice(0, 10);
    const projectPool = [
      '01JNE4ZHT9S0VQ7E2GQW1QYJ7B',
      '01JNE4ZHTA4H0W8S8P3H7C9X2M',
      '01JNE4ZHTB0RZ3W2F6N9J5K1Q8',
      '01JNE4ZHTC7D1P6M3T8V2R0L5N',
      '01JNE4ZHTDNN6E9B4Y1S7U3P0C',
    ] as const;
    const projectId = hash(i + 200) > 0.55 ? pick([...projectPool], i + 201) : null;

    let vendorsCount: number;
    let quotesCount: number;
    if (status === 'draft') {
      vendorsCount = Math.floor(hash(i + 10) * 3);
      quotesCount = 0;
    } else if (status === 'pending') {
      vendorsCount = 2 + Math.floor(hash(i + 11) * 4);
      quotesCount = Math.floor(hash(i + 12) * 3);
    } else if (status === 'active') {
      vendorsCount = 3 + Math.floor(hash(i + 13) * 6);
      quotesCount = 2 + Math.floor(hash(i + 14) * 6);
    } else {
      vendorsCount = 4 + Math.floor(hash(i + 15) * 5);
      quotesCount = Math.min(vendorsCount, 3 + Math.floor(hash(i + 16) * 6));
    }
    quotesCount = Math.min(quotesCount, vendorsCount);

    rfqs.push({
      id,
      rfqNumber,
      title,
      status,
      projectId,
      category,
      department,
      owner,
      estimatedValue,
      savingsPercent,
      submissionDeadline,
      vendorsCount,
      quotesCount,
    });

    const vendorPool = [...VENDOR_NAMES].sort((a, b) => {
      const ha = hash(i + a.length);
      const hb = hash(i + b.length);
      return ha !== hb ? (ha < hb ? -1 : 1) : a.localeCompare(b);
    });
    const vendors: SeedVendor[] = [];
    for (let v = 0; v < vendorsCount; v++) {
      const name = vendorPool[v % vendorPool.length];
      vendors.push({
        id: `${id}-v-${v}`,
        rfqId: id,
        name,
        email: `${name.toLowerCase().replace(/\s+/g, '.')}@vendor.com`,
        status: v < quotesCount ? 'responded' : 'invited',
      });
    }
    vendorsByRfq.set(id, vendors);

    const quotes: SeedQuote[] = [];
    const quoteStatuses: SeedQuote['status'][] = ['processing', 'parsed', 'accepted', 'rejected', 'error'];
    for (let q = 0; q < quotesCount; q++) {
      const vendor = vendors[q];
      let quoteStatus: SeedQuote['status'];
      if (status === 'draft' || status === 'pending') {
        quoteStatus = pick(quoteStatuses, i + q);
      } else if (status === 'active') {
        quoteStatus = hash(i + q + 1) > 0.2 ? 'accepted' : hash(i + q + 2) > 0.5 ? 'parsed' : 'processing';
      } else {
        quoteStatus = hash(i + q + 3) > 0.08 ? 'accepted' : 'rejected';
      }
      quotes.push({
        id: `${id}-q-${q}`,
        rfqId: id,
        vendorId: vendor.id,
        vendorName: vendor.name,
        fileName: `${vendor.name.replace(/\s+/g, '_')}_Quote_${rfqNumber}.pdf`,
        status: quoteStatus,
        confidence: hash(i + q + 4) > 0.3 ? 'high' : hash(i + q + 5) > 0.5 ? 'medium' : 'low',
        uploadedAt: `${1 + (q % 5)} days ago`,
      });
    }
    quotesByRfq.set(id, quotes);

    const acceptedCount = quotes.filter((q) => q.status === 'accepted').length;
    if ((status === 'closed' || status === 'awarded' || status === 'archived') && acceptedCount >= 2) {
      const runCount = status === 'awarded' ? 2 : status === 'closed' ? 1 + Math.floor(hash(i + 20) * 2) : 1;
      const runs: SeedComparisonRun[] = [];
      for (let r = 0; r < runCount; r++) {
        const isLast = r === runCount - 1;
        const runStatus = status === 'awarded' && isLast ? 'locked' : status === 'closed' && isLast ? (hash(i + 21) > 0.5 ? 'stale' : 'generated') : 'generated';
        runs.push({
          id: `${id}-run-${r}`,
          rfqId: id,
          runId: `RUN-${String(1000 + i * 10 + r).padStart(4, '0')}`,
          date: r === 0 ? 'Today' : r === 1 ? 'Yesterday' : `${r} days ago`,
          type: r === runCount - 1 ? 'final' : 'preview',
          status: runStatus,
          scoringModel: `v2.${r % 2}`,
          createdBy: pick(OWNERS, i + r).name,
        });
      }
      runsByRfq.set(id, runs);

      if (status === 'awarded' || status === 'closed') {
        const lastRun = runs[runs.length - 1];
        const approvalStatus = status === 'awarded' ? 'approved' : hash(i + 22) > 0.4 ? 'pending' : 'approved';
        approvalsByRfq.set(id, [
          {
            id: `APR-${String(50000 + i).padStart(5, '0')}`,
            rfqId: id,
            comparisonRunId: lastRun.id,
            type: 'Comparison Approval',
            summary: 'Review comparison run and approve recommendation.',
            priority: hash(i + 23) > 0.6 ? 'high' : 'medium',
            status: approvalStatus,
            assignee: pick(OWNERS, i + 24).name,
          },
        ]);
      }
    } else if (status === 'active' && acceptedCount >= 2) {
      runsByRfq.set(id, [
        {
          id: `${id}-run-0`,
          rfqId: id,
          runId: `RUN-${String(1000 + i * 10).padStart(4, '0')}`,
          date: 'Yesterday',
          type: 'preview',
          status: 'stale',
          scoringModel: 'v2.0',
          createdBy: pick(OWNERS, i).name,
        },
      ]);
    }

    if (status === 'awarded') {
      const acceptedQuotes = quotes.filter((q) => q.status === 'accepted');
      const winner = acceptedQuotes[Math.floor(hash(i + 30) * acceptedQuotes.length)] ?? acceptedQuotes[0];
      if (winner) {
        awardByRfq.set(id, {
          rfqId: id,
          winnerVendorId: winner.vendorId,
          winnerVendorName: winner.vendorName,
          amount: Math.round(estimatedValue * (1 - savingsPercent / 100)),
          savingsPercent,
        });
      }
    }
  }

  cachedSeed = {
    rfqs,
    vendorsByRfq,
    quotesByRfq,
    runsByRfq,
    approvalsByRfq,
    awardByRfq,
  };
  return cachedSeed;
}

export function getSeedRfqs(): SeedRfq[] {
  return buildSeed().rfqs;
}

export function getSeedRfqById(id: string): SeedRfq | undefined {
  return buildSeed().rfqs.find((r) => r.id === id || r.rfqNumber === id);
}

export function getSeedVendorsByRfqId(rfqId: string): SeedVendor[] {
  return buildSeed().vendorsByRfq.get(rfqId) ?? [];
}

export function getSeedQuotesByRfqId(rfqId: string): SeedQuote[] {
  return buildSeed().quotesByRfq.get(rfqId) ?? [];
}

export function getSeedComparisonRunsByRfqId(rfqId: string): SeedComparisonRun[] {
  return buildSeed().runsByRfq.get(rfqId) ?? [];
}

export function getSeedApprovalsByRfqId(rfqId: string): SeedApproval[] {
  return buildSeed().approvalsByRfq.get(rfqId) ?? [];
}

export function getSeedAwardByRfqId(rfqId: string): SeedAward | undefined {
  return buildSeed().awardByRfq.get(rfqId);
}

/** For use-rfqs: map seed RFQ to list item shape with optional filters */
export function getSeedRfqListItems(params: {
  q?: string;
  status?: string;
  owner?: string;
  category?: string;
  page?: number;
  projectId?: string;
}): { items: Array<{
  id: string;
  title: string;
  status: RfqStatus;
  owner?: { name: string; email: string };
  deadline?: string;
  category?: string;
  estValue?: string;
  savings?: string;
  vendorsCount?: number;
  quotesCount?: number;
  projectId?: string | null;
}>; total: number } {
  const { rfqs } = buildSeed();
  let list = rfqs.map((r) => ({
    id: r.id,
    title: r.title,
    status: r.status,
    owner: r.owner,
    deadline: r.submissionDeadline,
    category: r.category,
    estValue: `$${r.estimatedValue.toLocaleString()}`,
    savings: r.savingsPercent > 0 ? `${r.savingsPercent}%` : '—',
    vendorsCount: r.vendorsCount,
    quotesCount: r.quotesCount,
    projectId: r.projectId ?? null,
  }));

  if (params.status) {
    list = list.filter((r) => r.status === params.status);
  }
  if (params.projectId) {
    list = list.filter((r) => r.projectId === params.projectId);
  }
  if (params.owner) {
    list = list.filter((r) => r.owner?.name.toLowerCase().includes((params.owner ?? '').toLowerCase()));
  }
  if (params.category) {
    list = list.filter((r) => r.category === params.category);
  }
  if (params.q) {
    const q = params.q.toLowerCase();
    list = list.filter((r) => r.title.toLowerCase().includes(q) || r.id.toLowerCase().includes(q));
  }

  const total = list.length;
  const page = Math.max(1, params.page ?? 1);
  const perPage = 20;
  const start = (page - 1) * perPage;
  const items = list.slice(start, start + perPage);

  return { items, total };
}

/** For use-rfq: map seed RFQ to detail shape */
export function getSeedRfqDetail(id: string): {
  id: string;
  title: string;
  status: RfqStatus;
  vendorsCount: number;
  quotesCount: number;
  estValue: string;
  savings: string;
} | null {
  const r = getSeedRfqById(id);
  if (!r) return null;
  return {
    id: r.id,
    title: r.title,
    status: r.status,
    vendorsCount: r.vendorsCount,
    quotesCount: r.quotesCount,
    estValue: `$${r.estimatedValue.toLocaleString()}`,
    savings: r.savingsPercent > 0 ? `${r.savingsPercent}%` : '—',
  };
}

/** All pending approvals across RFQs (for dashboard Approval Queue) */
export function getSeedPendingApprovals(): Array<SeedApproval & { sla?: string; slaVariant?: 'safe' | 'warning' | 'overdue' }> {
  const { rfqs, approvalsByRfq } = buildSeed();
  const out: Array<SeedApproval & { sla?: string; slaVariant?: 'safe' | 'warning' | 'overdue' }> = [];
  for (const rfq of rfqs) {
    const list = approvalsByRfq.get(rfq.id) ?? [];
    for (const a of list) {
      if (a.status !== 'pending') continue;
      const idx = out.length;
      out.push({
        ...a,
        sla: idx % 3 === 0 ? '2d 4h' : idx % 3 === 1 ? '6h' : 'OVERDUE',
        slaVariant: idx % 3 === 0 ? 'safe' : idx % 3 === 1 ? 'warning' : 'overdue',
      });
    }
  }
  return out;
}

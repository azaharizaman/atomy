'use client';

import { useQueries, useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { isObject, toText } from '@/hooks/normalize-utils';
import { normalizeAiNarrativePayload, type AiNarrativeSummary } from '@/hooks/use-ai-narrative-summary';

export interface VendorGovernanceEvidence {
  id: string;
  vendorId: string;
  domain: string;
  type: string;
  title: string;
  source: string;
  observedAt: string | null;
  expiresAt: string | null;
  reviewStatus: string;
  reviewedBy: string | null;
  notes: string | null;
}

export interface VendorGovernanceFinding {
  id: string;
  vendorId: string;
  domain: string;
  issueType: string;
  severity: string;
  status: string;
  openedAt: string | null;
  openedBy: string | null;
  remediationOwner: string | null;
  remediationDueAt: string | null;
  resolutionSummary: string | null;
}

export interface VendorGovernanceScores {
  esgScore: number;
  complianceHealthScore: number;
  riskWatchScore: number;
  evidenceFreshnessScore: number;
}

export interface VendorGovernanceSummary {
  vendorId: string;
  evidence: VendorGovernanceEvidence[];
  findings: VendorGovernanceFinding[];
  scores: VendorGovernanceScores;
  warningFlags: string[];
  narrative: AiNarrativeSummary | null;
}

export type VendorGovernanceMap = Map<string, VendorGovernanceSummary>;

const GOVERNANCE_WARNING_ACRONYMS = new Set(['esg']);

function pickField(item: Record<string, unknown>, ...keys: string[]): unknown {
  for (const key of keys) {
    if (key in item) {
      return item[key];
    }
  }

  return undefined;
}

function requireText(value: unknown, field: string, context: string): string {
  const text = toText(value);
  if (text === null) {
    throw new Error(`${context}: missing ${field}`);
  }

  return text;
}

function nullableText(value: unknown, field: string, context: string): string | null {
  if (value === null) {
    return null;
  }

  if (value === undefined) {
    throw new Error(`${context}: missing ${field}`);
  }

  const text = toText(value);
  if (text === null) {
    throw new Error(`${context}: missing ${field}`);
  }

  return text;
}

function requireNumber(value: unknown, field: string, context: string): number {
  if (typeof value === 'number' && Number.isFinite(value)) {
    return value;
  }

  const text = toText(value);
  if (text !== null) {
    const parsed = Number(text);
    if (Number.isFinite(parsed)) {
      return parsed;
    }
  }

  throw new Error(`${context}: ${field} must be finite`);
}

function stringList(value: unknown, field: string, context: string): string[] {
  if (!Array.isArray(value)) {
    throw new Error(`${context}: ${field} must be an array`);
  }

  return value.map((item, index) => requireText(item, `${field}.${index}`, context));
}

function normalizeEvidence(row: unknown, index: number): VendorGovernanceEvidence {
  if (!isObject(row)) {
    throw new Error(`Invalid vendor governance evidence at index ${index}: expected object`);
  }

  const context = `Invalid vendor governance evidence at index ${index}`;

  return {
    id: requireText(pickField(row, 'id'), 'id', context),
    vendorId: requireText(pickField(row, 'vendor_id', 'vendorId'), 'vendor_id', context),
    domain: requireText(pickField(row, 'domain'), 'domain', context),
    type: requireText(pickField(row, 'type'), 'type', context),
    title: requireText(pickField(row, 'title'), 'title', context),
    source: requireText(pickField(row, 'source'), 'source', context),
    observedAt: nullableText(pickField(row, 'observed_at', 'observedAt'), 'observed_at', context),
    expiresAt: nullableText(pickField(row, 'expires_at', 'expiresAt'), 'expires_at', context),
    reviewStatus: requireText(pickField(row, 'review_status', 'reviewStatus'), 'review_status', context),
    reviewedBy: nullableText(pickField(row, 'reviewed_by', 'reviewedBy'), 'reviewed_by', context),
    notes: nullableText(pickField(row, 'notes'), 'notes', context),
  };
}

function normalizeFinding(row: unknown, index: number): VendorGovernanceFinding {
  if (!isObject(row)) {
    throw new Error(`Invalid vendor governance finding at index ${index}: expected object`);
  }

  const context = `Invalid vendor governance finding at index ${index}`;

  return {
    id: requireText(pickField(row, 'id'), 'id', context),
    vendorId: requireText(pickField(row, 'vendor_id', 'vendorId'), 'vendor_id', context),
    domain: requireText(pickField(row, 'domain'), 'domain', context),
    issueType: requireText(pickField(row, 'issue_type', 'issueType'), 'issue_type', context),
    severity: requireText(pickField(row, 'severity'), 'severity', context),
    status: requireText(pickField(row, 'status'), 'status', context),
    openedAt: nullableText(pickField(row, 'opened_at', 'openedAt'), 'opened_at', context),
    openedBy: nullableText(pickField(row, 'opened_by', 'openedBy'), 'opened_by', context),
    remediationOwner: nullableText(
      pickField(row, 'remediation_owner', 'remediationOwner'),
      'remediation_owner',
      context,
    ),
    remediationDueAt: nullableText(
      pickField(row, 'remediation_due_at', 'remediationDueAt'),
      'remediation_due_at',
      context,
    ),
    resolutionSummary: nullableText(
      pickField(row, 'resolution_summary', 'resolutionSummary'),
      'resolution_summary',
      context,
    ),
  };
}

export function formatVendorGovernanceWarning(flag: string): string {
  return flag
    .split('_')
    .map((part) => {
      const normalized = part.toLowerCase();
      if (GOVERNANCE_WARNING_ACRONYMS.has(normalized)) {
        return normalized.toUpperCase();
      }

      return normalized.charAt(0).toUpperCase() + normalized.slice(1);
    })
    .join(' ');
}

export function normalizeVendorGovernancePayload(payload: unknown): VendorGovernanceSummary {
  const envelope = isObject(payload) ? payload : null;
  const data = envelope !== null && isObject(envelope.data) ? envelope.data : null;
  if (data === null) {
    throw new Error('Invalid vendor governance payload: expected object envelope with data object.');
  }

  const evidence = pickField(data, 'evidence');
  const findings = pickField(data, 'findings');
  const scores = pickField(data, 'summary_scores', 'summaryScores');
  const warningFlags = pickField(data, 'warning_flags', 'warningFlags');
  if (!Array.isArray(evidence) || !Array.isArray(findings) || !isObject(scores) || !Array.isArray(warningFlags)) {
    throw new Error('Invalid vendor governance payload: expected evidence, findings, summary_scores, and warning_flags.');
  }

  const aiNarrativeField = pickField(data, 'ai_narrative', 'aiNarrative');
  let narrative = null;
  if (isObject(aiNarrativeField) && (typeof aiNarrativeField.feature_key === 'string' || typeof aiNarrativeField.featureKey === 'string')) {
    try {
      narrative = normalizeAiNarrativePayload(aiNarrativeField);
    } catch {
      narrative = null;
    }
  }

  return {
    vendorId: requireText(pickField(data, 'vendor_id', 'vendorId'), 'vendor_id', 'Invalid vendor governance payload'),
    evidence: evidence.map((row, index) => normalizeEvidence(row, index)),
    findings: findings.map((row, index) => normalizeFinding(row, index)),
    scores: {
      esgScore: requireNumber(pickField(scores, 'esg_score', 'esgScore'), 'esg_score', 'Invalid vendor governance payload'),
      complianceHealthScore: requireNumber(
        pickField(scores, 'compliance_health_score', 'complianceHealthScore'),
        'compliance_health_score',
        'Invalid vendor governance payload',
      ),
      riskWatchScore: requireNumber(
        pickField(scores, 'risk_watch_score', 'riskWatchScore'),
        'risk_watch_score',
        'Invalid vendor governance payload',
      ),
      evidenceFreshnessScore: requireNumber(
        pickField(scores, 'evidence_freshness_score', 'evidenceFreshnessScore'),
        'evidence_freshness_score',
        'Invalid vendor governance payload',
      ),
    },
    warningFlags: stringList(warningFlags, 'warning_flags', 'Invalid vendor governance payload'),
    narrative,
  };
}

async function fetchVendorGovernance(vendorId: string): Promise<VendorGovernanceSummary> {
  const response = await api.get(`/vendors/${encodeURIComponent(vendorId)}/governance`);

  return normalizeVendorGovernancePayload(response.data);
}

export function useVendorGovernance(vendorId: string) {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';
  const normalizedVendorId = vendorId.trim();

  return useQuery({
    queryKey: ['vendors', normalizedVendorId, 'governance'],
    enabled: !useMocks && normalizedVendorId !== '',
    queryFn: () => fetchVendorGovernance(normalizedVendorId),
  });
}

export function useVendorGovernanceMap(vendorIds: string[]) {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';
  const uniqueVendorIds = Array.from(new Set(vendorIds.map((id) => id.trim()).filter(Boolean))).sort();
  const results = useQueries({
    queries: uniqueVendorIds.map((vendorId) => ({
      queryKey: ['vendors', vendorId, 'governance'],
      enabled: !useMocks,
      queryFn: () => fetchVendorGovernance(vendorId),
    })),
  });

  const data: VendorGovernanceMap = new Map();
  let isLoading = false;
  let isError = false;
  let error: unknown = null;

  results.forEach((result, index) => {
    isLoading = isLoading || result.isLoading;
    isError = isError || result.isError;
    error ??= result.error;
    if (result.data) {
      data.set(uniqueVendorIds[index], result.data);
    }
  });

  return { data, isLoading, isError, error };
}

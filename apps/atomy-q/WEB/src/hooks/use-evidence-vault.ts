'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { api } from '@/lib/api';
import { fetchLiveOrFail } from '@/lib/api-live';
import { isObject, toText, unwrapResponse } from '@/hooks/normalize-utils';

export interface EvidenceVaultSummary {
  rfq: { id: string; title: string | null; rfq_number: string | null };
  award_pack: {
    status: 'not_ready' | 'draft_ready' | 'finalized' | 'superseded';
    bundle_id: string | null;
    version: number | null;
    finalized_at: string | null;
    checksum: string | null;
  };
  readiness: { ready: boolean; blockers: Array<{ code: string; message: string }> };
  timeline: Array<{ code: string; label: string; status: string; occurred_at: string | null }>;
  sections: Array<{ code: string; label: string; status: string; items: unknown[] }>;
  actions: { can_finalize: boolean; can_export: boolean; can_upload_supporting_evidence: boolean };
}

export interface SupportingEvidenceUploadInput {
  file: File;
  reason: string;
  vendor_id?: string | null;
  quote_submission_id?: string | null;
  award_id?: string | null;
}

export interface SupportingEvidenceResult {
  id: string;
  rfq_id: string;
  reason: string;
  original_filename: string;
  file_type: string | null;
  storage_path: string;
  checksum: string;
  uploaded_by: string;
  uploaded_at: string | null;
}

export interface EvidenceBundleResult {
  id: string;
  rfq_id: string;
  type: string;
  status: string;
  version: number | null;
  checksum: string | null;
  finalized_at: string | null;
  manifest: unknown;
}

export interface EvidenceVaultExportResult {
  bundle_id: string;
  checksum: string | null;
  manifest: unknown;
}

const AWARD_PACK_STATUSES = new Set(['not_ready', 'draft_ready', 'finalized', 'superseded']);

function requiredText(value: unknown, field: string): string {
  const text = toText(value);
  if (text === null) {
    throw new Error(`Invalid evidence vault response: missing ${field}.`);
  }

  return text;
}

function optionalText(value: unknown, field: string): string | null {
  if (value === undefined || value === null) {
    return null;
  }

  if (typeof value !== 'string' || value.trim() === '') {
    throw new Error(`Invalid evidence vault response: ${field} must be a non-empty string or null.`);
  }

  return value;
}

function optionalNumber(value: unknown, field: string): number | null {
  if (value === null || value === undefined) {
    return null;
  }

  if (typeof value !== 'number' && typeof value !== 'string') {
    throw new Error(`Invalid evidence vault response: ${field} must be numeric.`);
  }

  if (typeof value === 'string' && !/^[+-]?(?:\d+|\d+\.\d+|\.\d+)$/.test(value.trim())) {
    throw new Error(`Invalid evidence vault response: ${field} must be numeric.`);
  }

  const number = typeof value === 'number' ? value : Number(value.trim());
  if (!Number.isFinite(number)) {
    throw new Error(`Invalid evidence vault response: ${field} must be numeric.`);
  }

  return number;
}

function requireObject(value: unknown, field: string): Record<string, unknown> {
  if (!isObject(value)) {
    throw new Error(`Invalid evidence vault response: ${field} must be an object.`);
  }

  return value;
}

function normalizeBlocker(value: unknown, index: number): { code: string; message: string } {
  const row = requireObject(value, `readiness.blockers[${index}]`);

  return {
    code: requiredText(row.code, `readiness.blockers[${index}].code`),
    message: requiredText(row.message, `readiness.blockers[${index}].message`),
  };
}

function normalizeTimelineItem(
  value: unknown,
  index: number,
): { code: string; label: string; status: string; occurred_at: string | null } {
  const row = requireObject(value, `timeline[${index}]`);

  return {
    code: requiredText(row.code, `timeline[${index}].code`),
    label: requiredText(row.label, `timeline[${index}].label`),
    status: requiredText(row.status, `timeline[${index}].status`),
    occurred_at: optionalText(row.occurred_at, `timeline[${index}].occurred_at`),
  };
}

function normalizeSection(value: unknown, index: number): EvidenceVaultSummary['sections'][number] {
  const row = requireObject(value, `sections[${index}]`);
  if (!Array.isArray(row.items)) {
    throw new Error(`Invalid evidence vault response: sections[${index}].items must be an array.`);
  }

  return {
    code: requiredText(row.code, `sections[${index}].code`),
    label: requiredText(row.label, `sections[${index}].label`),
    status: requiredText(row.status, `sections[${index}].status`),
    items: row.items,
  };
}

function normalizeEvidenceVaultSummary(payload: unknown): EvidenceVaultSummary {
  const data = unwrapResponse(payload);
  const row = requireObject(data, 'data');
  const rfq = requireObject(row.rfq, 'rfq');
  const awardPack = requireObject(row.award_pack, 'award_pack');
  const readiness = requireObject(row.readiness, 'readiness');
  const actions = requireObject(row.actions, 'actions');

  const status = requiredText(awardPack.status, 'award_pack.status');
  if (!AWARD_PACK_STATUSES.has(status)) {
    throw new Error(`Invalid evidence vault response: unknown award_pack.status "${status}".`);
  }

  if (!Array.isArray(readiness.blockers)) {
    throw new Error('Invalid evidence vault response: readiness.blockers must be an array.');
  }
  if (!Array.isArray(row.timeline)) {
    throw new Error('Invalid evidence vault response: timeline must be an array.');
  }
  if (!Array.isArray(row.sections)) {
    throw new Error('Invalid evidence vault response: sections must be an array.');
  }

  return {
    rfq: {
      id: requiredText(rfq.id, 'rfq.id'),
      title: optionalText(rfq.title, 'rfq.title'),
      rfq_number: optionalText(rfq.rfq_number, 'rfq.rfq_number'),
    },
    award_pack: {
      status: status as EvidenceVaultSummary['award_pack']['status'],
      bundle_id: optionalText(awardPack.bundle_id, 'award_pack.bundle_id'),
      version: optionalNumber(awardPack.version, 'award_pack.version'),
      finalized_at: optionalText(awardPack.finalized_at, 'award_pack.finalized_at'),
      checksum: optionalText(awardPack.checksum, 'award_pack.checksum'),
    },
    readiness: {
      ready: readiness.ready === true,
      blockers: readiness.blockers.map(normalizeBlocker),
    },
    timeline: row.timeline.map(normalizeTimelineItem),
    sections: row.sections.map(normalizeSection),
    actions: {
      can_finalize: actions.can_finalize === true,
      can_export: actions.can_export === true,
      can_upload_supporting_evidence: actions.can_upload_supporting_evidence === true,
    },
  };
}

function normalizeSupportingEvidence(payload: unknown): SupportingEvidenceResult {
  const row = requireObject(unwrapResponse(payload), 'data');

  return {
    id: requiredText(row.id, 'id'),
    rfq_id: requiredText(row.rfq_id, 'rfq_id'),
    reason: requiredText(row.reason, 'reason'),
    original_filename: requiredText(row.original_filename, 'original_filename'),
    file_type: optionalText(row.file_type, 'file_type'),
    storage_path: requiredText(row.storage_path, 'storage_path'),
    checksum: requiredText(row.checksum, 'checksum'),
    uploaded_by: requiredText(row.uploaded_by, 'uploaded_by'),
    uploaded_at: optionalText(row.uploaded_at, 'uploaded_at'),
  };
}

function normalizeEvidenceBundle(payload: unknown): EvidenceBundleResult {
  const row = requireObject(unwrapResponse(payload), 'data');

  return {
    id: requiredText(row.id, 'id'),
    rfq_id: requiredText(row.rfq_id, 'rfq_id'),
    type: requiredText(row.type, 'type'),
    status: requiredText(row.status, 'status'),
    version: optionalNumber(row.version, 'version'),
    checksum: optionalText(row.checksum, 'checksum'),
    finalized_at: optionalText(row.finalized_at, 'finalized_at'),
    manifest: row.manifest ?? null,
  };
}

function normalizeEvidenceVaultExport(payload: unknown): EvidenceVaultExportResult {
  const row = requireObject(unwrapResponse(payload), 'data');

  return {
    bundle_id: requiredText(row.bundle_id, 'bundle_id'),
    checksum: optionalText(row.checksum, 'checksum'),
    manifest: row.manifest ?? null,
  };
}

function evidenceVaultQueryKey(rfqId: string) {
  return ['evidence-vault', rfqId] as const;
}

function appendOptional(formData: FormData, key: string, value: string | null | undefined): void {
  if (value !== null && value !== undefined && value.trim() !== '') {
    formData.append(key, value);
  }
}

export function useEvidenceVault(rfqId: string, options?: { enabled?: boolean }) {
  const enabled = (options?.enabled ?? true) && Boolean(rfqId);

  return useQuery({
    queryKey: evidenceVaultQueryKey(rfqId),
    queryFn: async (): Promise<EvidenceVaultSummary> => {
      const data = await fetchLiveOrFail<unknown>(`/rfqs/${encodeURIComponent(rfqId)}/evidence-vault`);

      if (data === undefined) {
        throw new Error(`Evidence vault summary unavailable for RFQ "${rfqId}".`);
      }

      return normalizeEvidenceVaultSummary(data);
    },
    enabled,
  });
}

export function useEvidenceVaultMutations(rfqId: string) {
  const queryClient = useQueryClient();

  function invalidateEvidenceVault(): void {
    queryClient.invalidateQueries({ queryKey: evidenceVaultQueryKey(rfqId) });
  }

  const uploadSupportingEvidence = useMutation({
    mutationFn: async (input: SupportingEvidenceUploadInput): Promise<SupportingEvidenceResult> => {
      const formData = new FormData();
      formData.append('file', input.file);
      formData.append('reason', input.reason);
      appendOptional(formData, 'vendor_id', input.vendor_id);
      appendOptional(formData, 'quote_submission_id', input.quote_submission_id);
      appendOptional(formData, 'award_id', input.award_id);

      const { data } = await api.post(`/rfqs/${encodeURIComponent(rfqId)}/evidence-vault/supporting-evidence`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });

      return normalizeSupportingEvidence(data);
    },
    onSuccess: invalidateEvidenceVault,
  });

  const finalizeAwardPack = useMutation({
    mutationFn: async (): Promise<EvidenceBundleResult> => {
      const { data } = await api.post(`/rfqs/${encodeURIComponent(rfqId)}/evidence-vault/award-pack/finalize`);
      return normalizeEvidenceBundle(data);
    },
    onSuccess: invalidateEvidenceVault,
  });

  const exportAwardPack = useMutation({
    mutationFn: async (): Promise<EvidenceVaultExportResult> => {
      const data = await fetchLiveOrFail<unknown>(`/rfqs/${encodeURIComponent(rfqId)}/evidence-vault/award-pack/export`);

      if (data === undefined) {
        throw new Error(`Evidence vault export unavailable for RFQ "${rfqId}".`);
      }

      return normalizeEvidenceVaultExport(data);
    },
  });

  return {
    uploadSupportingEvidence,
    finalizeAwardPack,
    exportAwardPack,
  };
}

export { evidenceVaultQueryKey, normalizeEvidenceVaultSummary };

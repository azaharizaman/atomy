'use client';

import { isObject, toText } from '@/hooks/normalize-utils';

export type AiRequestState = 'ready' | 'error';
export type AiMode = 'off' | 'provider' | 'deterministic' | 'unknown';
export type AiCapabilityFallbackUiMode = 'hide' | 'message' | 'disable' | 'unknown';

export interface AiCapabilityDefinition {
  key: string;
  capabilityGroup: string | null;
  requiresAi: boolean;
  hasManualFallback: boolean;
  fallbackUiMode: AiCapabilityFallbackUiMode;
  messageKey: string | null;
  operatorCritical: boolean;
  endpointGroup: string | null;
}

export interface AiEndpointGroupStatus {
  key: string;
  health: string;
  checkedAt: string | null;
  latencyMs: number | null;
  reasonCodes: string[];
  diagnostics: Record<string, unknown> | null;
}

export interface AiCapabilityStatus {
  key: string;
  status: string;
  available: boolean;
  fallbackUiMode: AiCapabilityFallbackUiMode;
  messageKey: string | null;
  operatorCritical: boolean;
  reasonCodes: string[];
  diagnostics: Record<string, unknown> | null;
}

export interface AiStatusSnapshot {
  requestState: AiRequestState;
  mode: AiMode;
  globalHealth: string;
  reasonCodes: string[];
  generatedAt: string | null;
  providerName: string | null;
  capabilityDefinitions: Record<string, AiCapabilityDefinition>;
  capabilityStatuses: Record<string, AiCapabilityStatus>;
  endpointGroups: Record<string, AiEndpointGroupStatus>;
}

function requireObject(value: unknown, context: string): Record<string, unknown> {
  if (!isObject(value)) {
    throw new Error(`${context}: expected object`);
  }

  return value;
}

function requireBoolean(value: unknown, field: string, context: string): boolean {
  if (typeof value !== 'boolean') {
    throw new Error(`${context}: ${field} must be boolean`);
  }

  return value;
}

function requireStringArray(value: unknown, field: string, context: string): string[] {
  if (!Array.isArray(value)) {
    throw new Error(`${context}: ${field} must be an array`);
  }

  return value.map((entry, index) => {
    const text = toText(entry);
    if (text === null) {
      throw new Error(`${context}: ${field}.${index} must be text`);
    }

    return text;
  });
}

function requireText(value: unknown, field: string, context: string): string {
  const text = toText(value);
  if (text === null) {
    throw new Error(`${context}: missing ${field}`);
  }

  return text;
}

function optionalText(value: unknown): string | null {
  return toText(value);
}

function normalizeMode(value: unknown): AiMode {
  const text = toText(value);
  if (text === 'off' || text === 'provider' || text === 'deterministic') {
    return text;
  }

  return 'unknown';
}

function normalizeFallbackUiMode(value: unknown): AiCapabilityFallbackUiMode {
  const text = toText(value)?.toLowerCase();

  if (text === 'hide' || text === 'hide_controls' || text === 'hidden' || text === 'hide_ai_controls') {
    return 'hide';
  }

  if (
    text === 'message'
    || text === 'show_message'
    || text === 'callout'
    || text === 'unavailable_message'
    || text === 'show_unavailable_message'
    || text === 'show_manual_continuity_banner'
  ) {
    return 'message';
  }

  if (text === 'disable' || text === 'disabled' || text === 'disable_controls') {
    return 'disable';
  }

  return 'unknown';
}

function normalizeCapabilityDefinition(key: string, value: unknown): AiCapabilityDefinition {
  const raw = requireObject(value, `Invalid AI capability definition "${key}"`);

  return {
    key,
    capabilityGroup: optionalText(raw.capability_group ?? raw.capabilityGroup),
    requiresAi: requireBoolean(raw.requires_ai ?? raw.requiresAi, 'requires_ai', `Invalid AI capability definition "${key}"`),
    hasManualFallback: requireBoolean(
      raw.has_manual_fallback ?? raw.hasManualFallback,
      'has_manual_fallback',
      `Invalid AI capability definition "${key}"`,
    ),
    fallbackUiMode: normalizeFallbackUiMode(raw.fallback_ui_mode ?? raw.fallbackUiMode),
    messageKey: optionalText(raw.degradation_message_key ?? raw.degradationMessageKey),
    operatorCritical: requireBoolean(
      raw.operator_critical ?? raw.operatorCritical,
      'operator_critical',
      `Invalid AI capability definition "${key}"`,
    ),
    endpointGroup: optionalText(raw.endpoint_group ?? raw.endpointGroup),
  };
}

function normalizeCapabilityStatus(key: string, value: unknown): AiCapabilityStatus {
  const context = `Invalid AI capability status "${key}"`;
  const raw = requireObject(value, context);
  const diagnostics = raw.diagnostics;

  return {
    key,
    status: requireText(raw.status, 'status', context),
    available: requireBoolean(raw.available, 'available', context),
    fallbackUiMode: normalizeFallbackUiMode(raw.fallback_ui_mode ?? raw.fallbackUiMode),
    messageKey: optionalText(raw.message_key ?? raw.messageKey),
    operatorCritical: requireBoolean(raw.operator_critical ?? raw.operatorCritical, 'operator_critical', context),
    reasonCodes: requireStringArray(raw.reason_codes ?? raw.reasonCodes ?? [], 'reason_codes', context),
    diagnostics: isObject(diagnostics) ? diagnostics : null,
  };
}

function normalizeEndpointGroupStatus(key: string, value: unknown): AiEndpointGroupStatus {
  const context = `Invalid AI endpoint group "${key}"`;
  const raw = requireObject(value, context);
  const diagnostics = raw.diagnostics;
  const latency = raw.latency_ms ?? raw.latencyMs;

  return {
    key,
    health: requireText(raw.health, 'health', context),
    checkedAt: optionalText(raw.checked_at ?? raw.checkedAt),
    latencyMs: typeof latency === 'number' && Number.isFinite(latency) ? latency : null,
    reasonCodes: requireStringArray(raw.reason_codes ?? raw.reasonCodes ?? [], 'reason_codes', context),
    diagnostics: isObject(diagnostics) ? diagnostics : null,
  };
}

function mapRecord<T>(
  value: unknown,
  context: string,
  mapEntry: (key: string, entry: unknown) => T,
): Record<string, T> {
  const raw = requireObject(value, context);

  return Object.fromEntries(Object.entries(raw).map(([key, entry]) => [key, mapEntry(key, entry)]));
}

function mapCollection<T>(
  value: unknown,
  context: string,
  keyField: string,
  mapEntry: (key: string, entry: unknown) => T,
): Record<string, T> {
  if (Array.isArray(value)) {
    return Object.fromEntries(
      value.map((entry, index) => {
        const raw = requireObject(entry, `${context}.${index}`);
        const key = requireText(raw[keyField], keyField, `${context}.${index}`);

        return [key, mapEntry(key, raw)] as const;
      }),
    );
  }

  return mapRecord(value, context, mapEntry);
}

function getProviderName(): string | null {
  return optionalText(process.env.NEXT_PUBLIC_AI_PROVIDER_NAME);
}

export function createAiStatusFallback(
  kind: 'off' | 'error' | 'unknown',
  overrides?: Partial<Pick<AiStatusSnapshot, 'mode' | 'globalHealth' | 'reasonCodes' | 'generatedAt'>>,
): AiStatusSnapshot {
  return {
    requestState: kind === 'error' ? 'error' : 'ready',
    mode: overrides?.mode ?? (kind === 'off' ? 'off' : 'unknown'),
    globalHealth:
      overrides?.globalHealth ?? (kind === 'off' ? 'disabled' : kind === 'unknown' ? 'unknown' : 'unknown'),
    reasonCodes: overrides?.reasonCodes ?? [],
    generatedAt: overrides?.generatedAt ?? null,
    providerName: getProviderName(),
    capabilityDefinitions: {},
    capabilityStatuses: {},
    endpointGroups: {},
  };
}

export function createMockAiStatusSnapshot(): AiStatusSnapshot {
  return {
    requestState: 'ready',
    mode: 'deterministic',
    globalHealth: 'degraded',
    reasonCodes: ['AI_STATUS_MOCK_MODE'],
    generatedAt: null,
    providerName: getProviderName(),
    capabilityDefinitions: {},
    capabilityStatuses: {},
    endpointGroups: {},
  };
}

export function createDeterministicAiStatusSnapshot(): AiStatusSnapshot {
  return {
    requestState: 'ready',
    mode: 'deterministic',
    globalHealth: 'degraded',
    reasonCodes: ['deterministic_fallback_mode'],
    generatedAt: null,
    providerName: getProviderName(),
    capabilityDefinitions: {},
    capabilityStatuses: {},
    endpointGroups: {},
  };
}

export function normalizeAiStatusPayload(payload: unknown): AiStatusSnapshot {
  const envelope = requireObject(payload, 'Invalid AI status payload');
  const data = requireObject(envelope.data, 'Invalid AI status payload');

  return {
    requestState: 'ready',
    mode: normalizeMode(data.mode),
    globalHealth: requireText(data.global_health ?? data.globalHealth, 'global_health', 'Invalid AI status payload'),
    reasonCodes: requireStringArray(data.reason_codes ?? data.reasonCodes ?? [], 'reason_codes', 'Invalid AI status payload'),
    generatedAt: optionalText(data.generated_at ?? data.generatedAt),
    providerName: getProviderName(),
    capabilityDefinitions: mapCollection(
      data.capability_definitions ?? data.capabilityDefinitions ?? {},
      'Invalid AI status payload: capability_definitions',
      'feature_key',
      normalizeCapabilityDefinition,
    ),
    capabilityStatuses: mapRecord(
      data.capability_statuses ?? data.capabilityStatuses ?? {},
      'Invalid AI status payload: capability_statuses',
      normalizeCapabilityStatus,
    ),
    endpointGroups: mapCollection(
      data.endpoint_groups ?? data.endpointGroups ?? {},
      'Invalid AI status payload: endpoint_groups',
      'endpoint_group',
      normalizeEndpointGroupStatus,
    ),
  };
}

export function resolveAiStatusPath(): string {
  const configured = toText(process.env.NEXT_PUBLIC_AI_STATUS_PATH) ?? '/ai/status';

  if (/^https?:\/\//i.test(configured)) {
    return configured;
  }

  const normalized = configured.startsWith('/') ? configured : `/${configured}`;

  if (normalized === '/api/v1') {
    return '/';
  }

  if (normalized.startsWith('/api/v1/')) {
    return normalized.slice('/api/v1'.length);
  }

  return normalized;
}

export function isAiQueryEnabled(): boolean {
  if (process.env.NEXT_PUBLIC_USE_MOCKS === 'true') {
    return false;
  }

  return normalizeMode(process.env.NEXT_PUBLIC_AI_MODE) === 'provider';
}

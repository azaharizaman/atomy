'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { isObject, toText } from '@/hooks/normalize-utils';
import {
  userIndex,
  userInvite,
  userReactivate,
  userRoles,
  userSuspend,
} from '@/generated/api/sdk.gen';
import type { UserInviteData, UserRolesResponse } from '@/generated/api/types.gen';

export interface SettingsUserRow {
  id: string;
  name: string | null;
  email: string;
  status: string;
  role: string;
  createdAt: string | null;
  lastLoginAt: string | null;
}

export interface SettingsUsersResult {
  items: SettingsUserRow[];
  meta: {
    currentPage: number;
    perPage: number;
    total: number;
  };
}

export interface SettingsUserRole {
  id: string;
  name: string;
  description: string | null;
  tenantId: string | null;
  isSystemRole: boolean;
}

type InviteUserPayload = UserInviteData['body'];

const settingsUsersQueryKey = ['settings-users'] as const;
const settingsUserRolesQueryKey = ['settings-user-roles'] as const;

function requireText(value: unknown, field: string, index: number): string {
  const text = toText(value);
  if (text === null) {
    throw new Error(`Invalid user row at index ${index}: missing ${field}`);
  }

  return text;
}

function requireEnvelope(payload: unknown, message: string): Record<string, unknown> {
  if (!isObject(payload)) {
    throw new Error(message);
  }

  return payload;
}

function normalizeOptionalText(value: unknown): string | null {
  return toText(value);
}

function normalizeUserRow(row: unknown, index: number): SettingsUserRow {
  const item = requireEnvelope(row, `Invalid user row at index ${index}: expected object`);

  return {
    id: requireText(item.id, 'id', index),
    name: normalizeOptionalText(item.name),
    email: requireText(item.email, 'email', index),
    status: requireText(item.status, 'status', index),
    role: requireText(item.role, 'role', index),
    createdAt: normalizeOptionalText(item.created_at ?? item.createdAt),
    lastLoginAt: normalizeOptionalText(item.last_login_at ?? item.lastLoginAt),
  };
}

function requireFiniteNumber(value: unknown, field: string): number {
  if (value === null || value === undefined) {
    throw new Error(`Invalid users payload: missing ${field}`);
  }
  if (typeof value === 'string' && value.trim() === '') {
    throw new Error(`Invalid users payload: missing ${field}`);
  }

  const numberValue = typeof value === 'number' ? value : Number(value);
  if (!Number.isFinite(numberValue)) {
    throw new Error(`Invalid users payload: ${field} must be a finite number`);
  }

  return numberValue;
}

function normalizeUsersResponse(payload: unknown): SettingsUsersResult {
  const envelope = requireEnvelope(payload, 'Invalid users payload: expected object envelope with data array.');
  if (!Array.isArray(envelope.data)) {
    throw new Error('Invalid users payload: expected data array.');
  }

  const meta = requireEnvelope(envelope.meta, 'Invalid users payload: expected meta object.');

  return {
    items: envelope.data.map((row: unknown, index: number) => normalizeUserRow(row, index)),
    meta: {
      currentPage: requireFiniteNumber(meta.current_page, 'current_page'),
      perPage: requireFiniteNumber(meta.per_page, 'per_page'),
      total: requireFiniteNumber(meta.total, 'total'),
    },
  };
}

function normalizeRolesResponse(payload: UserRolesResponse['data']): SettingsUserRole[] {
  const isTrueValue = (value: unknown): boolean => value === true || value === 'true';

  return payload.data.map((role, index: number) => {
    const id = toText(role.id);
    const name = toText(role.name);
    if (id === null) {
      throw new Error(`Invalid user role at index ${index}: missing id`);
    }
    if (name === null) {
      throw new Error(`Invalid user role at index ${index}: missing name`);
    }

    return {
      id,
      name,
      description: normalizeOptionalText(role.description),
      tenantId: normalizeOptionalText(role.tenant_id),
      isSystemRole: isTrueValue(role.is_system_role),
    };
  });
}

function normalizeSingleUserResponse(payload: unknown): SettingsUserRow {
  const envelope = requireEnvelope(payload, 'Invalid user payload: expected object envelope.');
  if (!('data' in envelope) || !isObject(envelope.data)) {
    throw new Error('Invalid user payload: expected non-null data object.');
  }

  return normalizeUserRow(envelope.data, 0);
}

function extractResponseErrorMessage(error: unknown): string {
  if (error instanceof Error) {
    const message = error.message.trim();
    if (message !== '') {
      return message;
    }
  }

  if (typeof error === 'string') {
    const message = error.trim();
    if (message !== '') {
      return message;
    }
  }

  if (isObject(error)) {
    const message = toText(error.message ?? error.error ?? error.detail ?? error.title);
    if (message !== null && message.trim() !== '') {
      return message.trim();
    }
  }

  try {
    return JSON.stringify(error) ?? 'Request failed.';
  } catch {
    return 'Request failed.';
  }
}

function requireSuccessfulResponse<TResponse extends { data: unknown; error?: unknown }>(
  response: TResponse | undefined,
  missingResponseMessage: string,
): TResponse {
  if (response === undefined) {
    throw new Error(missingResponseMessage);
  }

  if ('error' in response && response.error !== undefined) {
    throw new Error(extractResponseErrorMessage(response.error));
  }

  return response;
}

async function invokeUserInvite(payload: InviteUserPayload) {
  return userInvite({ body: payload });
}

export function useUsers() {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  return useQuery({
    queryKey: settingsUsersQueryKey,
    enabled: !useMocks,
    queryFn: async (): Promise<SettingsUsersResult> => {
      const response = requireSuccessfulResponse(await userIndex(), 'Invalid users payload: missing response.');
      return normalizeUsersResponse(response.data);
    },
  });
}

export function useUserRoles() {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  return useQuery({
    queryKey: settingsUserRolesQueryKey,
    enabled: !useMocks,
    queryFn: async (): Promise<SettingsUserRole[]> => {
      const response = requireSuccessfulResponse(await userRoles(), 'Invalid user roles payload: missing response.');
      return normalizeRolesResponse(response.data);
    },
  });
}

export function useInviteUser() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (payload: InviteUserPayload): Promise<SettingsUserRow> => {
      const response = requireSuccessfulResponse(await invokeUserInvite(payload), 'Invalid user payload: missing response.');
      return normalizeSingleUserResponse(response.data);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: settingsUsersQueryKey });
    },
  });
}

export function useSuspendUser() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (userId: string): Promise<SettingsUserRow> => {
      const response = requireSuccessfulResponse(await userSuspend({ path: { id: userId } }), 'Invalid user payload: missing response.');
      return normalizeSingleUserResponse(response.data);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: settingsUsersQueryKey });
    },
  });
}

export function useReactivateUser() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (userId: string): Promise<SettingsUserRow> => {
      const response = requireSuccessfulResponse(await userReactivate({ path: { id: userId } }), 'Invalid user payload: missing response.');
      return normalizeSingleUserResponse(response.data);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: settingsUsersQueryKey });
    },
  });
}

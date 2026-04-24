import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { api, requiresIdempotencyKey } from './api';

vi.mock('../store/use-auth-store', () => ({
  useAuthStore: {
    getState: vi.fn(() => ({
      token: undefined as string | undefined,
      refreshToken: undefined,
      user: null,
      logout: vi.fn(),
      setTokens: vi.fn(),
      login: vi.fn(),
    })),
  },
}));

describe('requiresIdempotencyKey', () => {
  it('matches Phase 1 idempotent paths (relative to API root)', () => {
    expect(requiresIdempotencyKey('rfqs')).toBe(true);
    expect(requiresIdempotencyKey('rfqs/bulk-action')).toBe(true);
    expect(requiresIdempotencyKey('rfqs/x/duplicate')).toBe(true);
    expect(requiresIdempotencyKey('rfqs/x/invitations')).toBe(true);
    expect(requiresIdempotencyKey('rfqs/x/invitations/y/remind')).toBe(true);
    expect(requiresIdempotencyKey('rfq-templates')).toBe(true);
    expect(requiresIdempotencyKey('rfq-templates/t/duplicate')).toBe(true);
    expect(requiresIdempotencyKey('rfq-templates/t/apply')).toBe(true);
    expect(requiresIdempotencyKey('quote-submissions/q1/source-lines')).toBe(true);
    expect(requiresIdempotencyKey('projects')).toBe(true);
    expect(requiresIdempotencyKey('tasks')).toBe(true);
  });

  it('returns false for non-idempotent paths', () => {
    expect(requiresIdempotencyKey('rfqs/abc')).toBe(false);
    expect(requiresIdempotencyKey('auth/login')).toBe(false);
    expect(requiresIdempotencyKey('me')).toBe(false);
  });

  it('strips query string and leading slashes', () => {
    expect(requiresIdempotencyKey('/rfqs?foo=1')).toBe(true);
    expect(requiresIdempotencyKey('//tasks')).toBe(true);
  });
});

describe('api idempotency request interceptor', () => {
  const uuid = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';

  beforeEach(() => {
    vi.stubGlobal('crypto', { randomUUID: () => uuid });
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('sets Idempotency-Key on POST to idempotent paths when missing', async () => {
    await api.request({
      method: 'post',
      url: 'projects',
      data: {},
      adapter: async (config) => {
        const h = config.headers as Record<string, string>;
        expect(h['Idempotency-Key']).toBe(uuid);
        return {
          data: {},
          status: 200,
          statusText: 'OK',
          headers: {},
          config,
        };
      },
    });
  });

  it('does not overwrite an existing Idempotency-Key header', async () => {
    await api.request({
      method: 'post',
      url: 'tasks',
      headers: { 'Idempotency-Key': 'client-provided' },
      data: {},
      adapter: async (config) => {
        const h = config.headers as Record<string, string>;
        expect(h['Idempotency-Key']).toBe('client-provided');
        return {
          data: {},
          status: 200,
          statusText: 'OK',
          headers: {},
          config,
        };
      },
    });
  });

  it('uses idempotencyKey on config when set', async () => {
    await api.request({
      method: 'post',
      url: 'rfqs',
      data: {},
      idempotencyKey: 'from-config',
      adapter: async (config) => {
        const h = config.headers as Record<string, string>;
        expect(h['Idempotency-Key']).toBe('from-config');
        return {
          data: {},
          status: 200,
          statusText: 'OK',
          headers: {},
          config,
        };
      },
    });
  });

  it('does not set Idempotency-Key for GET or non-idempotent POST', async () => {
    await api.request({
      method: 'get',
      url: 'rfqs',
      adapter: async (config) => {
        const h = config.headers as Record<string, string>;
        expect(h['Idempotency-Key']).toBeUndefined();
        return {
          data: {},
          status: 200,
          statusText: 'OK',
          headers: {},
          config,
        };
      },
    });

    await api.request({
      method: 'post',
      url: 'auth/login',
      data: {},
      adapter: async (config) => {
        const h = config.headers as Record<string, string>;
        expect(h['Idempotency-Key']).toBeUndefined();
        return {
          data: {},
          status: 200,
          statusText: 'OK',
          headers: {},
          config,
        };
      },
    });
  });
});

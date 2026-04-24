import React from 'react';
import { render, renderHook, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { createTestWrapper } from '@/test/utils';
import { AiProvider } from '@/providers/ai-provider';
import { useAiStatus } from './use-ai-status';

const mockGet = vi.hoisted(() => vi.fn());

vi.mock('@/lib/api', () => ({
  api: {
    get: mockGet,
  },
}));

function createAiWrapper() {
  const { Wrapper } = createTestWrapper();

  function AiWrapper({ children }: { children: React.ReactNode }) {
    return React.createElement(Wrapper, null, React.createElement(AiProvider, null, children));
  }

  return AiWrapper;
}

describe('useAiStatus', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    process.env.NEXT_PUBLIC_AI_MODE = 'provider';
    process.env.NEXT_PUBLIC_USE_MOCKS = 'false';
    process.env.NEXT_PUBLIC_AI_STATUS_PATH = '/api/v1/ai/status';
    process.env.NEXT_PUBLIC_AI_PROVIDER_NAME = 'huggingface';
  });

  it('normalizes provider mode payload and exposes availability helpers', async () => {
    mockGet.mockResolvedValue({
      data: {
        data: {
          provider_name: 'openrouter',
          mode: 'provider',
          global_health: 'healthy',
          reason_codes: [],
          generated_at: '2026-04-23T10:00:00Z',
          capability_definitions: [
            {
              feature_key: 'rfq_insights',
              capability_group: 'insight_intelligence',
              requires_ai: true,
              has_manual_fallback: true,
              fallback_ui_mode: 'show_manual_continuity_banner',
              degradation_message_key: 'ai.rfq_insights.degraded',
              operator_critical: false,
              endpoint_group: 'insight',
            },
          ],
          capability_statuses: {
            rfq_insights: {
              feature_key: 'rfq_insights',
              capability_group: 'insight_intelligence',
              endpoint_group: 'insight',
              status: 'available',
              available: true,
              fallback_ui_mode: 'show_manual_continuity_banner',
              message_key: null,
              operator_critical: false,
              reason_codes: [],
              diagnostics: {
                provider: 'huggingface',
              },
            },
          },
          endpoint_groups: [
            {
              endpoint_group: 'insight',
              health: 'healthy',
              checked_at: '2026-04-23T10:00:00Z',
              latency_ms: 123,
              reason_codes: [],
              diagnostics: {
                health: 'healthy',
              },
            },
          ],
        },
      },
    });

    const wrapper = createAiWrapper();
    const { result } = renderHook(() => useAiStatus(), { wrapper });

    await waitFor(() => expect(result.current.isReady).toBe(true));

    expect(mockGet).toHaveBeenCalledWith('/ai/status');
    expect(result.current.status.mode).toBe('provider');
    expect(result.current.status.providerName).toBe('openrouter');
    expect(result.current.isFeatureAvailable('rfq_insights')).toBe(true);
    expect(result.current.shouldHideAiControls('rfq_insights')).toBe(false);
    expect(result.current.shouldShowUnavailableMessage('rfq_insights')).toBe(false);
    expect(result.current.messageKeyForFeature('rfq_insights')).toBeNull();
  });

  it('falls back to the bootstrap provider name when the live payload omits provider_name', async () => {
    mockGet.mockResolvedValue({
      data: {
        data: {
          mode: 'provider',
          global_health: 'healthy',
          reason_codes: [],
          generated_at: '2026-04-23T10:00:00Z',
          capability_definitions: [],
          capability_statuses: {},
          endpoint_groups: [],
        },
      },
    });

    const wrapper = createAiWrapper();
    const { result } = renderHook(() => useAiStatus(), { wrapper });

    await waitFor(() => expect(result.current.isReady).toBe(true));

    expect(result.current.status.providerName).toBe('huggingface');
  });

  it('exposes degraded capability helpers from fallback ui mode and message key', async () => {
    mockGet.mockResolvedValue({
      data: {
        data: {
          mode: 'provider',
          global_health: 'degraded',
          reason_codes: ['AI_PROVIDER_DEGRADED'],
          generated_at: '2026-04-23T10:05:00Z',
          capability_definitions: [
            {
              feature_key: 'quote_summary',
              capability_group: 'comparison_intelligence',
              requires_ai: true,
              has_manual_fallback: true,
              fallback_ui_mode: 'show_unavailable_message',
              degradation_message_key: 'ai.quote_summary.degraded',
              operator_critical: true,
              endpoint_group: 'comparison_award',
            },
          ],
          capability_statuses: {
            quote_summary: {
              feature_key: 'quote_summary',
              capability_group: 'comparison_intelligence',
              endpoint_group: 'comparison_award',
              status: 'degraded',
              available: false,
              fallback_ui_mode: 'show_unavailable_message',
              message_key: 'ai.status.provider_unavailable',
              operator_critical: true,
              reason_codes: ['AI_PROVIDER_TIMEOUT'],
              diagnostics: {
                provider: 'huggingface',
              },
            },
          },
          endpoint_groups: [
            {
              endpoint_group: 'comparison_award',
              health: 'degraded',
              checked_at: '2026-04-23T10:05:00Z',
              latency_ms: 850,
              reason_codes: ['AI_PROVIDER_TIMEOUT'],
              diagnostics: {
                health: 'degraded',
              },
            },
          ],
        },
      },
    });

    const wrapper = createAiWrapper();
    const { result } = renderHook(() => useAiStatus(), { wrapper });

    await waitFor(() => expect(result.current.isReady).toBe(true));

    expect(result.current.isFeatureAvailable('quote_summary')).toBe(false);
    expect(result.current.shouldHideAiControls('quote_summary')).toBe(false);
    expect(result.current.shouldShowUnavailableMessage('quote_summary')).toBe(true);
    expect(result.current.messageKeyForFeature('quote_summary')).toBe('ai.status.provider_unavailable');
  });

  it('keeps manual-continuity fallback modes visible while still surfacing an unavailable message', async () => {
    mockGet.mockResolvedValue({
      data: {
        data: {
          mode: 'provider',
          global_health: 'healthy',
          reason_codes: [],
          generated_at: '2026-04-23T10:07:00Z',
          capability_definitions: [
            {
              feature_key: 'normalization_intelligence',
              capability_group: 'normalization_intelligence',
              requires_ai: true,
              has_manual_fallback: true,
              fallback_ui_mode: 'show_manual_continuity_banner',
              degradation_message_key: 'ai.normalization_intelligence.continuity',
              operator_critical: false,
              endpoint_group: 'normalization',
            },
          ],
          capability_statuses: {
            normalization_intelligence: {
              feature_key: 'normalization_intelligence',
              capability_group: 'normalization_intelligence',
              endpoint_group: 'normalization',
              status: 'unavailable',
              available: false,
              fallback_ui_mode: 'show_manual_continuity_banner',
              message_key: 'ai.status.provider_unavailable',
              operator_critical: false,
              reason_codes: ['AI_NORMALIZATION_MANUAL_CONTINUITY'],
              diagnostics: {},
            },
          },
          endpoint_groups: [],
        },
      },
    });

    const wrapper = createAiWrapper();
    const { result } = renderHook(() => useAiStatus(), { wrapper });

    await waitFor(() => expect(result.current.isReady).toBe(true));

    expect(result.current.shouldHideAiControls('normalization_intelligence')).toBe(false);
    expect(result.current.shouldShowUnavailableMessage('normalization_intelligence')).toBe(true);
    expect(result.current.messageKeyForFeature('normalization_intelligence')).toBe('ai.status.provider_unavailable');
  });

  it('shows an unavailable message when an unavailable feature has unknown fallback mode', async () => {
    mockGet.mockResolvedValue({
      data: {
        data: {
          mode: 'provider',
          global_health: 'healthy',
          reason_codes: [],
          generated_at: '2026-04-23T10:06:00Z',
          capability_definitions: [
            {
              feature_key: 'negotiation_summary',
              capability_group: 'governance_intelligence',
              requires_ai: true,
              has_manual_fallback: false,
              fallback_ui_mode: 'show_unavailable_message',
              degradation_message_key: 'ai.negotiation_summary.unavailable',
              operator_critical: false,
              endpoint_group: 'governance',
            },
          ],
          capability_statuses: {
            negotiation_summary: {
              feature_key: 'negotiation_summary',
              capability_group: 'governance_intelligence',
              endpoint_group: 'governance',
              status: 'unavailable',
              available: false,
              fallback_ui_mode: 'operator_decides',
              message_key: 'ai.status.provider_unavailable',
              operator_critical: false,
              reason_codes: ['AI_FALLBACK_UNSPECIFIED'],
              diagnostics: {},
            },
          },
          endpoint_groups: [],
        },
      },
    });

    const wrapper = createAiWrapper();
    const { result } = renderHook(() => useAiStatus(), { wrapper });

    await waitFor(() => expect(result.current.isReady).toBe(true));

    expect(result.current.isFeatureAvailable('negotiation_summary')).toBe(false);
    expect(result.current.shouldHideAiControls('negotiation_summary')).toBe(false);
    expect(result.current.shouldShowUnavailableMessage('negotiation_summary')).toBe(true);
    expect(result.current.messageKeyForFeature('negotiation_summary')).toBe('ai.status.provider_unavailable');
  });

  it('fails closed for ai availability when AI mode is off without blocking children', async () => {
    process.env.NEXT_PUBLIC_AI_MODE = 'off';

    const wrapper = createAiWrapper();
    const { result } = renderHook(() => useAiStatus(), { wrapper });

    await waitFor(() => expect(result.current.isReady).toBe(true));

    expect(mockGet).not.toHaveBeenCalled();
    expect(result.current.status.mode).toBe('off');
    expect(result.current.isFeatureAvailable('rfq_insights')).toBe(false);
    expect(result.current.shouldHideAiControls('rfq_insights')).toBe(true);
    expect(result.current.shouldShowUnavailableMessage('rfq_insights')).toBe(true);
    expect(result.current.messageKeyForFeature('rfq_insights')).toBe('ai.status.off');
  });

  it('stays in a stable mock-safe state and skips the live status request in mock mode', async () => {
    process.env.NEXT_PUBLIC_USE_MOCKS = 'true';

    const wrapper = createAiWrapper();
    const { result } = renderHook(() => useAiStatus(), { wrapper });

    await waitFor(() => expect(result.current.isReady).toBe(true));

    expect(mockGet).not.toHaveBeenCalled();
    expect(result.current.isError).toBe(false);
    expect(result.current.status.mode).toBe('deterministic');
    expect(result.current.status.globalHealth).toBe('degraded');
    expect(result.current.isFeatureAvailable('rfq_insights')).toBe(false);
    expect(result.current.shouldHideAiControls('rfq_insights')).toBe(false);
    expect(result.current.shouldShowUnavailableMessage('rfq_insights')).toBe(true);
    expect(result.current.messageKeyForFeature('rfq_insights')).toBe('ai.status.mock_mode');
  });

  it('skips the live status request in deterministic mode and returns a safe disabled snapshot', async () => {
    process.env.NEXT_PUBLIC_AI_MODE = 'deterministic';

    const wrapper = createAiWrapper();
    const { result } = renderHook(() => useAiStatus(), { wrapper });

    await waitFor(() => expect(result.current.isReady).toBe(true));

    expect(mockGet).not.toHaveBeenCalled();
    expect(result.current.status.mode).toBe('deterministic');
    expect(result.current.isFeatureAvailable('rfq_insights')).toBe(false);
    expect(result.current.shouldHideAiControls('rfq_insights')).toBe(false);
    expect(result.current.shouldShowUnavailableMessage('rfq_insights')).toBe(true);
    expect(result.current.messageKeyForFeature('rfq_insights')).toBe('ai.status.degraded');
  });

  it('exposes a no-op refetch when live status is disabled', async () => {
    process.env.NEXT_PUBLIC_AI_MODE = 'off';

    const wrapper = createAiWrapper();
    const { result } = renderHook(() => useAiStatus(), { wrapper });

    await waitFor(() => expect(result.current.isReady).toBe(true));

    await expect(result.current.refetch()).resolves.toEqual(result.current.status);
    expect(mockGet).not.toHaveBeenCalled();
  });

  it('keeps shell content rendered when the status request fails and returns safe fallbacks', async () => {
    mockGet.mockRejectedValue(new Error('status endpoint unavailable'));

    const wrapper = createAiWrapper();

    function Probe() {
      const aiStatus = useAiStatus();

      return React.createElement(
        'div',
        null,
        React.createElement('span', null, 'Shell navigation'),
        React.createElement('span', null, aiStatus.messageKeyForFeature('rfq_insights') ?? 'no-message-key'),
      );
    }

    render(React.createElement(Probe), { wrapper });

    expect(screen.getByText('Shell navigation')).toBeInTheDocument();

    await waitFor(() => {
      expect(screen.getByText('ai.status.unavailable')).toBeInTheDocument();
    });

    const hook = renderHook(() => useAiStatus(), { wrapper });

    await waitFor(() => expect(hook.result.current.isError).toBe(true));

    expect(hook.result.current.isFeatureAvailable('rfq_insights')).toBe(false);
    expect(hook.result.current.shouldHideAiControls('rfq_insights')).toBe(true);
    expect(hook.result.current.shouldShowUnavailableMessage('rfq_insights')).toBe(true);
    expect(hook.result.current.messageKeyForFeature('rfq_insights')).toBe('ai.status.unavailable');
  });
});

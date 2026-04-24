import React from 'react';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import { screen } from '@testing-library/react';

import { renderWithProviders } from '@/test/utils';

let aiStatusData = {
  shouldHideAiControls: () => false,
  shouldShowUnavailableMessage: () => false,
  messageKeyForFeature: () => null,
};

vi.mock('@/hooks/use-ai-status', () => ({
  useAiStatus: () => aiStatusData,
}));

import { AiNarrativePanel } from './ai-narrative-panel';

describe('AiNarrativePanel', () => {
  beforeEach(() => {
    aiStatusData = {
      shouldHideAiControls: () => false,
      shouldShowUnavailableMessage: () => false,
      messageKeyForFeature: () => null,
    };
  });

  it('renders the available narrative summary', () => {
    renderWithProviders(
      <AiNarrativePanel
        featureKey="insight_intelligence"
        title="AI summary"
        subtitle="Assistive interpretation of the deterministic data"
        summary={{
          featureKey: 'insight_intelligence',
          available: true,
          headline: 'Spend is stable and cycle time is improving.',
          summary: 'The RFQ pipeline is healthy.',
          bullets: ['Vendor response rates are up.', 'No material risk spikes are visible.'],
          provenance: { provider: 'openrouter', endpoint_group: 'insight' },
          raw: null,
        }}
        isLoading={false}
        isError={false}
        error={null}
        fallbackCopy="AI summary is unavailable."
      />,
    );

    expect(screen.getByText('AI summary')).toBeInTheDocument();
    expect(screen.getByText('Spend is stable and cycle time is improving.')).toBeInTheDocument();
    expect(screen.getByText('Vendor response rates are up.')).toBeInTheDocument();
    expect(screen.getByText('AI-derived')).toBeInTheDocument();
  });

  it('hides the panel when AI controls should be hidden', () => {
    aiStatusData = {
      shouldHideAiControls: () => true,
      shouldShowUnavailableMessage: () => false,
      messageKeyForFeature: () => null,
    };

    const { container } = renderWithProviders(
      <AiNarrativePanel
        featureKey="insight_intelligence"
        title="AI summary"
        summary={null}
        isLoading={false}
        isError={false}
        error={null}
        fallbackCopy="AI summary is unavailable."
      />,
    );

    expect(container).toBeEmptyDOMElement();
  });

  it('renders an unavailable callout when the capability surfaces a message', () => {
    aiStatusData = {
      shouldHideAiControls: () => false,
      shouldShowUnavailableMessage: () => true,
      messageKeyForFeature: () => 'ai.status.degraded',
    };

    renderWithProviders(
      <AiNarrativePanel
        featureKey="insight_intelligence"
        title="AI summary"
        summary={null}
        isLoading={false}
        isError={false}
        error={null}
        fallbackCopy="AI summary is unavailable."
      />,
    );

    expect(screen.getByText('AI summary unavailable')).toBeInTheDocument();
    expect(screen.getByText('ai.status.degraded')).toBeInTheDocument();
  });
});

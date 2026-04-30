import React from 'react';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

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

  it('renders a generate button when generation is available', () => {
    renderWithProviders(
      <AiNarrativePanel
        featureKey="insight_intelligence"
        title="AI summary"
        summary={null}
        onGenerate={vi.fn()}
        canGenerate
      />,
    );

    expect(screen.getByRole('button', { name: 'Generate' })).toBeEnabled();
  });

  it('disables generate when canGenerate is false', () => {
    renderWithProviders(
      <AiNarrativePanel
        featureKey="insight_intelligence"
        title="AI summary"
        summary={null}
        onGenerate={vi.fn()}
        canGenerate={false}
      />,
    );

    expect(screen.getByRole('button', { name: 'Generate' })).toBeDisabled();
  });

  it('calls onGenerate when the button is clicked', async () => {
    const user = userEvent.setup();
    const onGenerate = vi.fn();

    renderWithProviders(
      <AiNarrativePanel
        featureKey="insight_intelligence"
        title="AI summary"
        summary={null}
        onGenerate={onGenerate}
        canGenerate
      />,
    );

    await user.click(screen.getByRole('button', { name: 'Generate' }));

    expect(onGenerate).toHaveBeenCalledTimes(1);
  });

  it('does not re-trigger generate while generation is in-flight', async () => {
    const user = userEvent.setup();
    const onGenerate = vi.fn();

    renderWithProviders(
      <AiNarrativePanel
        featureKey="insight_intelligence"
        title="AI summary"
        summary={null}
        onGenerate={onGenerate}
        canGenerate
        isGenerating
      />,
    );

    const button = screen.getByRole('button', { name: 'Generating...' });
    expect(button).toBeDisabled();
    await user.click(button);
    expect(onGenerate).not.toHaveBeenCalled();
  });

  it('labels generation as regenerate when an available summary exists', () => {
    renderWithProviders(
      <AiNarrativePanel
        featureKey="insight_intelligence"
        title="AI summary"
        summary={{
          featureKey: 'insight_intelligence',
          available: true,
          headline: 'Spend is stable.',
          summary: null,
          bullets: [],
          provenance: null,
          raw: null,
        }}
        onGenerate={vi.fn()}
        canGenerate
      />,
    );

    expect(screen.getByRole('button', { name: 'Regenerate' })).toBeEnabled();
  });

  it('keeps unavailable callout scoped to the panel', () => {
    aiStatusData = {
      shouldHideAiControls: () => false,
      shouldShowUnavailableMessage: () => true,
      messageKeyForFeature: () => 'ai.status.degraded',
    };

    renderWithProviders(
      <div>
        <span>Outside content</span>
        <AiNarrativePanel
          featureKey="insight_intelligence"
          title="AI summary"
          summary={null}
          onGenerate={vi.fn()}
          canGenerate
          fallbackCopy="AI summary is unavailable."
        />
      </div>,
    );

    expect(screen.getByText('Outside content')).toBeInTheDocument();
    expect(screen.getByText('AI summary unavailable')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Generate' })).toBeEnabled();
  });
});

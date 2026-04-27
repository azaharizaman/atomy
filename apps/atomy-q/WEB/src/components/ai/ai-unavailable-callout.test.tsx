import React from 'react';
import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { AiUnavailableCallout } from './ai-unavailable-callout';

describe('AiUnavailableCallout', () => {
  it('renders mapped copy for a known message key', () => {
    render(<AiUnavailableCallout messageKey="ai.status.off" />);

    expect(screen.getByText('AI unavailable')).toBeInTheDocument();
    expect(screen.getByText('AI features are disabled for this environment.')).toBeInTheDocument();
    expect(screen.getByText('ai.status.off')).toBeInTheDocument();
  });

  it('renders fallback copy and preserves the action surface for unknown keys', () => {
    render(
      <AiUnavailableCallout
        title="Quote summary unavailable"
        messageKey="ai.vendor.summary.blocked"
        fallbackCopy="Quote summaries are temporarily unavailable. Continue with the manual review path."
        actions={<button type="button">Review manually</button>}
      />,
    );

    expect(screen.getByText('Quote summary unavailable')).toBeInTheDocument();
    expect(
      screen.getByText('Quote summaries are temporarily unavailable. Continue with the manual review path.'),
    ).toBeInTheDocument();
    expect(screen.getByText('ai.vendor.summary.blocked')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Review manually' })).toBeInTheDocument();
  });
});

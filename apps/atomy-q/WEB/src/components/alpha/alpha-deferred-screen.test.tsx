import { afterEach, describe, expect, it, vi } from 'vitest';
import { render, screen } from '@testing-library/react';

import { AlphaDeferredScreen } from './alpha-deferred-screen';
import {
  ALPHA_DEFERRED_COPY,
  isAlphaMode,
  isDeferredAlphaPath,
  isRfqSectionVisibleInAlpha,
  isTopLevelNavVisibleInAlpha,
} from '@/lib/alpha-mode';

afterEach(() => {
  vi.unstubAllEnvs();
});

describe('alpha mode policy', () => {
  it('uses the exact alpha flag contract', () => {
    vi.stubEnv('NEXT_PUBLIC_ALPHA_MODE', 'true');
    expect(isAlphaMode()).toBe(true);

    vi.stubEnv('NEXT_PUBLIC_ALPHA_MODE', 'false');
    expect(isAlphaMode()).toBe(false);

    vi.stubEnv('NEXT_PUBLIC_ALPHA_MODE', 'TRUE');
    expect(isAlphaMode()).toBe(false);

    vi.stubEnv('NEXT_PUBLIC_ALPHA_MODE', '');
    expect(isAlphaMode()).toBe(false);

    vi.stubEnv('NEXT_PUBLIC_ALPHA_MODE', undefined);
    expect(isAlphaMode()).toBe(false);
  });

  it('keeps top-level navigation restricted to alpha surfaces', () => {
    expect(isTopLevelNavVisibleInAlpha('dashboard')).toBe(true);
    expect(isTopLevelNavVisibleInAlpha('requisition')).toBe(true);
    expect(isTopLevelNavVisibleInAlpha('documents')).toBe(false);
    expect(isTopLevelNavVisibleInAlpha('reporting')).toBe(false);
    expect(isTopLevelNavVisibleInAlpha('settings')).toBe(false);
    expect(isTopLevelNavVisibleInAlpha('approvals')).toBe(false);
  });

  it('keeps RFQ workspace visibility aligned with the alpha golden path', () => {
    expect(isRfqSectionVisibleInAlpha('overview')).toBe(true);
    expect(isRfqSectionVisibleInAlpha('details')).toBe(true);
    expect(isRfqSectionVisibleInAlpha('line-items')).toBe(true);
    expect(isRfqSectionVisibleInAlpha('vendors')).toBe(true);
    expect(isRfqSectionVisibleInAlpha('award')).toBe(true);
    expect(isRfqSectionVisibleInAlpha('quote-intake')).toBe(true);
    expect(isRfqSectionVisibleInAlpha('comparison-runs')).toBe(true);
    expect(isRfqSectionVisibleInAlpha('approvals')).toBe(true);
    expect(isRfqSectionVisibleInAlpha('decision-trail')).toBe(true);
    expect(isRfqSectionVisibleInAlpha('negotiations')).toBe(false);
    expect(isRfqSectionVisibleInAlpha('documents')).toBe(false);
    expect(isRfqSectionVisibleInAlpha('risk')).toBe(false);
  });

  it('matches the deferred route contract', () => {
    expect(isDeferredAlphaPath('/documents')).toBe(true);
    expect(isDeferredAlphaPath('/reporting')).toBe(true);
    expect(isDeferredAlphaPath('/settings')).toBe(true);
    expect(isDeferredAlphaPath('/settings/users')).toBe(true);
    expect(isDeferredAlphaPath('/settings/integrations')).toBe(true);
    expect(isDeferredAlphaPath('/rfqs/01JABC123/negotiations')).toBe(true);
    expect(isDeferredAlphaPath('/rfqs/01JABC123/documents')).toBe(true);
    expect(isDeferredAlphaPath('/rfqs/01JABC123/risk')).toBe(true);
    expect(isDeferredAlphaPath('/rfqs/01JABC123/approvals')).toBe(false);
    expect(isDeferredAlphaPath('/rfqs/01JABC123/overview')).toBe(false);
    expect(isDeferredAlphaPath('/dashboard')).toBe(false);
  });
});

describe('AlphaDeferredScreen', () => {
  it('renders the shared deferred screen copy without a default action button', () => {
    render(<AlphaDeferredScreen title="Reporting" subtitle="Alpha access is deferred" />);

    expect(screen.getByRole('heading', { level: 1, name: 'Reporting' })).toBeInTheDocument();
    expect(screen.getByText(ALPHA_DEFERRED_COPY)).toBeInTheDocument();
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });
});

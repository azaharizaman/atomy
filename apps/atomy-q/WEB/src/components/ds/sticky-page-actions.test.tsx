import React from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { render, screen } from '@testing-library/react';

import { StickyPageActions } from './sticky-page-actions';

class MockIntersectionObserver {
  callback: IntersectionObserverCallback;

  constructor(callback: IntersectionObserverCallback) {
    this.callback = callback;
  }

  observe() {
    this.callback([{ isIntersecting: false } as IntersectionObserverEntry], this as unknown as IntersectionObserver);
  }

  disconnect() {}
  unobserve() {}
  takeRecords() {
    return [];
  }
}

describe('StickyPageActions', () => {
  beforeEach(() => {
    vi.stubGlobal('IntersectionObserver', MockIntersectionObserver);
  });

  it('renders the floating dock when the inline actions are not visible', () => {
    const target = document.createElement('div');
    document.body.appendChild(target);
    const targetRef = { current: target };

    render(
      <StickyPageActions active targetRef={targetRef}>
        <button type="button">Save changes</button>
      </StickyPageActions>,
    );

    expect(screen.getByTestId('sticky-page-actions')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /save changes/i })).toBeInTheDocument();
  });

  it('renders nothing when inactive', () => {
    const target = document.createElement('div');
    document.body.appendChild(target);
    const targetRef = { current: target };

    const { container } = render(
      <StickyPageActions active={false} targetRef={targetRef}>
        <button type="button">Save changes</button>
      </StickyPageActions>,
    );

    expect(container).toBeEmptyDOMElement();
  });
});

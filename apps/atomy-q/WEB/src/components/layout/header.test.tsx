import React from 'react';
import { describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';

import { renderWithProviders } from '@/test/utils';

vi.mock('next/navigation', () => ({
  usePathname: () => '/rfqs/rfq-1/details',
  useRouter: () => ({ push: vi.fn() }),
}));

vi.mock('@/store/use-auth-store', () => ({
  useAuthStore: (selector: (state: { user: { name: string; email: string; tenantId: string }; logout: () => void }) => unknown) =>
    selector({
      user: { name: 'Aisyah Lim', email: 'aisyah@example.test', tenantId: 'tenant-1' },
      logout: vi.fn(),
    }),
}));

vi.mock('@/hooks/use-rfq', () => ({
  useRfq: () => ({
    data: { title: 'Desktop Purchase' },
    isLoading: false,
    isError: false,
    error: null,
  }),
}));

import { Header } from './header';

describe('Header', () => {
  it('renders semantic breadcrumb items for RFQ workspace routes', () => {
    renderWithProviders(<Header />);

    expect(screen.getByRole('navigation', { name: /breadcrumb/i })).toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'RFQs' })).toHaveAttribute('href', '/rfqs');
    expect(screen.getByText('Desktop Purchase')).toBeInTheDocument();
    expect(screen.getByText('Details')).toHaveAttribute('aria-current', 'page');
  });
});

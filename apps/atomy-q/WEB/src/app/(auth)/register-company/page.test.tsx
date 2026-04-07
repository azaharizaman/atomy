import React from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import { AxiosError } from 'axios';
import { renderWithProviders } from '@/test/utils';

const mocks = vi.hoisted(() => ({
  pushMock: vi.fn(),
  loginMock: vi.fn(),
  apiPostMock: vi.fn(),
  apiGetMock: vi.fn(),
  toastSuccessMock: vi.fn(),
  toastErrorMock: vi.fn(),
}));

vi.mock('next/navigation', () => ({
  useRouter: () => ({ push: mocks.pushMock }),
}));

vi.mock('sonner', () => ({
  toast: {
    success: mocks.toastSuccessMock,
    error: mocks.toastErrorMock,
  },
}));

vi.mock('@/lib/api', () => ({
  api: {
    post: mocks.apiPostMock,
    get: mocks.apiGetMock,
  },
}));

vi.mock('@/store/use-auth-store', () => ({
  useAuthStore: (selector: (state: { login: typeof mocks.loginMock }) => unknown) =>
    selector({ login: mocks.loginMock }),
}));

import RegisterCompanyPage from './page';

function buildAxiosError(status: number, data: unknown) {
  const error = new AxiosError('Request failed');
  error.response = {
    status,
    statusText: 'Error',
    data,
    headers: {},
    config: error.config ?? {},
  };
  return error;
}

describe('RegisterCompanyPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mocks.apiGetMock.mockResolvedValue({
      data: {
        id: 'user-1',
        name: 'Ada Lovelace',
        email: 'ada@acme.com',
        role: 'admin',
        tenantId: 'tenant-1',
      },
    });
  });

  it('registers a company, logs the owner in, and redirects', async () => {
    mocks.apiPostMock.mockResolvedValue({
      data: {
        access_token: 'access-token',
        refresh_token: 'refresh-token',
        token_type: 'Bearer',
        expires_in: 3600,
        user: {
          id: 'user-1',
          name: 'Ada Lovelace',
          email: 'ada@acme.com',
          role: 'admin',
          tenantId: 'tenant-1',
        },
        bootstrap: {
          tenant_code: 'acme',
        },
      },
    });

    renderWithProviders(<RegisterCompanyPage />);

    fireEvent.change(screen.getByLabelText('Company name'), { target: { value: 'Acme Procurement Ltd' } });
    fireEvent.change(screen.getByLabelText('Company code'), { target: { value: 'acme' } });
    fireEvent.change(screen.getByLabelText('Owner full name'), { target: { value: 'Ada Lovelace' } });
    fireEvent.change(screen.getByLabelText('Owner email'), { target: { value: 'Ada@Acme.Com' } });
    fireEvent.change(screen.getByLabelText('Owner password'), { target: { value: 'password123' } });
    fireEvent.change(screen.getByLabelText('Timezone'), { target: { value: 'America/New_York' } });
    fireEvent.change(screen.getByLabelText('Locale'), { target: { value: 'en-US' } });
    fireEvent.change(screen.getByLabelText('Currency'), { target: { value: 'USD' } });

    fireEvent.click(screen.getByRole('button', { name: /create company and sign in/i }));

    await waitFor(() => expect(mocks.apiPostMock).toHaveBeenCalledTimes(1));

    expect(mocks.apiPostMock).toHaveBeenCalledWith('/auth/register-company', {
      tenant_code: 'acme',
      company_name: 'Acme Procurement Ltd',
      owner_name: 'Ada Lovelace',
      owner_email: 'ada@acme.com',
      owner_password: 'password123',
      timezone: 'America/New_York',
      locale: 'en-US',
      currency: 'USD',
    });
    expect(mocks.loginMock).toHaveBeenCalledWith('access-token', 'refresh-token', {
      id: 'user-1',
      name: 'Ada Lovelace',
      email: 'ada@acme.com',
      role: 'admin',
      tenantId: 'tenant-1',
    });
    expect(mocks.pushMock).toHaveBeenCalledWith('/');
    expect(mocks.toastSuccessMock).toHaveBeenCalledWith('Company created successfully');
  });

  it('maps backend validation errors onto the form', async () => {
    mocks.apiPostMock.mockRejectedValue(
      buildAxiosError(422, {
        message: 'Validation failed',
        errors: {
          company_name: ['The company name has already been taken.'],
        },
      })
    );

    renderWithProviders(<RegisterCompanyPage />);

    fireEvent.change(screen.getByLabelText('Company name'), { target: { value: 'Acme Procurement Ltd' } });
    fireEvent.change(screen.getByLabelText('Company code'), { target: { value: 'acme' } });
    fireEvent.change(screen.getByLabelText('Owner full name'), { target: { value: 'Ada Lovelace' } });
    fireEvent.change(screen.getByLabelText('Owner email'), { target: { value: 'ada@acme.com' } });
    fireEvent.change(screen.getByLabelText('Owner password'), { target: { value: 'password123' } });

    fireEvent.click(screen.getByRole('button', { name: /create company and sign in/i }));

    await waitFor(() => expect(screen.getByText('The company name has already been taken.')).toBeInTheDocument());
    expect(mocks.loginMock).not.toHaveBeenCalled();
    expect(mocks.pushMock).not.toHaveBeenCalled();
    expect(mocks.toastErrorMock).toHaveBeenCalledWith('Validation failed');
  });
});

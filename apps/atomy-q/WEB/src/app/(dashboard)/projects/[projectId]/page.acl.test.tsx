import React from 'react';
import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen } from '@testing-library/react';
import { renderWithProviders } from '@/test/utils';

const mockUseProject = vi.fn();
const mockUseProjectHealth = vi.fn();
const mockUseProjectRfqs = vi.fn();
const mockUseProjectTasks = vi.fn();
const mockUseProjectAcl = vi.fn();

vi.mock('next/navigation', () => ({
  useParams: () => ({ projectId: 'p1' }),
  useRouter: () => ({ push: vi.fn() }),
}));

vi.mock('@/hooks/use-project', () => ({
  useProject: (...args: unknown[]) => mockUseProject(...args),
}));
vi.mock('@/hooks/use-project-health', () => ({
  useProjectHealth: (...args: unknown[]) => mockUseProjectHealth(...args),
}));
vi.mock('@/hooks/use-project-rfqs', () => ({
  useProjectRfqs: (...args: unknown[]) => mockUseProjectRfqs(...args),
}));
vi.mock('@/hooks/use-project-tasks', () => ({
  useProjectTasks: (...args: unknown[]) => mockUseProjectTasks(...args),
}));
vi.mock('@/hooks/use-project-acl', () => ({
  useProjectAcl: (...args: unknown[]) => mockUseProjectAcl(...args),
}));

vi.mock('@/hooks/use-feature-flags', () => ({
  useFeatureFlags: () => ({ data: { projects: true, tasks: true }, isLoading: false }),
}));

const mockUpdateAclMutate = vi.fn();
vi.mock('@/hooks/use-update-project-acl', () => ({
  useUpdateProjectAcl: () => ({ mutate: mockUpdateAclMutate, isPending: false, isError: false, error: null }),
}));

vi.mock('@/hooks/use-update-project', () => ({
  useUpdateProject: () => ({ mutate: vi.fn(), isPending: false, isError: false, error: null }),
}));
vi.mock('@/hooks/use-update-project-status', () => ({
  PROJECT_STATUSES: ['planning', 'active'],
  useUpdateProjectStatus: () => ({ mutate: vi.fn(), isPending: false }),
}));

import ProjectDetailPage from './page';

describe('ProjectDetailPage ACL', () => {
  it('renders Project access card and saves edited roles', () => {
    mockUseProject.mockReturnValue({ data: { id: 'p1', name: 'P', status: 'planning' }, isLoading: false, error: null });
    mockUseProjectHealth.mockReturnValue({ data: null, isLoading: false, isError: false, error: null });
    mockUseProjectRfqs.mockReturnValue({ data: [], isLoading: false, isError: false, error: null });
    mockUseProjectTasks.mockReturnValue({ data: [], isLoading: false, isError: false, error: null });
    mockUseProjectAcl.mockReturnValue({ data: [{ userId: 'u1', role: 'viewer' }], isLoading: false, isError: false, error: null });

    renderWithProviders(<ProjectDetailPage />);

    expect(screen.getByText(/Project access/i)).toBeInTheDocument();

    const userInput = screen.getByLabelText('User ID 1') as HTMLInputElement;
    fireEvent.change(userInput, { target: { value: 'u2' } });

    fireEvent.click(screen.getByRole('button', { name: /save access/i }));

    expect(mockUpdateAclMutate).toHaveBeenCalledWith([{ userId: 'u2', role: 'viewer' }], expect.any(Object));
  });
});


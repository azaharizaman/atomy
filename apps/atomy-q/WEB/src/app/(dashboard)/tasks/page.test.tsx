import React from 'react';
import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen } from '@testing-library/react';
import { renderWithProviders } from '@/test/utils';

const mockUseTasks = vi.fn();
const mockUseTask = vi.fn();

vi.mock('@/hooks/use-tasks', () => ({
  useTasks: (...args: unknown[]) => mockUseTasks(...args),
}));

vi.mock('@/hooks/use-task', () => ({
  useTask: (...args: unknown[]) => mockUseTask(...args),
}));

vi.mock('@/hooks/use-update-task-status', () => ({
  TASK_STATUSES: ['pending', 'in_progress', 'completed'],
  useUpdateTaskStatus: () => ({ mutate: vi.fn(), isPending: false }),
}));

import TasksPage from './page';

describe('TasksPage', () => {
  it('renders drawer error state when task fetch fails', async () => {
    mockUseTasks.mockReturnValue({
      data: [{ id: 't1', title: 'Task 1', status: 'pending' }],
      isLoading: false,
      isError: false,
      error: null,
    });
    mockUseTask.mockReturnValue({
      data: null,
      isLoading: false,
      isError: true,
      error: new Error('boom'),
    });

    renderWithProviders(<TasksPage />);

    fireEvent.click(screen.getByText('Task 1'));
    expect(await screen.findByText(/Failed to load task/i)).toBeInTheDocument();
  });

  it('overlay closes drawer on click and on keyboard', () => {
    mockUseTasks.mockReturnValue({
      data: [{ id: 't1', title: 'Task 1', status: 'pending' }],
      isLoading: false,
      isError: false,
      error: null,
    });
    mockUseTask.mockReturnValue({
      data: { id: 't1', title: 'Task 1', status: 'pending' },
      isLoading: false,
      isError: false,
      error: null,
    });

    renderWithProviders(<TasksPage />);

    fireEvent.click(screen.getByText('Task 1'));

    const overlay = screen.getByRole('button', { name: /close task drawer/i });
    fireEvent.keyDown(overlay, { key: 'Enter' });
    expect(screen.queryByText('Task detail')).not.toBeInTheDocument();

    fireEvent.click(screen.getByText('Task 1'));
    const overlay2 = screen.getByRole('button', { name: /close task drawer/i });
    fireEvent.click(overlay2);
    expect(screen.queryByText('Task detail')).not.toBeInTheDocument();
  });
});


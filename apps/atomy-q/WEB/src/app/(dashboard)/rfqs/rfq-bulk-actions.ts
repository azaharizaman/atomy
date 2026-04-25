'use client';

export function getRfqBulkActionLabels(useMocks: boolean): string[] {
  return useMocks
    ? ['Close Selected', 'Archive Selected', 'Assign Owner', 'Export Selected']
    : ['Close Selected', 'Cancel Selected'];
}

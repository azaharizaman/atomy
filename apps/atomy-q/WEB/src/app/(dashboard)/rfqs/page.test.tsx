import { describe, expect, it } from 'vitest';

import { getRfqBulkActionLabels } from './rfq-bulk-actions';

describe('rfq list bulk action labels', () => {
  it('hides unsupported actions in live mode', () => {
    expect(getRfqBulkActionLabels(false)).toEqual(['Close Selected', 'Cancel Selected']);
  });

  it('keeps the broader mock toolbar in mock mode', () => {
    expect(getRfqBulkActionLabels(true)).toEqual([
      'Close Selected',
      'Archive Selected',
      'Assign Owner',
      'Export Selected',
    ]);
  });
});

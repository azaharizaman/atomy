import { describe, expect, it } from 'vitest';

import { getRfqBulkActionLabels } from './rfq-bulk-actions';

describe('rfq list bulk action labels', () => {
  it('returns the live bulk action labels', () => {
    expect(getRfqBulkActionLabels()).toEqual(['Close Selected', 'Cancel Selected']);
  });
});

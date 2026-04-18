import { describe, expect, it } from 'vitest';

import { getSeedLineItemsByRfqId } from './seed';

describe('seed line items', () => {
  it('provides deterministic line items for seeded requisitions', () => {
    const lineItems = getSeedLineItemsByRfqId('RFQ-2026-0001');

    expect(lineItems.length).toBeGreaterThan(0);
    expect(lineItems.every((item) => item.rfqId === 'RFQ-2026-0001')).toBe(true);
    expect(lineItems.some((item) => item.rowType === 'heading')).toBe(true);
    expect(lineItems.some((item) => item.rowType === 'line')).toBe(true);
  });
});

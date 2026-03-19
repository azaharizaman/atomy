import { describe, expect, it } from 'vitest';
import { conflictTypeLabel } from './use-normalization-review';

describe('conflictTypeLabel', () => {
  it('maps known conflict types to human labels', () => {
    expect(conflictTypeLabel('price_mismatch')).toBe('Missing price');
    expect(conflictTypeLabel('ambiguous_mapping')).toBe('Ambiguous mapping');
    expect(conflictTypeLabel('currency_missing')).toBe('Missing currency');
    expect(conflictTypeLabel('invalid_uom')).toBe('Invalid UOM');
  });
});

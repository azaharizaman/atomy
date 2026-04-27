import { describe, expect, it } from 'vitest';

import {
  getRfqInsightsErrorMessage,
  getRfqOverviewErrorMessage,
  getRfqRecordErrorMessage,
} from './rfq-error-copy';

describe('rfq error copy', () => {
  it('explains when the RFQ is not accessible', () => {
    expect(getRfqRecordErrorMessage({ status: 404 })).toBe('This RFQ is not available in your workspace.');
    expect(getRfqOverviewErrorMessage({ status: 403 })).toBe(
      'This RFQ is not available in your workspace, so the overview cannot be shown.',
    );
    expect(getRfqInsightsErrorMessage({ status: 404 })).toBe(
      'This RFQ is not available in your workspace, so insights cannot be shown.',
    );
  });

  it('explains incomplete RFQ status data in plain language', () => {
    expect(getRfqRecordErrorMessage(new Error('RFQ "x" has invalid status.'))).toBe(
      'We found this RFQ, but its status data is incomplete.',
    );
  });

  it('keeps generic load failures user-friendly', () => {
    expect(getRfqOverviewErrorMessage(new Error('Network unavailable'))).toBe(
      'We could not load the overview for this RFQ right now. Please try again in a moment.',
    );
  });
});

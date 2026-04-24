import { describe, expect, it } from 'vitest';
import { normalizeAwardAiGuidance } from '@/hooks/use-award-guidance';

describe('normalizeAwardAiGuidance', () => {
  it('falls back to payload provenance when top-level provenance is missing', () => {
    expect(
      normalizeAwardAiGuidance({
        data: {
          ai_guidance: {
            feature_key: 'award_ai_guidance',
            available: true,
            payload: {
              headline: 'Proceed.',
              provenance: {
                source: 'provider',
              },
            },
          },
        },
      }),
    ).toEqual({
      featureKey: 'award_ai_guidance',
      available: true,
      payload: {
        headline: 'Proceed.',
        provenance: {
          source: 'provider',
        },
      },
      provenance: {
        source: 'provider',
      },
    });
  });
});

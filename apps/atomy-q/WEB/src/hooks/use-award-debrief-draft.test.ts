import { describe, expect, it } from 'vitest';
import { normalizeAwardDebriefDraft } from '@/hooks/use-award-debrief-draft';

describe('normalizeAwardDebriefDraft', () => {
  it('rejects payloads whose draft_message is not a string', () => {
    expect(
      normalizeAwardDebriefDraft({
        data: {
          ai_debrief_draft: {
            feature_key: 'award_ai_guidance',
            available: true,
            payload: {
              draft_message: 42,
            },
          },
        },
      }),
    ).toEqual({
      featureKey: 'award_ai_guidance',
      available: true,
      payload: null,
      provenance: null,
    });
  });

  it('rejects payloads whose legacy message is not a string', () => {
    expect(
      normalizeAwardDebriefDraft({
        data: {
          ai_debrief_draft: {
            feature_key: 'award_ai_guidance',
            available: true,
            payload: {
              message: false,
            },
          },
        },
      }),
    ).toEqual({
      featureKey: 'award_ai_guidance',
      available: true,
      payload: null,
      provenance: null,
    });
  });
});

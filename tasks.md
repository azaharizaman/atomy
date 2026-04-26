# Task Plan

- Task 1: Update `useNormalizationSourceLines.ts` to include `overrideNormalizationSourceLine` mutation.
- Task 2: Update `NormalizePage` in `page.tsx` to collect reason/note/data and call the new mutation.

## Task 1: Update Hook
- Replace `useManualNormalizationSourceLineMutations` logic.
- Change `updateSourceLine` to use `PUT /normalization/source-lines/{id}/override`.
- Payload needs: `override_data` (object containing the normalized values), `reason_code`, `note`.

## Task 2: Update UI
- `page.tsx` is currently calling `updateSourceLine` with old signature.
- Change call to use new mutation signature: `id`, `override_data` (values), `reason_code`, `note`.

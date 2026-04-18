# Hook Test Standard

All new or modified hooks in `apps/atomy-q/WEB/src/hooks` must be accompanied by the standard test set below.

## Required Coverage

1. `mock-mode` fallback test
2. `live-mode` transport error test
3. `live-mode` valid payload test
4. `live-mode` undefined payload test
5. `live-mode` malformed payload test

## Enforcement Standard

- Hook work is not complete until the required coverage exists.
- The live-mode tests must verify the real transport boundary and normalization behavior.
- The mock-mode test must confirm the current seed/demo fallback behavior for `NEXT_PUBLIC_USE_MOCKS=true`.
- If a hook does not support a mock fallback, its test must still explicitly lock the intended mock-mode behavior.

## Notes

- Use the existing hook tests in `src/hooks/*.live.test.ts` and `src/hooks/*.test.ts` as the reference pattern.
- Keep assertions focused on behavior, not implementation details.
- When hook behavior changes, update the corresponding test matrix in the same change.

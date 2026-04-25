import { defineConfig } from 'vitest/config';

export default defineConfig({
  test: {
    environment: 'node',
    globals: true,
    include: ['tests/support/providerQuoteFixtures.test.ts'],
    passWithNoTests: false,
  },
});

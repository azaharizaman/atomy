import path from 'node:path';
import fs from 'node:fs';

import { describe, expect, it } from 'vitest';

import { discoverProviderQuoteFixtures, parseProviderQuoteFixture } from './providerQuoteFixtures';

describe('providerQuoteFixtures', () => {
  it('parses the sample metadata example and resolves quote pdf paths', () => {
    const sampleDir = path.resolve(__dirname, '../../../../../sample');
    const fixture = parseProviderQuoteFixture({
      baseDir: sampleDir,
      metadataPath: path.resolve(sampleDir, 'metadata.example.json'),
    });

    expect(fixture.requisitionId).toBe('vehicle-service-rfq');
    expect(fixture.rfqLineItems).toHaveLength(1);
    expect(fixture.quotes).toHaveLength(1);
    expect(fixture.quotes[0]?.file).toBe('1A/1A-1.pdf');
    expect(fixture.quotes[0]?.filePath).toBe(
      path.resolve(sampleDir, '1A/1A-1.pdf'),
    );
    expect(fixture.e2e.documentParser.pdfEngine).toBe('mistral-ocr');
  });

  it('discovers folder-based metadata fixtures and ignores the root example file', () => {
    const sampleDir = path.resolve(__dirname, '../../../../../sample');
    const fixtures = discoverProviderQuoteFixtures(sampleDir);

    expect(fixtures.length).toBeGreaterThan(0);
    fixtures.forEach((fixture) => {
      expect(path.basename(fixture.metadataPath)).toBe('metadata.json');
      expect(fixture.requisitionId).not.toBe('vehicle-service-rfq');
      expect(fixture.e2e.requiresLiveProvider).toBe(true);
      fixture.quotes.forEach((quote) => {
        expect(fs.existsSync(quote.filePath), quote.filePath).toBe(true);
      });
    });
  });
});

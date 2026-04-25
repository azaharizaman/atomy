import fs from 'node:fs';
import path from 'node:path';

export interface ProviderQuoteFixtureInput {
  baseDir: string;
  metadataPath: string;
}

export interface ProviderQuoteFixture {
  metadataPath: string;
  baseDir: string;
  requisitionId: string;
  title: string;
  buyer: {
    name: string;
    tenantReference: string;
  };
  currency: string;
  submissionDeadlineDaysFromNow: number;
  rfqLineItems: Array<{
    lineReference: string;
    description: string;
    quantity: number;
    uom: string;
    expectedKeywords: string[];
  }>;
  quotes: Array<{
    vendorReference: string;
    vendorName: string;
    file: string;
    filePath: string;
    mimeType: string;
    expected: {
      quoteNumber: string | null;
      quoteDate: string | null;
      currency: string | null;
      totalAmount: number | null;
      subtotalAmount: number | null;
      discountAmount: number | null;
      lineCountMin: number;
      lineKeywords: string[];
      paymentTermsKeywords: string[];
      validityKeywords: string[];
    };
  }>;
  e2e: {
    requiresLiveProvider: boolean;
    provider: string;
    documentParser: {
      plugin: string;
      pdfEngine: string;
    };
    assertions: {
      uploadStatus: string[];
      sourceLinesMin: number;
      allowManualMappingCompletion: boolean;
    };
  };
}

export function discoverProviderQuoteFixtures(sampleRoot: string): ProviderQuoteFixture[] {
  const absoluteRoot = path.resolve(sampleRoot);
  if (!fs.existsSync(absoluteRoot)) {
    return [];
  }

  return fs
    .readdirSync(absoluteRoot, { withFileTypes: true })
    .filter((entry) => entry.isDirectory())
    .map((entry) => {
      const baseDir = path.join(absoluteRoot, entry.name);

      return parseProviderQuoteFixture({
        baseDir,
        metadataPath: path.join(baseDir, 'metadata.json'),
      });
    })
    .sort((left, right) => left.requisitionId.localeCompare(right.requisitionId));
}

export function parseProviderQuoteFixture(input: ProviderQuoteFixtureInput): ProviderQuoteFixture {
  const metadataPath = path.resolve(input.metadataPath);
  const baseDir = path.resolve(input.baseDir);

  if (!fs.existsSync(metadataPath)) {
    throw new Error(`Provider quote fixture metadata not found: ${metadataPath}`);
  }

  let raw: Record<string, unknown>;

  try {
    raw = JSON.parse(fs.readFileSync(metadataPath, 'utf8')) as Record<string, unknown>;
  } catch (error) {
    const message = error instanceof Error ? error.message : String(error);
    throw new Error(`Failed to parse JSON at ${metadataPath}: ${message}`);
  }

  return {
    metadataPath,
    baseDir,
    requisitionId: expectString(raw.requisition_id, 'requisition_id'),
    title: expectString(raw.title, 'title'),
    buyer: parseBuyer(raw.buyer),
    currency: expectString(raw.currency, 'currency'),
    submissionDeadlineDaysFromNow: expectNumber(
      raw.submission_deadline_days_from_now,
      'submission_deadline_days_from_now',
    ),
    rfqLineItems: expectArray(raw.rfq_line_items, 'rfq_line_items').map((value, index) =>
      parseLineItem(value, `rfq_line_items[${index}]`),
    ),
    quotes: expectArray(raw.quotes, 'quotes').map((value, index) => parseQuote(value, `quotes[${index}]`, baseDir)),
    e2e: parseE2e(raw.e2e),
  };
}

function parseBuyer(value: unknown): ProviderQuoteFixture['buyer'] {
  const buyer = expectRecord(value, 'buyer');
  return {
    name: expectString(buyer.name, 'buyer.name'),
    tenantReference: expectString(buyer.tenant_reference, 'buyer.tenant_reference'),
  };
}

function parseLineItem(
  value: unknown,
  name: string,
): ProviderQuoteFixture['rfqLineItems'][number] {
  const line = expectRecord(value, name);
  return {
    lineReference: expectString(line.line_reference, `${name}.line_reference`),
    description: expectString(line.description, `${name}.description`),
    quantity: expectNumber(line.quantity, `${name}.quantity`),
    uom: expectString(line.uom, `${name}.uom`),
    expectedKeywords: expectStringArray(line.expected_keywords, `${name}.expected_keywords`),
  };
}

function parseQuote(
  value: unknown,
  name: string,
  baseDir: string,
): ProviderQuoteFixture['quotes'][number] {
  const quote = expectRecord(value, name);
  const expected = expectRecord(quote.expected, `${name}.expected`);
  const file = expectString(quote.file, `${name}.file`);
  const filePath = path.join(baseDir, file);
  if (!fs.existsSync(filePath)) {
    throw new Error(`Invalid provider quote fixture: ${name}.file resolved to missing file ${filePath}.`);
  }

  return {
    vendorReference: expectString(quote.vendor_reference, `${name}.vendor_reference`),
    vendorName: expectString(quote.vendor_name, `${name}.vendor_name`),
    file,
    filePath,
    mimeType: expectString(quote.mime_type, `${name}.mime_type`),
    expected: {
      quoteNumber: optionalString(expected.quote_number, `${name}.expected.quote_number`),
      quoteDate: optionalString(expected.quote_date, `${name}.expected.quote_date`),
      currency: optionalString(expected.currency, `${name}.expected.currency`),
      totalAmount: optionalNumber(expected.total_amount, `${name}.expected.total_amount`),
      subtotalAmount: optionalNumber(expected.subtotal_amount, `${name}.expected.subtotal_amount`),
      discountAmount: optionalNumber(expected.discount_amount, `${name}.expected.discount_amount`),
      lineCountMin: expectNumber(expected.line_count_min, `${name}.expected.line_count_min`),
      lineKeywords: expectStringArray(expected.line_keywords, `${name}.expected.line_keywords`),
      paymentTermsKeywords: expectStringArray(
        expected.payment_terms_keywords,
        `${name}.expected.payment_terms_keywords`,
      ),
      validityKeywords: expectStringArray(expected.validity_keywords, `${name}.expected.validity_keywords`),
    },
  };
}

function parseE2e(value: unknown): ProviderQuoteFixture['e2e'] {
  const e2e = expectRecord(value, 'e2e');
  const documentParser = expectRecord(e2e.document_parser, 'e2e.document_parser');
  const assertions = expectRecord(e2e.assertions, 'e2e.assertions');

  return {
    requiresLiveProvider: expectBoolean(e2e.requires_live_provider, 'e2e.requires_live_provider'),
    provider: expectString(e2e.provider, 'e2e.provider'),
    documentParser: {
      plugin: expectString(documentParser.plugin, 'e2e.document_parser.plugin'),
      pdfEngine: expectString(documentParser.pdf_engine, 'e2e.document_parser.pdf_engine'),
    },
    assertions: {
      uploadStatus: expectStringArray(assertions.upload_status, 'e2e.assertions.upload_status'),
      sourceLinesMin: expectNumber(assertions.source_lines_min, 'e2e.assertions.source_lines_min'),
      allowManualMappingCompletion: expectBoolean(
        assertions.allow_manual_mapping_completion,
        'e2e.assertions.allow_manual_mapping_completion',
      ),
    },
  };
}

function expectRecord(value: unknown, name: string): Record<string, unknown> {
  if (value === null || typeof value !== 'object' || Array.isArray(value)) {
    throw new Error(`Invalid provider quote fixture: ${name} must be an object.`);
  }

  return value as Record<string, unknown>;
}

function expectArray(value: unknown, name: string): unknown[] {
  if (!Array.isArray(value)) {
    throw new Error(`Invalid provider quote fixture: ${name} must be an array.`);
  }

  return value;
}

function expectString(value: unknown, name: string): string {
  if (typeof value !== 'string' || value.trim() === '') {
    throw new Error(`Invalid provider quote fixture: ${name} must be a non-empty string.`);
  }

  return value;
}

function expectStringArray(value: unknown, name: string): string[] {
  return expectArray(value, name).map((entry, index) => expectString(entry, `${name}[${index}]`));
}

function expectNumber(value: unknown, name: string): number {
  if (typeof value !== 'number' || !Number.isFinite(value)) {
    throw new Error(`Invalid provider quote fixture: ${name} must be a finite number.`);
  }

  return value;
}

function optionalNumber(value: unknown, name: string): number | null {
  if (value === null || value === undefined) {
    return null;
  }

  return expectNumber(value, name);
}

function optionalString(value: unknown, name: string): string | null {
  if (value === null || value === undefined) {
    return null;
  }

  return expectString(value, name);
}

function expectBoolean(value: unknown, name: string): boolean {
  if (typeof value !== 'boolean') {
    throw new Error(`Invalid provider quote fixture: ${name} must be a boolean.`);
  }

  return value;
}

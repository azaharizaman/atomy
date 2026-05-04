'use client';

type CompactScale = {
  threshold: number;
  suffix: string;
};

const COMPACT_SCALES: CompactScale[] = [
  { threshold: 1_000_000_000, suffix: 'B' },
  { threshold: 1_000_000, suffix: 'M' },
  { threshold: 1_000, suffix: 'K' },
];

const COMPACT_CURRENCY_SCALES: CompactScale[] = [
  { threshold: 100_000_000, suffix: 'B' },
  { threshold: 100_000, suffix: 'M' },
  { threshold: 1_000, suffix: 'K' },
];

function numericValue(value: string | number | null | undefined): number | null {
  if (value === null || value === undefined) {
    return null;
  }

  if (typeof value === 'number') {
    return Number.isFinite(value) ? value : null;
  }

  const trimmed = value.trim();
  const normalized = trimmed.replace(/[^0-9.-]/g, '').trim();
  if (normalized === '') {
    return null;
  }

  const parsed = Number(normalized);

  if (!Number.isFinite(parsed)) {
    return null;
  }

  const suffix = trimmed.match(/([kmb])\s*$/i)?.[1]?.toUpperCase();
  const multiplier = suffix === 'B'
    ? 1_000_000_000
    : suffix === 'M'
      ? 1_000_000
      : suffix === 'K'
        ? 1_000
        : 1;

  return parsed * multiplier;
}

function truncateToTenths(value: number): number {
  return Math.trunc(value * 10) / 10;
}

function compactNumber(value: number, scales: CompactScale[] = COMPACT_SCALES): string {
  const sign = value < 0 ? '-' : '';
  const absolute = Math.abs(value);
  const scale = scales.find((candidate) => absolute >= candidate.threshold);

  if (!scale) {
    return `${sign}${Math.round(absolute).toLocaleString('en-US')}`;
  }

  const divisor = scale.suffix === 'B'
    ? 1_000_000_000
    : scale.suffix === 'M'
      ? 1_000_000
      : 1_000;

  return `${sign}${truncateToTenths(absolute / divisor).toFixed(1)}${scale.suffix}`;
}

function currencySymbol(currency: string): string {
  const normalizedCurrency = currency.trim().toUpperCase() || 'USD';

  try {
    const symbol = new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: normalizedCurrency,
      currencyDisplay: 'narrowSymbol',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    })
      .formatToParts(0)
      .find((part) => part.type === 'currency')?.value;

    return symbol ?? `${normalizedCurrency} `;
  } catch {
    return `${normalizedCurrency} `;
  }
}

export function formatCompactCurrency(
  value: string | number | null | undefined,
  currency = 'USD',
): string {
  const parsed = numericValue(value);
  if (parsed === null) {
    return '—';
  }

  return `${currencySymbol(currency)}${compactNumber(parsed, COMPACT_CURRENCY_SCALES)}`;
}

export function formatCompactPercent(value: string | number | null | undefined): string {
  const parsed = numericValue(value);
  if (parsed === null) {
    return '—';
  }

  return `${Math.round(parsed)}%`;
}

export function formatCompactCount(value: string | number | null | undefined): string {
  const parsed = numericValue(value);
  if (parsed === null) {
    return '—';
  }

  return compactNumber(parsed);
}

export function formatCompactMetricValue(value: string | number | null | undefined): string {
  if (value === null || value === undefined) {
    return '—';
  }

  if (typeof value === 'number') {
    return formatCompactCount(value);
  }

  const trimmed = value.trim();
  if (trimmed === '') {
    return '—';
  }

  if (trimmed.includes('%')) {
    return formatCompactPercent(trimmed);
  }

  if (/^[$€£¥]|^[A-Z]{3}\s+/i.test(trimmed)) {
    return formatCompactCurrency(trimmed);
  }

  if (/^-?\d[\d,]*(?:\.\d+)?$/.test(trimmed)) {
    return formatCompactCount(trimmed);
  }

  return value;
}

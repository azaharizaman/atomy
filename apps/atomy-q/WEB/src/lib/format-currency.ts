'use client';

export function formatCurrencyValue(
  value: string | number | null | undefined,
  currency = 'USD',
): string | undefined {
  if (value === null || value === undefined) {
    return undefined;
  }

  if (typeof value === 'string' && value.trim() === '') {
    return undefined;
  }

  const numericValue =
    typeof value === 'number'
      ? value
      : Number(value.replace(/,/g, '').trim());

  if (!Number.isFinite(numericValue)) {
    return typeof value === 'string' ? value.trim() || undefined : undefined;
  }

  const normalizedCurrency = currency.trim().toUpperCase() || 'USD';

  try {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: normalizedCurrency,
    }).format(numericValue);
  } catch {
    return `${normalizedCurrency} ${numericValue.toLocaleString('en-US')}`;
  }
}

import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

export function formatCurrency(
  value: number,
  currency = 'MYR',
  locale = 'en-MY'
): string {
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency,
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value);
}

export function formatDate(
  date: string | Date,
  format: 'short' | 'long' | 'iso' = 'iso'
): string {
  const d = typeof date === 'string' ? new Date(date) : date;
  switch (format) {
    case 'short':
      return d.toLocaleDateString('en-MY', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
      });
    case 'long':
      return d.toLocaleDateString('en-MY', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric',
      });
    case 'iso':
    default:
      return d.toISOString().split('T')[0];
  }
}

export function formatPercentage(value: number, decimals = 1): string {
  return `${value.toFixed(decimals)}%`;
}

export function formatNumber(value: number, locale = 'en-MY'): string {
  return new Intl.NumberFormat(locale).format(value);
}

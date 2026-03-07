export const fontFamilies = {
  sans: '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
  mono: '"JetBrains Mono", "Fira Code", "Cascadia Code", monospace',
} as const;

export const fontSizes = {
  xs: { size: '0.75rem', lineHeight: '1rem', tracking: '0.01em' },
  sm: { size: '0.875rem', lineHeight: '1.25rem', tracking: '0' },
  base: { size: '1rem', lineHeight: '1.5rem', tracking: '0' },
  lg: { size: '1.125rem', lineHeight: '1.75rem', tracking: '-0.01em' },
  xl: { size: '1.25rem', lineHeight: '1.75rem', tracking: '-0.01em' },
  '2xl': { size: '1.5rem', lineHeight: '2rem', tracking: '-0.02em' },
  '3xl': { size: '1.875rem', lineHeight: '2.25rem', tracking: '-0.02em' },
  '4xl': { size: '2.25rem', lineHeight: '2.5rem', tracking: '-0.03em' },
  '5xl': { size: '2.625rem', lineHeight: '3rem', tracking: '-0.03em' },
} as const;

export const fontWeights = {
  normal: 400,
  medium: 500,
  semibold: 600,
  bold: 700,
} as const;

export const typeScale = {
  display: { family: 'sans', size: '5xl', weight: 'semibold', usage: 'Dashboard hero metrics, login page titles' },
  heading1: { family: 'sans', size: '3xl', weight: 'semibold', usage: 'Page titles' },
  heading2: { family: 'sans', size: '2xl', weight: 'semibold', usage: 'Section headings' },
  heading3: { family: 'sans', size: 'xl', weight: 'medium', usage: 'Sub-section headings' },
  heading4: { family: 'sans', size: 'lg', weight: 'medium', usage: 'Card headers, panel titles' },
  body: { family: 'sans', size: 'base', weight: 'normal', usage: 'Primary body text' },
  bodySmall: { family: 'sans', size: 'sm', weight: 'normal', usage: 'Secondary text, table cells' },
  caption: { family: 'sans', size: 'xs', weight: 'normal', usage: 'Labels, hints, timestamps' },
  overline: { family: 'sans', size: 'xs', weight: 'semibold', usage: 'Section labels, category tags (uppercase)' },
  dataLarge: { family: 'mono', size: '2xl', weight: 'semibold', usage: 'KPI values, financial totals' },
  dataMedium: { family: 'mono', size: 'base', weight: 'medium', usage: 'Table numeric data' },
  dataSmall: { family: 'mono', size: 'sm', weight: 'normal', usage: 'IDs, timestamps, reference numbers' },
  code: { family: 'mono', size: 'sm', weight: 'normal', usage: 'Code blocks, API references' },
} as const;

export const spacing = {
  0: '0px',
  0.5: '2px',
  1: '4px',
  1.5: '6px',
  2: '8px',
  2.5: '10px',
  3: '12px',
  3.5: '14px',
  4: '16px',
  5: '20px',
  6: '24px',
  7: '28px',
  8: '32px',
  9: '36px',
  10: '40px',
  11: '44px',
  12: '48px',
  14: '56px',
  16: '64px',
  20: '80px',
  24: '96px',
} as const;

export const spacingScale = [
  { token: 'space-1', value: '4px', rem: '0.25rem', usage: 'Tight gaps: icon-to-text, inline badge padding' },
  { token: 'space-2', value: '8px', rem: '0.5rem', usage: 'Inner padding for compact elements, table cell padding' },
  { token: 'space-3', value: '12px', rem: '0.75rem', usage: 'Form field padding, small card inner spacing' },
  { token: 'space-4', value: '16px', rem: '1rem', usage: 'Default content padding, standard gaps' },
  { token: 'space-5', value: '20px', rem: '1.25rem', usage: 'Card internal spacing, section gaps' },
  { token: 'space-6', value: '24px', rem: '1.5rem', usage: 'Card body padding, major section gaps' },
  { token: 'space-8', value: '32px', rem: '2rem', usage: 'Page-level section separation' },
  { token: 'space-10', value: '40px', rem: '2.5rem', usage: 'Large layout gaps, dashboard card spacing' },
  { token: 'space-12', value: '48px', rem: '3rem', usage: 'Major layout blocks, hero sections' },
  { token: 'space-16', value: '64px', rem: '4rem', usage: 'Top-level page margins, sidebar width units' },
] as const;

export const breakpoints = {
  sm: '640px',
  md: '768px',
  lg: '1024px',
  xl: '1280px',
  '2xl': '1440px',
  '3xl': '1920px',
} as const;

export const breakpointDescriptions = [
  { name: 'sm', value: '640px', usage: 'Small tablets (portrait). Rarely targeted — AtomyQ is desktop-first.' },
  { name: 'md', value: '768px', usage: 'Tablets. Sidebar collapses to icon-only.' },
  { name: 'lg', value: '1024px', usage: 'Large tablets / small laptops. Primary mobile breakpoint.' },
  { name: 'xl', value: '1280px', usage: 'Standard laptop. Default comfortable layout.' },
  { name: '2xl', value: '1440px', usage: 'Standard desktop. Primary design target.' },
  { name: '3xl', value: '1920px', usage: 'Wide desktop. Extra column space for data tables.' },
] as const;

export const radii = {
  none: '0px',
  sm: '4px',
  md: '6px',
  lg: '8px',
  xl: '12px',
  '2xl': '16px',
  full: '9999px',
} as const;

export const shadows = {
  sm: '0 1px 2px rgba(0, 0, 0, 0.05)',
  md: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1)',
  lg: '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1)',
  xl: '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1)',
} as const;

export const elevationDescriptions = [
  { level: 'sm', usage: 'Cards, subtle containers', shadow: shadows.sm },
  { level: 'md', usage: 'Dropdowns, popovers', shadow: shadows.md },
  { level: 'lg', usage: 'Modals, slide-overs', shadow: shadows.lg },
  { level: 'xl', usage: 'Toasts, floating actions', shadow: shadows.xl },
] as const;

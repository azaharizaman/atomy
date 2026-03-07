import { addons } from '@storybook/manager-api';
import { create } from '@storybook/theming/create';

const atomyqTheme = create({
  base: 'light',
  brandTitle: 'AtomyQ Design System',
  brandUrl: '/',
  brandTarget: '_self',

  colorPrimary: '#2563eb',
  colorSecondary: '#1d4ed8',

  appBg: '#f8fafc',
  appContentBg: '#ffffff',
  appPreviewBg: '#f8fafc',
  appBorderColor: '#e2e8f0',
  appBorderRadius: 8,

  textColor: '#0f172a',
  textInverseColor: '#f8fafc',
  textMutedColor: '#64748b',

  barTextColor: '#334155',
  barSelectedColor: '#2563eb',
  barHoverColor: '#1d4ed8',
  barBg: '#ffffff',

  inputBg: '#f3f4f6',
  inputBorder: '#cbd5e1',
  inputTextColor: '#0f172a',
  inputBorderRadius: 6,

  fontBase: '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
  fontCode: '"JetBrains Mono", "Fira Code", monospace',
});

addons.setConfig({
  theme: atomyqTheme,
  sidebar: {
    showRoots: true,
  },
});

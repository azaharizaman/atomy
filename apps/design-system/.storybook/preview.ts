import type { Preview } from '@storybook/react-vite';
import '../src/styles/globals.css';

const preview: Preview = {
  parameters: {
    layout: 'centered',
    controls: {
      expanded: true,
      matchers: {
        color: /(background|color)$/i,
        date: /Date$/i,
      },
      sort: 'requiredFirst',
    },
    actions: {
      argTypesRegex: '^on[A-Z].*',
    },
    backgrounds: {
      options: {
        canvas: { name: 'Canvas', value: '#f8fafc' },
        surface: { name: 'Surface', value: '#ffffff' },
        dark: { name: 'Dark', value: '#0f172a' },
      },
    },
    options: {
      storySort: {
        order: [
          'Design System',
          ['Introduction', 'Design Principles', 'Layout & Page Structure', 'Accessibility', 'Performance', 'API Integration', 'Naming Conventions', 'Developer Guide'],
          'Tokens',
          ['Colors', 'Typography', 'Spacing', 'Elevation', 'Icons'],
          'Components',
          ['Basic', 'Form', 'Data', 'Navigation', 'Feedback'],
          'Layouts',
          ['Page Layouts', 'Modal Layouts'],
          'Patterns',
          ['Interaction Patterns', 'Data Display', 'Permission & Roles', 'Workflow Patterns'],
          'Examples',
          ['Dashboard', 'Quote Comparison', 'Quote Intake', 'Settings'],
        ],
      },
    },
  },
  initialGlobals: {
    backgrounds: {
      value: 'canvas',
    },
  },
  tags: ['autodocs'],
};

export default preview;

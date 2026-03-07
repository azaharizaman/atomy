import type { Preview } from '@storybook/react';
import '../src/styles/globals.css';

const preview: Preview = {
  parameters: {
    layout: 'centered',
    controls: {
      matchers: {
        color: /(background|color)$/i,
        date: /Date$/i,
      },
    },
    backgrounds: {
      default: 'canvas',
      values: [
        { name: 'canvas', value: '#f8fafc' },
        { name: 'surface', value: '#ffffff' },
        { name: 'dark', value: '#0f172a' },
      ],
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
          'Patterns',
          ['Interaction Patterns', 'Data Display', 'Permission & Roles', 'Workflow Patterns'],
          'Examples',
          ['Dashboard', 'Quote Comparison', 'Quote Intake', 'Settings'],
        ],
      },
    },
  },
};

export default preview;

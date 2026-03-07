import type { Preview } from '@storybook/react-vite';
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
      options: {
        canvas: { name: 'canvas', value: '#f8fafc' },
        surface: { name: 'surface', value: '#ffffff' },
        dark: { name: 'dark', value: '#0f172a' }
      }
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

    a11y: {
      // 'todo' - show a11y violations in the test UI only
      // 'error' - fail CI on a11y violations
      // 'off' - skip a11y checks entirely
      test: 'todo'
    }
  },

  initialGlobals: {
    backgrounds: {
      value: 'canvas'
    }
  }
};

export default preview;

/**
 * Atomy-Q Design System — Token Reference
 * These are semantic aliases to Tailwind color utilities.
 * Use these constants for documentation/reference, not for runtime styles.
 */

export const DS_TOKENS = {
  // ─── Base Colors ────────────────────────────────────────────────
  color: {
    // Page & Surface
    pageBg:        'bg-slate-100',          // #F1F5F9 — outer page background
    surface:       'bg-white',              // #FFFFFF — cards, panels
    surfaceMuted:  'bg-slate-50',           // #F8FAFC — subtle panel
    surfaceHover:  'bg-slate-50',           // hover state for rows

    // Borders
    border:        'border-slate-200',      // #E2E8F0 — default border
    borderStrong:  'border-slate-300',      // #CBD5E1 — prominent border

    // Text
    textPrimary:   'text-slate-900',        // #0F172A
    textSecondary: 'text-slate-700',        // #334155
    textMuted:     'text-slate-500',        // #64748B
    textSubtle:    'text-slate-400',        // #94A3B8
    textInverse:   'text-white',            // on dark bg

    // Accent — Interactive (Indigo)
    accent:        'bg-indigo-600',
    accentHover:   'bg-indigo-700',
    accentLight:   'bg-indigo-50',
    accentText:    'text-indigo-600',
    accentBorder:  'border-indigo-300',

    // Semantic — Success (Green)
    success:       'text-green-700',
    successBg:     'bg-green-50',
    successBadge:  'bg-green-100 text-green-700',
    successDot:    'bg-green-500',

    // Semantic — Warning (Amber)
    warning:       'text-amber-700',
    warningBg:     'bg-amber-50',
    warningBadge:  'bg-amber-100 text-amber-700',
    warningDot:    'bg-amber-500',

    // Semantic — Danger (Red)
    danger:        'text-red-700',
    dangerBg:      'bg-red-50',
    dangerBadge:   'bg-red-100 text-red-700',
    dangerDot:     'bg-red-500',

    // Semantic — Info (Blue)
    info:          'text-blue-700',
    infoBg:        'bg-blue-50',
    infoBadge:     'bg-blue-100 text-blue-700',
    infoDot:       'bg-blue-500',

    // Semantic — Neutral/Slate
    neutral:       'text-slate-600',
    neutralBg:     'bg-slate-100',
    neutralBadge:  'bg-slate-100 text-slate-600',
    neutralDot:    'bg-slate-400',
  },

  // ─── Spacing ────────────────────────────────────────────────────
  spacing: {
    base:          4,   // 4px base unit
    contentPad:    20,  // 20–24px content padding
    cardPad:       16,  // 16–20px card padding
    tableRowH:     44,  // 44–48px table row height
  },

  // ─── Layout ─────────────────────────────────────────────────────
  layout: {
    sidebarW:      200, // px — Default Layout sidebar
    railW:         48,  // px — Workspace Layout collapsed rail
    activeMenuW:   360, // px — Workspace Layout Active Record Menu
    topBarH:       56,  // px
    footerH:       36,  // px
  },

  // ─── Typography ─────────────────────────────────────────────────
  typography: {
    fontFamily:    "'Inter', system-ui, -apple-system, sans-serif",
    fontMono:      "'JetBrains Mono', 'Fira Code', monospace",
    // Weights: 400 body, 500 label/caption, 600 subheading, 700 heading
  },

  // ─── Shadows ────────────────────────────────────────────────────
  shadow: {
    card:     'shadow-sm',
    popover:  'shadow-md',
    slideover:'shadow-xl',
    topbar:   'shadow-sm',
  },
} as const;

/** Generic classification chip (Tag) — maps to DS_TOKENS semantic families */
export type TagVariant = 'neutral' | 'success' | 'warning' | 'danger' | 'info' | 'accent';

export type StatusVariant =
  | 'active' | 'closed' | 'awarded' | 'draft' | 'pending'
  | 'approved' | 'rejected' | 'processing' | 'locked' | 'stale'
  | 'error' | 'paid' | 'due' | 'overdue' | 'unpaid' | 'preview'
  | 'final' | 'generated' | 'publishing' | 'new';

export type SLAVariant = 'safe' | 'warning' | 'overdue';
export type ConfidenceVariant = 'high' | 'medium' | 'low';
export type ButtonVariant = 'primary' | 'secondary' | 'ghost' | 'outline' | 'destructive';
export type ButtonSize = 'xs' | 'sm' | 'md' | 'lg';
export type SlideOverWidth = 'sm' | 'md' | 'lg' | 'xl';
export type AvatarSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl';

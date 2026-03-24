# RFQ AI Insights Sidebar - Design Specification

**Date:** 2026-03-24  
**Status:** Approved  
**Location:** `docs/superpowers/specs/2026-03-24-rfq-ai-insights-sidebar-design.md`

## 1. Overview

Add a collapsible right sidebar to the RFQ workspace that displays AI insights, comparison runs, and risk items. The sidebar appears on all RFQ sub-pages. For new RFQs or when data isn't available, placeholder content is shown instead.

## 2. Layout Integration

- **Container**: Add to `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/layout.tsx`
- **Position**: Right of the main content area, between content and the edge of the viewport
- **Width**: 288px (w-72) expanded, 48px collapsed (icon only)
- **Default State**: Expanded (visible by default)
- **Toggle**: Chevron button in sidebar header to collapse/expand

```
┌────────────────────────────────────────────────────────────┐
│  Rail  │  Header                                           │
│        ├───────────────────────────────────────┬───────────┤
│        │  Active Record Menu                    │           │
│  48px  │  ┌─────────────────────────────────┐   │  288px  │
│        │  │     Main Content Area           │   │  Sidebar │
│        │  │                                 │   │           │
│        │  │                                 │   │  ┌─────┐ │
│        │  │                                 │   │  │AI   │ │
│        │  └─────────────────────────────────┘   │  │Insight│ │
│        │                                       │  │s     │ │
│        ├───────────────────────────────────────┤  │       │ │
│        │  Footer                               │  └─────┘ │
└────────────────────────────────────────────────────────────┘
```

## 3. Component Structure

### 3.1 RfqInsightsSidebar (main component)

**Location**: `apps/atomy-q/WEB/src/components/workspace/rfq-insights-sidebar.tsx`

**Props**:
```typescript
interface RfqInsightsSidebarProps {
  rfqId: string;
  isNewRfq?: boolean;
}
```

**State**:
- `expanded` - boolean, default `true`

### 3.2 Cards (within sidebar)

1. **AI Insights Card**
   - Title: "AI Insights"
   - Content when data available: Summary text, key metrics
   - Placeholder when no data: "No insights available yet. Insights will appear once you have quotes and comparison data."

2. **Comparison Runs Card**
   - Title: "Comparison Runs"
   - Shows: Latest run name, status badge (draft/preview/locked), created date
   - Link to full comparison page
   - Placeholder: "No comparison runs yet"

3. **Risk Items Card**
   - Title: "Risk Items"
   - Shows: Count by severity (high/medium/low), summary
   - Link to risk page
   - Placeholder: "No risk items detected"

## 4. Data Flow

### 4.1 Hooks Used

- `useRfq(rfqId)` - Basic RFQ data (status, title)
- `useRfqOverview(rfqId)` - Comparison, approvals, normalization data

### 4.2 Placeholder Detection

An RFQ is considered "new" (show placeholders) when:
- Status is `draft` AND no quotes received
- OR `isNewRfq` prop is explicitly true

## 5. Styling

- Use existing design system components: `Card`, `SectionCard`, `StatusBadge`
- Colors: Follow existing palette (slate-50 for backgrounds, indigo-600 for accents)
- Spacing: 16px padding inside cards, 12px gap between cards
- Collapsed state: Show only toggle button with rotate animation

## 6. Implementation Order

1. Create `RfqInsightsSidebar` component
2. Add to RFQ workspace layout
3. Wire up data from existing hooks
4. Handle collapsed state
5. Verify placeholder states work correctly

## 7. Acceptance Criteria

- [ ] Sidebar appears on all RFQ sub-pages
- [ ] Sidebar is collapsible (default: expanded)
- [ ] Shows 3 cards: AI Insights, Comparison Runs, Risk Items
- [ ] Placeholder content for new RFQs (draft with no quotes)
- [ ] Uses existing design system components
- [ ] Responsive behavior (consider mobile/tablet views)

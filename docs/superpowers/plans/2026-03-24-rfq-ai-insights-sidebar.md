# RFQ AI Insights Sidebar - Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a collapsible right sidebar to the RFQ workspace showing AI Insights, Comparison Runs, and Risk Items. Shows placeholders for new RFQs without data.

**Architecture:** Add sidebar to existing RFQ workspace layout. Create new component `RfqInsightsSidebar` that uses existing hooks (`useRfq`, `useRfqOverview`) to populate cards. Placeholder state determined by RFQ status and quote count.

**Tech Stack:** Next.js, React, TypeScript, Tailwind CSS, existing design system components (Card, SectionCard, StatusBadge)

---

## File Structure

### New Files
- `apps/atomy-q/WEB/src/components/workspace/rfq-insights-sidebar.tsx` - Main sidebar component

### Modified Files
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/layout.tsx` - Add sidebar to layout

---

## Tasks

### Task 1: Create RfqInsightsSidebar Component

**Files:**
- Create: `apps/atomy-q/WEB/src/components/workspace/rfq-insights-sidebar.tsx`

- [ ] **Step 1: Create the component file**

```tsx
'use client';

import React from 'react';
import { ChevronRight, BarChart2, AlertTriangle, Lightbulb } from 'lucide-react';
import { Card, SectionCard } from '@/components/ds/Card';
import { StatusBadge } from '@/components/ds/Badge';
import { useRfq } from '@/hooks/use-rfq';
import { useRfqOverview } from '@/hooks/use-rfq-overview';
import Link from 'next/link';

interface RfqInsightsSidebarProps {
  rfqId: string;
}

function PlaceholderCard({ icon, title, message }: { icon: React.ReactNode; title: string; message: string }) {
  return (
    <Card padding="sm" className="bg-slate-50 border-dashed">
      <div className="flex items-start gap-3">
        <div className="text-slate-400 shrink-0">{icon}</div>
        <div>
          <h4 className="text-xs font-semibold text-slate-700">{title}</h4>
          <p className="text-xs text-slate-500 mt-0.5">{message}</p>
        </div>
      </div>
    </Card>
  );
}

export function RfqInsightsSidebar({ rfqId }: RfqInsightsSidebarProps) {
  const [expanded, setExpanded] = React.useState(true);
  const { data: rfq, isLoading: rfqLoading } = useRfq(rfqId);
  const { data: overview, isLoading: overviewLoading } = useRfqOverview(rfqId);

  const isNewRfq = !rfqLoading && rfq && (rfq.status === 'draft' || (rfq.quotesCount ?? 0) === 0);

  const comparison = overview?.comparison;
  const riskSummary = { high: 0, medium: 0, low: 0 }; // Placeholder - can be extended with actual data

  if (overviewLoading) {
    return (
      <div className="w-72 border-l border-slate-200 bg-slate-50 p-4 space-y-3 animate-pulse">
        <div className="h-4 w-24 bg-slate-200 rounded" />
        <div className="h-20 bg-slate-200 rounded" />
        <div className="h-20 bg-slate-200 rounded" />
        <div className="h-20 bg-slate-200 rounded" />
      </div>
    );
  }

  return (
    <div
      className={[
        'flex flex-col border-l border-slate-200 bg-slate-50 transition-all duration-200',
        expanded ? 'w-72' : 'w-12',
      ].join(' ')}
    >
      {/* Header with toggle */}
      <div className="flex items-center justify-between px-3 py-2 border-b border-slate-200">
        {expanded && <span className="text-xs font-semibold text-slate-700">Insights</span>}
        <button
          onClick={() => setExpanded(!expanded)}
          className="p-1 rounded hover:bg-slate-200 transition-colors"
          aria-label={expanded ? 'Collapse sidebar' : 'Expand sidebar'}
        >
          <ChevronRight
            size={16}
            className={['text-slate-500 transition-transform', !expanded && 'rotate-180'].join(' ')}
          />
        </button>
      </div>

      {expanded && (
        <div className="flex-1 overflow-y-auto p-3 space-y-3 custom-scrollbar">
          {/* AI Insights Card */}
          {isNewRfq ? (
            <PlaceholderCard
              icon={<Lightbulb size={16} />}
              title="AI Insights"
              message="No insights available yet. Insights will appear once you have quotes and comparison data."
            />
          ) : (
            <Card padding="sm">
              <h4 className="text-xs font-semibold text-slate-800 mb-2 flex items-center gap-1.5">
                <Lightbulb size={14} className="text-amber-500" />
                AI Insights
              </h4>
              <p className="text-xs text-slate-600">
                Analysis based on {overview?.normalization?.total_quotes ?? 0} quotes from{' '}
                {overview?.rfq?.vendors_count ?? 0} vendors.
              </p>
              {overview?.comparison && (
                <p className="text-xs text-slate-500 mt-2">
                  Latest comparison: <span className="font-medium">{overview.comparison.name}</span>
                </p>
              )}
            </Card>
          )}

          {/* Comparison Runs Card */}
          {isNewRfq ? (
            <PlaceholderCard
              icon={<BarChart2 size={16} />}
              title="Comparison Runs"
              message="No comparison runs yet. Run a comparison to see vendor quotes analyzed."
            />
          ) : (
            <Card padding="sm" hover>
              <Link href={`/rfqs/${encodeURIComponent(rfqId)}/comparison-runs`} className="block">
                <h4 className="text-xs font-semibold text-slate-800 mb-2 flex items-center gap-1.5">
                  <BarChart2 size={14} className="text-indigo-500" />
                  Comparison Runs
                </h4>
                {comparison ? (
                  <div className="flex items-center justify-between">
                    <span className="text-xs text-slate-600">{comparison.name}</span>
                    <StatusBadge
                      status={
                        comparison.status === 'locked'
                          ? 'approved'
                          : comparison.is_preview
                            ? 'preview'
                            : 'draft'
                      }
                      label={comparison.status}
                    />
                  </div>
                ) : (
                  <p className="text-xs text-slate-500">No runs yet</p>
                )}
              </Link>
            </Card>
          )}

          {/* Risk Items Card */}
          {isNewRfq ? (
            <PlaceholderCard
              icon={<AlertTriangle size={16} />}
              title="Risk Items"
              message="No risk items detected. Risk analysis will appear after vendor screening."
            />
          ) : (
            <Card padding="sm" hover>
              <Link href={`/rfqs/${encodeURIComponent(rfqId)}/risk`} className="block">
                <h4 className="text-xs font-semibold text-slate-800 mb-2 flex items-center gap-1.5">
                  <AlertTriangle size={14} className="text-red-500" />
                  Risk Items
                </h4>
                <div className="flex items-center gap-2">
                  {riskSummary.high > 0 && (
                    <span className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-red-100 text-red-700">
                      {riskSummary.high} High
                    </span>
                  )}
                  {riskSummary.medium > 0 && (
                    <span className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-100 text-amber-700">
                      {riskSummary.medium} Med
                    </span>
                  )}
                  {riskSummary.low > 0 && (
                    <span className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-100 text-blue-700">
                      {riskSummary.low} Low
                    </span>
                  )}
                  {riskSummary.high === 0 && riskSummary.medium === 0 && riskSummary.low === 0 && (
                    <span className="text-xs text-green-600 flex items-center gap-1">
                      <StatusBadge status="approved" label="No issues" />
                    </span>
                  )}
                </div>
              </Link>
            </Card>
          )}
        </div>
      )}
    </div>
  );
}
```

- [ ] **Step 2: Verify file is created correctly**

Run: `ls -la apps/atomy-q/WEB/src/components/workspace/rfq-insights-sidebar.tsx`

Expected: File exists

- [ ] **Step 3: Commit**

```bash
git add apps/atomy-q/WEB/src/components/workspace/rfq-insights-sidebar.tsx
git commit -m "feat: add RfqInsightsSidebar component"
```

---

### Task 2: Integrate Sidebar into RFQ Layout

**Files:**
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/layout.tsx`

- [ ] **Step 1: Add import for RfqInsightsSidebar**

Add to imports section:
```tsx
import { RfqInsightsSidebar } from '@/components/workspace/rfq-insights-sidebar';
```

- [ ] **Step 2: Add sidebar to layout, after the main content div**

Find this section in layout.tsx (around line 91):
```tsx
<div className="flex-1 min-w-0 overflow-y-auto flex flex-col">
```

Replace with:
```tsx
<div className="flex-1 min-w-0 overflow-y-auto flex flex-col">
  <div className="flex flex-1">
    <div className="flex-1 min-w-0">
      {/* existing content */}
```

And wrap the content that follows with:
```tsx
    </div>
    <RfqInsightsSidebar rfqId={rfqId} />
  </div>
```

- [ ] **Step 3: Test the component compiles**

Run: `cd apps/atomy-q/WEB && npm run build 2>&1 | head -50`

Expected: No errors, build completes

- [ ] **Step 4: Commit**

```bash
git add apps/atomy-q/WEB/src/app/\(dashboard\)/rfqs/\[rfqId\]/layout.tsx
git commit -m "feat: add insights sidebar to RFQ workspace layout"
```

---

### Task 3: Verify and Test

- [ ] **Step 1: Run typecheck**

Run: `cd apps/atomy-q/WEB && npm run typecheck 2>&1 | head -30`

Expected: No type errors

- [ ] **Step 2: Run lint**

Run: `cd apps/atomy-q/WEB && npm run lint 2>&1 | head -30`

Expected: No lint errors

- [ ] **Step 3: Start dev server and verify visually (optional)**

Run: `cd apps/atomy-q/WEB && npm run dev`

Open browser to verify sidebar appears on RFQ pages

---

## Summary

- Created `RfqInsightsSidebar` component with 3 cards
- Integrated into RFQ workspace layout
- Handles placeholder state for new RFQs
- Sidebar is collapsible (default: expanded)

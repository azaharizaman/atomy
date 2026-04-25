'use client';

import React from 'react';

type StickyPageActionsProps = {
  active: boolean;
  targetRef: React.RefObject<HTMLElement | null>;
  children: React.ReactNode;
  insetClassName?: string;
};

export function StickyPageActions({
  active,
  targetRef,
  children,
  insetClassName = 'max-w-7xl',
}: StickyPageActionsProps) {
  const [showDock, setShowDock] = React.useState(false);

  React.useEffect(() => {
    if (!active) {
      setShowDock(false);
      return;
    }

    if (typeof IntersectionObserver === 'undefined' || !targetRef.current) {
      setShowDock(false);
      return;
    }

    const observer = new IntersectionObserver(
      ([entry]) => {
        setShowDock(!entry.isIntersecting);
      },
      { threshold: 0.95 },
    );

    observer.observe(targetRef.current);
    return () => observer.disconnect();
  }, [active, targetRef]);

  if (!active || !showDock) {
    return null;
  }

  return (
    <div className="pointer-events-none fixed inset-x-0 bottom-4 z-30 px-4">
      <div className={`mx-auto flex justify-end ${insetClassName}`}>
        <div
          data-testid="sticky-page-actions"
          className="pointer-events-auto flex items-center gap-2 rounded-xl border border-slate-200 bg-white/95 px-3 py-3 shadow-lg backdrop-blur"
        >
          {children}
        </div>
      </div>
    </div>
  );
}

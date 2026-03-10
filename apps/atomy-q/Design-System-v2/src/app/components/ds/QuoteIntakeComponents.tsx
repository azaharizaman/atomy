import React from 'react';
import { RotateCcw, Link2 } from 'lucide-react';
import { Button } from './Button';
import { Alert } from './Alert';

interface ValidationCalloutProps {
  title?: string;
  issues: string[];
  variant?: 'warning' | 'error' | 'info';
}

export function ValidationCallout({
  title = 'Validation Results',
  issues,
  variant = 'warning',
}: ValidationCalloutProps) {
  if (issues.length === 0) {
    return (
      <Alert variant="success" title={title}>
        All validation checks passed.
      </Alert>
    );
  }

  return <Alert variant={variant} title={title} list={issues} />;
}

interface OverrideChipProps {
  label?: string;
}

export function OverrideChip({ label = 'Override' }: OverrideChipProps) {
  return (
    <span className="inline-flex items-center h-5 rounded-full px-2 text-xs font-medium bg-blue-50 border border-blue-200 text-blue-700">
      <Link2 size={10} className="mr-1" />
      {label}
    </span>
  );
}

interface RevertControlProps {
  onClick?: () => void;
}

export function RevertControl({ onClick }: RevertControlProps) {
  return (
    <Button variant="ghost" size="sm" icon={<RotateCcw size={12} />} onClick={onClick}>
      Revert
    </Button>
  );
}

interface QuoteDetailActionBarProps {
  onReject?: () => void;
  onAccept?: () => void;
  onAcceptNormalize?: () => void;
  onReplace?: () => void;
  onReparse?: () => void;
  disabled?: boolean;
}

export function QuoteDetailActionBar({
  onReject,
  onAccept,
  onAcceptNormalize,
  onReplace,
  onReparse,
  disabled = false,
}: QuoteDetailActionBarProps) {
  return (
    <div className="sticky bottom-0 z-10 border-t border-slate-200 bg-white px-4 py-3">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2">
          <Button variant="outline" size="sm" onClick={onReplace} disabled={disabled}>
            Replace Document
          </Button>
          <Button variant="ghost" size="sm" onClick={onReparse} disabled={disabled}>
            Re-Parse
          </Button>
        </div>
        <div className="flex items-center gap-2">
          <Button variant="ghost" size="sm" onClick={onReject} disabled={disabled}>
            Reject
          </Button>
          <Button variant="secondary" size="sm" onClick={onAccept} disabled={disabled}>
            Accept
          </Button>
          <Button size="sm" onClick={onAcceptNormalize} disabled={disabled}>
            Accept & Normalize
          </Button>
        </div>
      </div>
    </div>
  );
}

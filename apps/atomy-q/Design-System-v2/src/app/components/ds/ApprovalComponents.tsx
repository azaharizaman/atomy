import React from 'react';
import { AvatarLabel } from './Avatar';
import { Button } from './Button';
import { Textarea, SelectInput } from './Input';
import { Card } from './Card';
import { SecondaryTabs } from './Tabs';

export function PriorityMarker({ priority }: { priority: 'high' | 'medium' | 'low' }) {
  const styles = {
    high: 'bg-red-50 text-red-700 border border-red-200',
    medium: 'bg-amber-50 text-amber-700 border border-amber-200',
    low: 'bg-green-50 text-green-700 border border-green-200',
  };
  return (
    <span className={['inline-flex h-5 items-center rounded-full px-2 text-xs font-medium', styles[priority]].join(' ')}>
      {priority.toUpperCase()}
    </span>
  );
}

interface AssignmentControlProps {
  value: string;
  onChange: (value: string) => void;
  options: { value: string; label: string }[];
}

export function AssignmentControl({ value, onChange, options }: AssignmentControlProps) {
  return (
    <SelectInput
      label="Assignee"
      compact
      value={value}
      onChange={e => onChange(e.target.value)}
      options={options}
    />
  );
}

interface SnoozeControlProps {
  onSnooze?: (duration: string) => void;
}

export function SnoozeControl({ onSnooze }: SnoozeControlProps) {
  const [duration, setDuration] = React.useState('4h');
  return (
    <div className="flex items-end gap-2">
      <SelectInput
        label="Snooze"
        compact
        value={duration}
        onChange={e => setDuration(e.target.value)}
        options={[
          { value: '4h', label: '4 hours' },
          { value: '8h', label: '8 hours' },
          { value: '1d', label: '1 day' },
          { value: '2d', label: '2 days' },
        ]}
      />
      <Button size="sm" variant="outline" onClick={() => onSnooze?.(duration)}>Apply</Button>
    </div>
  );
}

interface EvidenceTabsPanelProps {
  tabs: { id: string; label: string; count?: number }[];
  activeTab: string;
  onChange: (id: string) => void;
  children: React.ReactNode;
}

export function EvidenceTabsPanel({
  tabs,
  activeTab,
  onChange,
  children,
}: EvidenceTabsPanelProps) {
  return (
    <Card padding="md">
      <SecondaryTabs tabs={tabs} activeTab={activeTab} onChange={onChange} />
      <div className="mt-3">{children}</div>
    </Card>
  );
}

interface DecisionPanelProps {
  reason: string;
  onReasonChange: (value: string) => void;
  onApprove?: () => void;
  onReject?: () => void;
  onReturn?: () => void;
}

export function DecisionPanel({
  reason,
  onReasonChange,
  onApprove,
  onReject,
  onReturn,
}: DecisionPanelProps) {
  return (
    <Card padding="md">
      <p className="text-sm font-semibold text-slate-800 mb-2">Decision</p>
      <div className="flex flex-col gap-2">
        <Button variant="primary" onClick={onApprove}>Approve</Button>
        <Button variant="destructive" onClick={onReject}>Reject</Button>
        <Button variant="outline" onClick={onReturn}>Return for Revision</Button>
      </div>
      <div className="mt-3">
        <Textarea
          label="Reason (required)"
          value={reason}
          onChange={e => onReasonChange(e.target.value)}
          placeholder="Provide your decision rationale..."
          required
        />
      </div>
    </Card>
  );
}

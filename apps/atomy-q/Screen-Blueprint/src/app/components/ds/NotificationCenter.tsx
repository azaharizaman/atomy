import React from 'react';
import { Bell, CheckCheck } from 'lucide-react';
import { SlideOver, SlideOverSection } from './SlideOver';
import { Button } from './Button';
import { StatusDot } from './Badge';

export interface NotificationItem {
  id: string;
  title: string;
  description?: string;
  timestamp: string;
  unread?: boolean;
}

interface NotificationCenterProps {
  open: boolean;
  onClose: () => void;
  items: NotificationItem[];
  onItemClick?: (id: string) => void;
  onMarkAllRead?: () => void;
}

export function NotificationCenter({
  open,
  onClose,
  items,
  onItemClick,
  onMarkAllRead,
}: NotificationCenterProps) {
  return (
    <SlideOver
      open={open}
      onClose={onClose}
      width="sm"
      title="Notifications"
      subtitle="Recent updates and alerts"
      footer={
        <>
          <Button variant="ghost" onClick={onClose}>Close</Button>
          <Button variant="secondary" icon={<CheckCheck size={13} />} onClick={onMarkAllRead}>
            Mark all as read
          </Button>
        </>
      }
    >
      <SlideOverSection noPad>
        {items.length === 0 ? (
          <div className="px-5 py-10 text-center">
            <Bell size={20} className="mx-auto text-slate-300 mb-2" />
            <p className="text-sm text-slate-500">No notifications</p>
          </div>
        ) : (
          <div className="divide-y divide-slate-100">
            {items.map(item => (
              <button
                key={item.id}
                onClick={() => onItemClick?.(item.id)}
                className="w-full text-left px-5 py-3 hover:bg-slate-50 transition-colors"
              >
                <div className="flex items-start gap-2">
                  <div className="pt-1">
                    <StatusDot color={item.unread ? 'amber' : 'slate'} />
                  </div>
                  <div className="min-w-0 flex-1">
                    <div className="flex items-start justify-between gap-2">
                      <p className={['text-sm', item.unread ? 'font-semibold text-slate-800' : 'font-medium text-slate-700'].join(' ')}>
                        {item.title}
                      </p>
                      <span className="text-[11px] text-slate-400 shrink-0">{item.timestamp}</span>
                    </div>
                    {item.description && <p className="text-xs text-slate-500 mt-0.5">{item.description}</p>}
                  </div>
                </div>
              </button>
            ))}
          </div>
        )}
      </SlideOverSection>
    </SlideOver>
  );
}

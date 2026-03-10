import React from 'react';
import {
  Mail,
  UserCircle,
  Phone,
  MapPin,
  LogOut,
  Pencil,
} from 'lucide-react';
import * as HoverCardPrimitive from '@radix-ui/react-hover-card';
import { Avatar } from './Avatar';
import { Button } from './Button';

// ─── Types ────────────────────────────────────────────────────────────────────

export interface TablePersonHoverCardProps {
  /** Label shown at top of card (e.g. "Product Owner", "Vendor") */
  roleLabel: string;
  /** Display name */
  name: string;
  /** Email address */
  email?: string;
  /** Profile image URL */
  avatarUrl?: string;
  /** "Reports to" person name */
  reportsTo?: string;
  /** Mobile number */
  mobile?: string;
  /** Location text */
  location?: string;
  /** Called when user clicks Leave */
  onLeave?: () => void;
  /** Called when user clicks Change */
  onChange?: () => void;
  /** Optional class for the trigger wrapper */
  className?: string;
}

const iconClass = 'shrink-0 text-slate-400';

// ─── Table Person Hover Card ──────────────────────────────────────────────────
/**
 * Hover pop-up card reserved for Owner name and Vendor name cells in data table views.
 * Renders a trigger (avatar + name); on hover shows a card with contact details and
 * Leave / Change actions.
 */
export function TablePersonHoverCard({
  roleLabel,
  name,
  email,
  avatarUrl,
  reportsTo,
  mobile,
  location,
  onLeave,
  onChange,
  className = '',
}: TablePersonHoverCardProps) {
  return (
    <HoverCardPrimitive.Root openDelay={200} closeDelay={100}>
      <HoverCardPrimitive.Trigger asChild>
        <button
          type="button"
          className={[
            'inline-flex items-center gap-2 min-w-0 rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-1',
            'text-left cursor-pointer hover:opacity-90 transition-opacity',
            className,
          ].join(' ')}
          onClick={(e) => e.stopPropagation()}
        >
          <Avatar src={avatarUrl} name={name} size="xs" />
          <span className="text-sm text-slate-700 truncate">{name}</span>
        </button>
      </HoverCardPrimitive.Trigger>
      <HoverCardPrimitive.Portal>
        <HoverCardPrimitive.Content
          side="right"
          align="start"
          sideOffset={8}
          className="z-50 w-72 rounded-lg border border-slate-200 bg-white p-4 shadow-md outline-none data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=right]:slide-in-from-left-2"
          onClick={(e) => e.stopPropagation()}
        >
          <p className="text-xs font-medium text-slate-500 uppercase tracking-wide mb-3">
            {roleLabel}
          </p>
          <div className="flex gap-3 mb-4">
            <Avatar src={avatarUrl} name={name} size="lg" />
            <div className="min-w-0 flex-1">
              <p className="text-sm font-semibold text-slate-900 truncate">{name}</p>
              {email && (
                <div className="flex items-center gap-1.5 mt-0.5 text-slate-500">
                  <Mail size={12} className={iconClass} />
                  <span className="text-xs truncate">{email}</span>
                </div>
              )}
            </div>
          </div>
          <div className="space-y-2 mb-4">
            {reportsTo && (
              <div className="flex items-center gap-2 text-xs text-slate-600">
                <UserCircle size={14} className={iconClass} />
                <span>Reports to: {reportsTo}</span>
              </div>
            )}
            {mobile && (
              <div className="flex items-center gap-2 text-xs text-slate-600">
                <Phone size={14} className={iconClass} />
                <span>Mobile: {mobile}</span>
              </div>
            )}
            {location && (
              <div className="flex items-center gap-2 text-xs text-slate-600">
                <MapPin size={14} className={iconClass} />
                <span>Location: {location}</span>
              </div>
            )}
          </div>
          <div className="flex items-center gap-2 pt-1 border-t border-slate-100">
            {onLeave && (
              <Button
                variant="outline"
                size="sm"
                icon={<LogOut size={12} />}
                onClick={(e) => {
                  e.preventDefault();
                  onLeave();
                }}
              >
                Leave
              </Button>
            )}
            {onChange && (
              <Button
                variant="primary"
                size="sm"
                icon={<Pencil size={12} />}
                onClick={(e) => {
                  e.preventDefault();
                  onChange();
                }}
              >
                Change
              </Button>
            )}
          </div>
        </HoverCardPrimitive.Content>
      </HoverCardPrimitive.Portal>
    </HoverCardPrimitive.Root>
  );
}

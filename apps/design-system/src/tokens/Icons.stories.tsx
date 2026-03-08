import type { Meta, StoryObj } from '@storybook/react-vite';
import {
  LayoutDashboard, FileText, Inbox, GitCompare, Building2, CheckCircle,
  MessageSquare, History, Settings2, Shield, AlertTriangle, BarChart3,
  Users, Settings, Plug, Search, Plus, ChevronDown, ChevronRight,
  Bell, Sparkles, Download, Upload, Trash2, Edit, Eye, EyeOff,
  Copy, ExternalLink, Filter, RefreshCw, MoreHorizontal, X,
  ArrowUpDown, ArrowUp, ArrowDown, Check, Minus, Info, AlertCircle,
  TrendingUp, TrendingDown, Home, Star, Clock, Calendar, Mail,
  LogOut, User, HelpCircle, Zap,
} from 'lucide-react';

const meta: Meta = {
  title: 'Tokens/Icons',
  parameters: { layout: 'padded' },
};

export default meta;

const iconGroups = [
  {
    name: 'Navigation',
    icons: [
      { name: 'LayoutDashboard', Icon: LayoutDashboard },
      { name: 'FileText', Icon: FileText },
      { name: 'Inbox', Icon: Inbox },
      { name: 'GitCompare', Icon: GitCompare },
      { name: 'Building2', Icon: Building2 },
      { name: 'CheckCircle', Icon: CheckCircle },
      { name: 'MessageSquare', Icon: MessageSquare },
      { name: 'History', Icon: History },
      { name: 'Settings2', Icon: Settings2 },
      { name: 'Shield', Icon: Shield },
      { name: 'AlertTriangle', Icon: AlertTriangle },
      { name: 'BarChart3', Icon: BarChart3 },
      { name: 'Users', Icon: Users },
      { name: 'Settings', Icon: Settings },
      { name: 'Plug', Icon: Plug },
    ],
  },
  {
    name: 'Actions',
    icons: [
      { name: 'Search', Icon: Search },
      { name: 'Plus', Icon: Plus },
      { name: 'Edit', Icon: Edit },
      { name: 'Trash2', Icon: Trash2 },
      { name: 'Copy', Icon: Copy },
      { name: 'Download', Icon: Download },
      { name: 'Upload', Icon: Upload },
      { name: 'ExternalLink', Icon: ExternalLink },
      { name: 'Filter', Icon: Filter },
      { name: 'RefreshCw', Icon: RefreshCw },
      { name: 'MoreHorizontal', Icon: MoreHorizontal },
      { name: 'X', Icon: X },
      { name: 'Sparkles', Icon: Sparkles },
      { name: 'Zap', Icon: Zap },
    ],
  },
  {
    name: 'Status & Feedback',
    icons: [
      { name: 'Check', Icon: Check },
      { name: 'Minus', Icon: Minus },
      { name: 'Info', Icon: Info },
      { name: 'AlertCircle', Icon: AlertCircle },
      { name: 'AlertTriangle', Icon: AlertTriangle },
      { name: 'TrendingUp', Icon: TrendingUp },
      { name: 'TrendingDown', Icon: TrendingDown },
      { name: 'Star', Icon: Star },
      { name: 'Eye', Icon: Eye },
      { name: 'EyeOff', Icon: EyeOff },
      { name: 'Bell', Icon: Bell },
    ],
  },
  {
    name: 'Navigation Arrows',
    icons: [
      { name: 'ChevronDown', Icon: ChevronDown },
      { name: 'ChevronRight', Icon: ChevronRight },
      { name: 'ArrowUpDown', Icon: ArrowUpDown },
      { name: 'ArrowUp', Icon: ArrowUp },
      { name: 'ArrowDown', Icon: ArrowDown },
      { name: 'Home', Icon: Home },
    ],
  },
  {
    name: 'Data & Meta',
    icons: [
      { name: 'Clock', Icon: Clock },
      { name: 'Calendar', Icon: Calendar },
      { name: 'Mail', Icon: Mail },
      { name: 'User', Icon: User },
      { name: 'LogOut', Icon: LogOut },
      { name: 'HelpCircle', Icon: HelpCircle },
    ],
  },
];

export const IconLibrary: StoryObj = {
  render: () => (
    <div className="space-y-8 max-w-4xl">
      <div>
        <h2 className="text-xl font-semibold mb-1">Icon Library</h2>
        <p className="text-sm text-[var(--aq-text-muted)] mb-2">
          AtomyQ uses <strong>Lucide React</strong> for all icons. Icons are 16px (size-4) by default, 20px (size-5) for emphasis.
        </p>
        <div className="text-xs text-[var(--aq-text-subtle)] space-y-1 mb-6">
          <p>• Always pair icon-only buttons with <code className="bg-[var(--aq-bg-elevated)] px-1 rounded">aria-label</code></p>
          <p>• Use semantic icons that match the action (e.g., Trash2 for delete, Plus for create)</p>
          <p>• Don't use icons purely for decoration — every icon should convey meaning</p>
        </div>
      </div>

      <div className="flex gap-6 items-end mb-8">
        <div className="flex flex-col items-center gap-2">
          <Search className="size-3.5 text-[var(--aq-text-secondary)]" />
          <span className="text-xs text-[var(--aq-text-muted)]">14px (sm)</span>
        </div>
        <div className="flex flex-col items-center gap-2">
          <Search className="size-4 text-[var(--aq-text-secondary)]" />
          <span className="text-xs text-[var(--aq-text-muted)]">16px (default)</span>
        </div>
        <div className="flex flex-col items-center gap-2">
          <Search className="size-5 text-[var(--aq-text-secondary)]" />
          <span className="text-xs text-[var(--aq-text-muted)]">20px (lg)</span>
        </div>
        <div className="flex flex-col items-center gap-2">
          <Search className="size-6 text-[var(--aq-text-secondary)]" />
          <span className="text-xs text-[var(--aq-text-muted)]">24px (xl)</span>
        </div>
      </div>

      {iconGroups.map((group) => (
        <div key={group.name}>
          <h3 className="text-sm font-semibold uppercase tracking-wider text-[var(--aq-text-muted)] mb-3">{group.name}</h3>
          <div className="grid grid-cols-5 gap-2">
            {group.icons.map(({ name, Icon }) => (
              <div key={name} className="flex flex-col items-center gap-1.5 p-3 rounded-lg hover:bg-[var(--aq-bg-elevated)] transition-colors">
                <Icon className="size-5 text-[var(--aq-text-secondary)]" />
                <span className="text-[10px] font-mono text-[var(--aq-text-muted)]">{name}</span>
              </div>
            ))}
          </div>
        </div>
      ))}
    </div>
  ),
};

import { useState, useMemo } from 'react';
import {
  Bell, ClipboardCheck, AtSign, UserPlus, Clock, AlertTriangle,
  Settings, CheckCheck, Trash2, Inbox, Search
} from 'lucide-react';
import { useNavigate } from 'react-router';
import { notifications, getTimeAgo, getUserById } from '../data/mockData';
import type { NotificationType, NotificationPriority } from '../data/mockData';

type FilterTab = 'all' | 'unread' | 'approval' | 'mention' | 'deadline' | 'alert' | 'system';

const filterTabs: { id: FilterTab; label: string }[] = [
  { id: 'all', label: 'All' },
  { id: 'unread', label: 'Unread' },
  { id: 'approval', label: 'Approval' },
  { id: 'mention', label: 'Mentions' },
  { id: 'deadline', label: 'Deadlines' },
  { id: 'alert', label: 'Alerts' },
  { id: 'system', label: 'System' },
];

const typeIcons: Record<NotificationType, typeof Bell> = {
  approval: ClipboardCheck,
  mention: AtSign,
  assignment: UserPlus,
  deadline: Clock,
  alert: AlertTriangle,
  system: Settings,
};

const typeColors: Record<NotificationType, string> = {
  approval: 'bg-indigo-100 text-indigo-600',
  mention: 'bg-sky-100 text-sky-600',
  assignment: 'bg-violet-100 text-violet-600',
  deadline: 'bg-amber-100 text-amber-600',
  alert: 'bg-red-100 text-red-600',
  system: 'bg-slate-100 text-slate-600',
};

const priorityDots: Record<NotificationPriority, string> = {
  urgent: 'bg-red-500',
  high: 'bg-orange-400',
  normal: 'bg-blue-400',
  low: 'bg-slate-300',
};

export function NotificationCenter() {
  const navigate = useNavigate();
  const [filter, setFilter] = useState<FilterTab>('all');
  const [search, setSearch] = useState('');
  const [localNotifications, setLocalNotifications] = useState(
    () => notifications.map(n => ({ ...n }))
  );

  const unreadCount = useMemo(
    () => localNotifications.filter(n => !n.read).length,
    [localNotifications]
  );

  const filtered = useMemo(() => {
    let items = localNotifications;

    if (filter === 'unread') {
      items = items.filter(n => !n.read);
    } else if (filter !== 'all') {
      const typeMap: Record<string, NotificationType> = {
        approval: 'approval',
        mention: 'mention',
        deadline: 'deadline',
        alert: 'alert',
        system: 'system',
      };
      items = items.filter(n => n.type === typeMap[filter]);
    }

    if (search.trim()) {
      const q = search.toLowerCase();
      items = items.filter(
        n => n.title.toLowerCase().includes(q) || n.body.toLowerCase().includes(q)
      );
    }

    return items.sort(
      (a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime()
    );
  }, [localNotifications, filter, search]);

  function markAllRead() {
    setLocalNotifications(prev => prev.map(n => ({ ...n, read: true })));
  }

  function clearAllRead() {
    setLocalNotifications(prev => prev.filter(n => !n.read));
  }

  function toggleRead(id: string) {
    setLocalNotifications(prev =>
      prev.map(n => (n.id === id ? { ...n, read: !n.read } : n))
    );
  }

  function handleClick(notif: (typeof localNotifications)[0]) {
    setLocalNotifications(prev =>
      prev.map(n => (n.id === notif.id ? { ...n, read: true } : n))
    );
    navigate(notif.link);
  }

  function getTabCount(tab: FilterTab): number {
    if (tab === 'all') return localNotifications.length;
    if (tab === 'unread') return unreadCount;
    const typeMap: Record<string, NotificationType> = {
      approval: 'approval',
      mention: 'mention',
      deadline: 'deadline',
      alert: 'alert',
      system: 'system',
    };
    return localNotifications.filter(n => n.type === typeMap[tab]).length;
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <h1 className="text-2xl text-slate-900" style={{ fontWeight: 600 }}>
            Notifications
          </h1>
          {unreadCount > 0 && (
            <span className="inline-flex items-center justify-center min-w-[24px] h-6 px-2 rounded-full bg-indigo-600 text-white text-xs" style={{ fontWeight: 600 }}>
              {unreadCount}
            </span>
          )}
        </div>
        <div className="flex items-center gap-2">
          <button
            onClick={markAllRead}
            disabled={unreadCount === 0}
            className="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed"
          >
            <CheckCheck className="w-4 h-4" />
            Mark all as read
          </button>
          <button
            onClick={clearAllRead}
            disabled={localNotifications.every(n => !n.read)}
            className="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed"
          >
            <Trash2 className="w-4 h-4" />
            Clear all read
          </button>
        </div>
      </div>

      {/* Search + Filters */}
      <div className="space-y-3">
        <div className="relative max-w-md">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
          <input
            type="text"
            placeholder="Search notifications..."
            value={search}
            onChange={e => setSearch(e.target.value)}
            className="w-full pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
          />
        </div>

        <div className="flex gap-1 border-b border-slate-200">
          {filterTabs.map(tab => {
            const count = getTabCount(tab.id);
            const active = filter === tab.id;
            return (
              <button
                key={tab.id}
                onClick={() => setFilter(tab.id)}
                className={`px-3 py-2 text-sm border-b-2 transition-colors ${
                  active
                    ? 'border-indigo-600 text-indigo-600'
                    : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                }`}
                style={{ fontWeight: active ? 600 : 400 }}
              >
                {tab.label}
                {count > 0 && (
                  <span className={`ml-1.5 text-xs px-1.5 py-0.5 rounded-full ${
                    active ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-500'
                  }`}>
                    {count}
                  </span>
                )}
              </button>
            );
          })}
        </div>
      </div>

      {/* Notification Feed */}
      {filtered.length === 0 ? (
        <div className="flex flex-col items-center justify-center py-20 text-slate-400">
          <Inbox className="w-12 h-12 mb-3" />
          <p className="text-lg" style={{ fontWeight: 500 }}>No notifications</p>
          <p className="text-sm mt-1">
            {filter === 'unread'
              ? "You're all caught up!"
              : 'Nothing matches your current filter.'}
          </p>
        </div>
      ) : (
        <div className="bg-white border border-slate-200 rounded-xl overflow-hidden divide-y divide-slate-100">
          {filtered.map(notif => {
            const Icon = typeIcons[notif.type];
            const actor = notif.actorId ? getUserById(notif.actorId) : null;

            return (
              <div
                key={notif.id}
                className={`flex items-start gap-3 px-4 py-3.5 cursor-pointer transition-colors group ${
                  notif.read
                    ? 'bg-white hover:bg-slate-50'
                    : 'bg-indigo-50/30 border-l-[3px] border-l-indigo-500 hover:bg-indigo-50/50'
                }`}
                onClick={() => handleClick(notif)}
              >
                {/* Priority dot */}
                <div className="pt-1.5 flex-shrink-0">
                  <span className={`block w-2.5 h-2.5 rounded-full ${priorityDots[notif.priority]}`} />
                </div>

                {/* Type icon */}
                <div className={`flex-shrink-0 w-9 h-9 rounded-lg flex items-center justify-center ${typeColors[notif.type]}`}>
                  <Icon className="w-4.5 h-4.5" />
                </div>

                {/* Content */}
                <div className="flex-1 min-w-0">
                  <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0">
                      <p
                        className={`text-sm truncate ${notif.read ? 'text-slate-700' : 'text-slate-900'}`}
                        style={{ fontWeight: notif.read ? 400 : 600 }}
                      >
                        {notif.title}
                      </p>
                      <p className="text-sm text-slate-500 mt-0.5 line-clamp-1">
                        {notif.body}
                      </p>
                    </div>
                    <div className="flex items-center gap-2 flex-shrink-0">
                      <span className="text-xs text-slate-400 whitespace-nowrap">
                        {getTimeAgo(notif.createdAt)}
                      </span>
                    </div>
                  </div>

                  <div className="flex items-center gap-2 mt-1.5">
                    <span className={`inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full ${typeColors[notif.type]}`}>
                      {notif.type.charAt(0).toUpperCase() + notif.type.slice(1)}
                    </span>
                    {actor && (
                      <span className="text-xs text-slate-400">
                        by {actor.name}
                      </span>
                    )}
                    <button
                      onClick={e => { e.stopPropagation(); toggleRead(notif.id); }}
                      className="ml-auto text-xs text-slate-400 hover:text-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity"
                    >
                      {notif.read ? 'Mark unread' : 'Mark read'}
                    </button>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}

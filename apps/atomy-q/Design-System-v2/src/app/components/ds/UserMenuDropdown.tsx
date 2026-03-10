import React from 'react';
import { ChevronDown, LogOut, Settings, Bell } from 'lucide-react';
import { Avatar } from './Avatar';

interface UserMenuDropdownProps {
  user: { name: string; role?: string; avatarSrc?: string };
  onUserSettings?: () => void;
  onNotifications?: () => void;
  onLogout?: () => void;
  className?: string;
}

export function UserMenuDropdown({
  user,
  onUserSettings,
  onNotifications,
  onLogout,
  className = '',
}: UserMenuDropdownProps) {
  const [open, setOpen] = React.useState(false);
  const menuRef = React.useRef<HTMLDivElement | null>(null);

  React.useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (!menuRef.current) return;
      if (!menuRef.current.contains(event.target as Node)) {
        setOpen(false);
      }
    }
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  function runAction(action?: () => void) {
    setOpen(false);
    action?.();
  }

  return (
    <div ref={menuRef} className={['relative', className].join(' ')}>
      <button
        className="flex items-center gap-2 pl-1 pr-0.5 py-0.5 rounded-md hover:bg-slate-100 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500"
        aria-label="User menu"
        aria-expanded={open}
        onClick={() => setOpen(v => !v)}
      >
        <Avatar name={user.name} src={user.avatarSrc} size="sm" />
        <span className="text-xs font-medium text-slate-700 max-w-24 truncate hidden sm:block">
          {user.name.split(' ')[0]}
        </span>
        <ChevronDown size={12} className="text-slate-400" />
      </button>

      {open && (
        <div className="absolute right-0 mt-1.5 w-48 rounded-md border border-slate-200 bg-white shadow-lg z-40 py-1">
          <div className="px-3 py-2 border-b border-slate-100">
            <p className="text-xs font-medium text-slate-800 truncate">{user.name}</p>
            {user.role && <p className="text-[11px] text-slate-500 truncate mt-0.5">{user.role}</p>}
          </div>

          <button
            onClick={() => runAction(onUserSettings)}
            className="w-full px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50 flex items-center gap-2 text-left"
          >
            <Settings size={13} className="text-slate-400" />
            User Settings
          </button>
          <button
            onClick={() => runAction(onNotifications)}
            className="w-full px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50 flex items-center gap-2 text-left"
          >
            <Bell size={13} className="text-slate-400" />
            Notifications
          </button>
          <button
            onClick={() => runAction(onLogout)}
            className="w-full px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2 text-left"
          >
            <LogOut size={13} className="text-red-500" />
            Logout
          </button>
        </div>
      )}
    </div>
  );
}

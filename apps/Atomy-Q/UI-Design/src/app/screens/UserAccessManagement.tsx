import { useState } from 'react';
import {
  Search, Plus, X, Shield, ChevronDown, AlertTriangle, Check,
  Edit3, Trash2, UserCheck, Clock, MoreVertical, Filter
} from 'lucide-react';

type UserStatus = 'active' | 'inactive' | 'pending';
type Role = 'Admin' | 'Procurement Lead' | 'Buyer' | 'Approver' | 'Viewer' | 'Finance Reviewer';

interface User {
  id: string;
  name: string;
  email: string;
  role: Role;
  status: UserStatus;
  department: string;
  lastActive: string;
  approvalLimit: number | null;
  delegate?: string;
  mfaEnabled: boolean;
}

const users: User[] = [
  { id: 'u1', name: 'Alex Kumar', email: 'alex.kumar@company.com', role: 'Procurement Lead', status: 'active', department: 'Technology', lastActive: 'Now', approvalLimit: 500000, mfaEnabled: true },
  { id: 'u2', name: 'Sarah Chen', email: 's.chen@company.com', role: 'Buyer', status: 'active', department: 'Operations', lastActive: '2h ago', approvalLimit: 50000, delegate: 'Alex Kumar', mfaEnabled: true },
  { id: 'u3', name: 'Marcus Williams', email: 'm.williams@company.com', role: 'Approver', status: 'active', department: 'Finance', lastActive: '1d ago', approvalLimit: 250000, mfaEnabled: false },
  { id: 'u4', name: 'Jamie Park', email: 'j.park@company.com', role: 'Finance Reviewer', status: 'active', department: 'Finance', lastActive: '3d ago', approvalLimit: null, mfaEnabled: true },
  { id: 'u5', name: 'Lisa Thompson', email: 'l.thompson@company.com', role: 'Viewer', status: 'inactive', department: 'Legal', lastActive: '2 weeks ago', approvalLimit: null, mfaEnabled: false },
  { id: 'u6', name: 'David Okonkwo', email: 'd.okonkwo@company.com', role: 'Admin', status: 'active', department: 'IT', lastActive: '5h ago', approvalLimit: null, mfaEnabled: true },
  { id: 'u7', name: 'Priya Sharma', email: 'p.sharma@company.com', role: 'Buyer', status: 'pending', department: 'Facilities', lastActive: 'Never', approvalLimit: 25000, mfaEnabled: false },
];

const rolePermissions: Record<Role, string[]> = {
  'Admin': ['Manage users', 'Full system access', 'Audit logs', 'Configure policies'],
  'Procurement Lead': ['Create RFQs', 'Award contracts', 'View reports', 'Manage scoring models', 'Negotiate'],
  'Buyer': ['Create RFQs', 'View quotes', 'Normalize quotes', 'Compare quotes'],
  'Approver': ['Approve RFQs', 'Approve awards up to limit', 'View reports'],
  'Finance Reviewer': ['View financial data', 'Approve budgets', 'Export reports'],
  'Viewer': ['View RFQs', 'View quotes (read only)'],
};

const StatusBadge = ({ status }: { status: UserStatus }) => {
  const map = {
    active: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    inactive: 'bg-slate-100 text-slate-500 border-slate-200',
    pending: 'bg-amber-50 text-amber-700 border-amber-200',
  };
  return <span className={`text-xs px-2 py-0.5 rounded-full border capitalize ${map[status]}`} style={{ fontWeight: 600 }}>{status}</span>;
};

export function UserAccessManagement() {
  const [selectedUser, setSelectedUser] = useState<User | null>(users[0]);
  const [editRole, setEditRole] = useState<Role | null>(null);
  const [showConfirm, setShowConfirm] = useState(false);
  const [pendingRole, setPendingRole] = useState<Role | null>(null);
  const [search, setSearch] = useState('');

  const filtered = users.filter(u =>
    u.name.toLowerCase().includes(search.toLowerCase()) ||
    u.email.toLowerCase().includes(search.toLowerCase()) ||
    u.role.toLowerCase().includes(search.toLowerCase())
  );

  const handleRoleChange = (role: Role) => {
    if (role === 'Admin' || selectedUser?.role === 'Admin') {
      setPendingRole(role);
      setShowConfirm(true);
    } else {
      setEditRole(role);
    }
  };

  return (
    <div className="flex h-full bg-slate-50">
      {/* User Table */}
      <div className="flex-1 flex flex-col overflow-hidden">
        {/* Header */}
        <div className="bg-white border-b border-slate-200 px-6 py-4 flex-shrink-0">
          <div className="flex items-center justify-between mb-4">
            <div>
              <h1 className="text-slate-900">User & Access Management</h1>
              <p className="text-slate-500 text-xs mt-0.5">{users.length} users · RBAC with delegation and approval limits</p>
            </div>
            <button className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-3 py-2 text-sm" style={{ fontWeight: 500 }}>
              <Plus size={14} />
              Invite User
            </button>
          </div>
          <div className="flex items-center gap-3">
            <div className="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 flex-1 max-w-xs">
              <Search size={14} className="text-slate-400" />
              <input
                value={search}
                onChange={e => setSearch(e.target.value)}
                className="bg-transparent flex-1 text-sm text-slate-700 outline-none placeholder:text-slate-400"
                placeholder="Search users..."
              />
            </div>
            <button className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
              <Filter size={14} />
              Filter
              <ChevronDown size={12} />
            </button>
          </div>
        </div>

        {/* Table */}
        <div className="flex-1 overflow-auto">
          <table className="w-full">
            <thead className="sticky top-0 z-10 bg-slate-50 border-b border-slate-200">
              <tr>
                {['User', 'Role', 'Department', 'Approval Limit', 'Status', 'MFA', 'Last Active', ''].map(h => (
                  <th key={h} className="text-left px-5 py-3 text-xs text-slate-500" style={{ fontWeight: 600, whiteSpace: 'nowrap' }}>{h}</th>
                ))}
              </tr>
            </thead>
            <tbody className="bg-white">
              {filtered.map(user => (
                <tr
                  key={user.id}
                  onClick={() => setSelectedUser(user)}
                  className={`border-b border-slate-100 cursor-pointer transition-colors hover:bg-slate-50 ${selectedUser?.id === user.id ? 'bg-indigo-50/60' : ''}`}
                >
                  <td className="px-5 py-3">
                    <div className="flex items-center gap-3">
                      <div className="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                        <span className="text-indigo-700 text-xs" style={{ fontWeight: 700 }}>{user.name.split(' ').map(n => n[0]).join('')}</span>
                      </div>
                      <div>
                        <div className="text-slate-800 text-sm" style={{ fontWeight: 500 }}>{user.name}</div>
                        <div className="text-slate-400 text-xs">{user.email}</div>
                      </div>
                    </div>
                  </td>
                  <td className="px-5 py-3">
                    <div className="flex items-center gap-1.5">
                      <Shield size={12} className="text-indigo-400" />
                      <span className="text-slate-700 text-sm">{user.role}</span>
                    </div>
                  </td>
                  <td className="px-5 py-3 text-slate-600 text-sm">{user.department}</td>
                  <td className="px-5 py-3">
                    {user.approvalLimit !== null ? (
                      <span className="text-slate-700 text-sm" style={{ fontWeight: 500 }}>${user.approvalLimit.toLocaleString()}</span>
                    ) : (
                      <span className="text-slate-400 text-sm">No limit</span>
                    )}
                  </td>
                  <td className="px-5 py-3"><StatusBadge status={user.status} /></td>
                  <td className="px-5 py-3">
                    {user.mfaEnabled ? (
                      <span className="flex items-center gap-1 text-emerald-600 text-xs"><Check size={12} />Enabled</span>
                    ) : (
                      <span className="flex items-center gap-1 text-slate-400 text-xs"><X size={12} />Disabled</span>
                    )}
                  </td>
                  <td className="px-5 py-3 text-slate-500 text-sm">{user.lastActive}</td>
                  <td className="px-5 py-3">
                    <button className="text-slate-300 hover:text-slate-600 p-1 rounded">
                      <MoreVertical size={15} />
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* Detail Drawer */}
      {selectedUser && (
        <div className="w-80 flex-shrink-0 bg-white border-l border-slate-200 flex flex-col overflow-hidden">
          <div className="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <h3 className="text-slate-900 text-sm" style={{ fontWeight: 600 }}>User Details</h3>
            <button onClick={() => setSelectedUser(null)} className="text-slate-400 hover:text-slate-600"><X size={16} /></button>
          </div>

          <div className="flex-1 overflow-y-auto p-5 space-y-5">
            {/* User Profile */}
            <div className="text-center">
              <div className="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center mx-auto mb-3">
                <span className="text-indigo-700 text-xl" style={{ fontWeight: 700 }}>{selectedUser.name.split(' ').map(n => n[0]).join('')}</span>
              </div>
              <div className="text-slate-900 text-sm" style={{ fontWeight: 600 }}>{selectedUser.name}</div>
              <div className="text-slate-500 text-xs mt-0.5">{selectedUser.email}</div>
              <div className="mt-2"><StatusBadge status={selectedUser.status} /></div>
            </div>

            {/* Role Assignment */}
            <div className="bg-slate-50 rounded-xl border border-slate-200 p-4">
              <div className="flex items-center justify-between mb-3">
                <span className="text-slate-700 text-xs" style={{ fontWeight: 700 }}>ROLE ASSIGNMENT</span>
                <button onClick={() => setEditRole(selectedUser.role)} className="text-indigo-600 text-xs hover:text-indigo-700" style={{ fontWeight: 500 }}>Change</button>
              </div>
              <div className="flex items-center gap-2 mb-3">
                <Shield size={14} className="text-indigo-500" />
                <span className="text-slate-800 text-sm" style={{ fontWeight: 600 }}>{selectedUser.role}</span>
              </div>
              <div className="space-y-1.5">
                <div className="text-slate-500 text-xs mb-2" style={{ fontWeight: 600 }}>PERMISSIONS</div>
                {rolePermissions[selectedUser.role].map(p => (
                  <div key={p} className="flex items-center gap-1.5 text-xs text-slate-600">
                    <Check size={11} className="text-emerald-500 flex-shrink-0" />
                    <span>{p}</span>
                  </div>
                ))}
              </div>
            </div>

            {/* Approval Limit */}
            <div className="bg-slate-50 rounded-xl border border-slate-200 p-4">
              <div className="text-slate-700 text-xs mb-3" style={{ fontWeight: 700 }}>APPROVAL AUTHORITY</div>
              <div>
                <label className="text-slate-500 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Maximum Approval Limit</label>
                <div className="relative">
                  <span className="absolute left-3 top-2.5 text-slate-400 text-sm">$</span>
                  <input
                    defaultValue={selectedUser.approvalLimit ?? ''}
                    placeholder="No limit"
                    className="w-full border border-slate-200 rounded-lg pl-7 pr-3 py-2.5 text-sm text-slate-800 focus:border-indigo-400 outline-none bg-white"
                  />
                </div>
              </div>
            </div>

            {/* Delegation */}
            <div className="bg-slate-50 rounded-xl border border-slate-200 p-4">
              <div className="text-slate-700 text-xs mb-3" style={{ fontWeight: 700 }}>DELEGATION</div>
              <div>
                <label className="text-slate-500 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Delegate Authority To</label>
                <select className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white">
                  <option value="">None</option>
                  {users.filter(u => u.id !== selectedUser.id).map(u => (
                    <option key={u.id} value={u.id}>{u.name}</option>
                  ))}
                </select>
              </div>
              {selectedUser.delegate && (
                <div className="mt-2 flex items-center gap-1.5 text-xs text-slate-600">
                  <UserCheck size={12} className="text-indigo-500" />
                  <span>Currently delegated to <strong>{selectedUser.delegate}</strong></span>
                </div>
              )}
            </div>

            {/* Account Info */}
            <div className="space-y-2">
              {[
                { label: 'Department', value: selectedUser.department },
                { label: 'Last Active', value: selectedUser.lastActive },
                { label: 'MFA Status', value: selectedUser.mfaEnabled ? 'Enabled' : 'Disabled' },
              ].map(f => (
                <div key={f.label} className="flex justify-between items-center py-1.5 border-b border-slate-100">
                  <span className="text-slate-500 text-xs" style={{ fontWeight: 500 }}>{f.label}</span>
                  <span className="text-slate-700 text-xs">{f.value}</span>
                </div>
              ))}
            </div>
          </div>

          {/* Actions */}
          <div className="border-t border-slate-200 p-4 space-y-2 flex-shrink-0">
            <button className="w-full flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2.5 text-sm" style={{ fontWeight: 500 }}>
              <Edit3 size={14} />
              Save Changes
            </button>
            <button className="w-full flex items-center justify-center gap-2 border border-red-200 bg-red-50 text-red-600 rounded-lg px-4 py-2.5 text-sm hover:bg-red-100">
              <Trash2 size={14} />
              {selectedUser.status === 'active' ? 'Deactivate User' : 'Delete User'}
            </button>
          </div>
        </div>
      )}

      {/* Role Change Modal */}
      {editRole && selectedUser && (
        <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
            <div className="flex items-center justify-between px-6 py-4 border-b border-slate-100">
              <h2 className="text-slate-900 text-sm">Change Role — {selectedUser.name}</h2>
              <button onClick={() => setEditRole(null)} className="text-slate-400 hover:text-slate-600"><X size={18} /></button>
            </div>
            <div className="p-5 space-y-2">
              {(Object.keys(rolePermissions) as Role[]).map(role => (
                <button
                  key={role}
                  onClick={() => handleRoleChange(role)}
                  className={`w-full flex items-start gap-3 px-4 py-3 rounded-xl border text-left transition-colors ${editRole === role ? 'border-indigo-300 bg-indigo-50' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'}`}
                >
                  <Shield size={15} className={editRole === role ? 'text-indigo-600 mt-0.5' : 'text-slate-400 mt-0.5'} />
                  <div>
                    <div className="text-slate-800 text-sm" style={{ fontWeight: editRole === role ? 600 : 400 }}>{role}</div>
                    <div className="text-slate-500 text-xs mt-0.5">{rolePermissions[role].slice(0, 2).join(' · ')}</div>
                  </div>
                  {editRole === role && <Check size={14} className="text-indigo-600 ml-auto mt-0.5" />}
                </button>
              ))}
            </div>
            <div className="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100">
              <button onClick={() => setEditRole(null)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
              <button onClick={() => setEditRole(null)} className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Apply Role Change</button>
            </div>
          </div>
        </div>
      )}

      {/* Confirm Critical Role Change */}
      {showConfirm && (
        <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
            <div className="p-6 text-center">
              <div className="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-4">
                <AlertTriangle size={22} className="text-amber-600" />
              </div>
              <h2 className="text-slate-900 mb-2">Confirm Role Change</h2>
              <p className="text-slate-500 text-sm">
                Changing <strong>{selectedUser?.name}</strong>'s role to <strong>{pendingRole}</strong> is a critical change with elevated permissions. This action is logged.
              </p>
            </div>
            <div className="flex gap-3 px-6 pb-6">
              <button onClick={() => { setShowConfirm(false); setPendingRole(null); }} className="flex-1 border border-slate-200 rounded-lg px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
              <button onClick={() => { setShowConfirm(false); setEditRole(null); }} className="flex-1 bg-amber-600 hover:bg-amber-700 text-white rounded-lg px-4 py-2.5 text-sm" style={{ fontWeight: 500 }}>Confirm Change</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
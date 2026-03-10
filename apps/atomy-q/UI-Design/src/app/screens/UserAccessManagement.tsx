import { useState } from 'react';
import {
  Search, Plus, X, Shield, ChevronDown, AlertTriangle, Check,
  Edit3, Trash2, UserCheck, Clock, MoreVertical, Filter,
  Ban, Users, DollarSign
} from 'lucide-react';
import { SlideOver } from '../components/SlideOver';
import {
  users, roles, delegationRules, statusColors, formatDate, formatDateTime,
  getUserById,
} from '../data/mockData';
import type { UserStatus } from '../data/mockData';

const StatusBadge = ({ status }: { status: string }) => {
  const colors = statusColors[status] ?? 'bg-slate-100 text-slate-600';
  return <span className={`text-xs px-2 py-0.5 rounded-full capitalize ${colors}`} style={{ fontWeight: 600 }}>{status.replace('_', ' ')}</span>;
};

export function UserAccessManagement() {
  const [selectedUserId, setSelectedUserId] = useState<string | null>(users[0]?.id ?? null);
  const [search, setSearch] = useState('');

  const [showInvite, setShowInvite] = useState(false);
  const [showSuspend, setShowSuspend] = useState(false);
  const [showDelegation, setShowDelegation] = useState(false);
  const [showAuthority, setShowAuthority] = useState(false);

  const selectedUser = users.find(u => u.id === selectedUserId) ?? null;
  const selectedRole = roles.find(r => r.id === selectedUser?.roleId);

  const filtered = users.filter(u =>
    u.name.toLowerCase().includes(search.toLowerCase()) ||
    u.email.toLowerCase().includes(search.toLowerCase()) ||
    u.role.toLowerCase().includes(search.toLowerCase())
  );

  const userDelegations = delegationRules.filter(d => d.fromUserId === selectedUserId || d.toUserId === selectedUserId);

  return (
    <div className="flex h-full bg-slate-50">
      {/* User Table */}
      <div className="flex-1 flex flex-col overflow-hidden">
        <div className="bg-white border-b border-slate-200 px-6 py-4 flex-shrink-0">
          <div className="flex items-center justify-between mb-4">
            <div>
              <h1 className="text-slate-900">User & Access Management</h1>
              <p className="text-slate-500 text-xs mt-0.5">{users.length} users · RBAC with delegation and authority limits</p>
            </div>
            <button onClick={() => setShowInvite(true)} className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-3 py-2 text-sm" style={{ fontWeight: 500 }}>
              <Plus size={14} /> Invite User
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
              <Filter size={14} /> Filter <ChevronDown size={12} />
            </button>
          </div>
        </div>

        <div className="flex-1 overflow-auto">
          <table className="w-full">
            <thead className="sticky top-0 z-10 bg-slate-50 border-b border-slate-200">
              <tr>
                {['Name', 'Email', 'Role', 'Status', 'Authority Limit', 'Last Login', ''].map(h => (
                  <th key={h} className="text-left px-5 py-3 text-xs text-slate-500" style={{ fontWeight: 600, whiteSpace: 'nowrap' }}>{h}</th>
                ))}
              </tr>
            </thead>
            <tbody className="bg-white">
              {filtered.map(user => (
                <tr
                  key={user.id}
                  onClick={() => setSelectedUserId(user.id)}
                  className={`border-b border-slate-100 cursor-pointer transition-colors hover:bg-slate-50 ${selectedUserId === user.id ? 'bg-indigo-50/60' : ''}`}
                >
                  <td className="px-5 py-3">
                    <div className="flex items-center gap-3">
                      <div className="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                        <span className="text-indigo-700 text-xs" style={{ fontWeight: 700 }}>{user.initials}</span>
                      </div>
                      <span className="text-slate-800 text-sm" style={{ fontWeight: 500 }}>{user.name}</span>
                    </div>
                  </td>
                  <td className="px-5 py-3 text-slate-500 text-sm">{user.email}</td>
                  <td className="px-5 py-3">
                    <div className="flex items-center gap-1.5">
                      <Shield size={12} className="text-indigo-400" />
                      <span className="text-xs bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-full" style={{ fontWeight: 500 }}>{user.role}</span>
                    </div>
                  </td>
                  <td className="px-5 py-3"><StatusBadge status={user.status} /></td>
                  <td className="px-5 py-3">
                    {user.authorityLimit > 0 ? (
                      <span className="text-slate-700 text-sm" style={{ fontWeight: 500 }}>${user.authorityLimit.toLocaleString()}</span>
                    ) : (
                      <span className="text-slate-400 text-sm">—</span>
                    )}
                  </td>
                  <td className="px-5 py-3 text-slate-500 text-sm">{user.lastLogin ? formatDateTime(user.lastLogin) : 'Never'}</td>
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
            <button onClick={() => setSelectedUserId(null)} className="text-slate-400 hover:text-slate-600"><X size={16} /></button>
          </div>

          <div className="flex-1 overflow-y-auto p-5 space-y-5">
            <div className="text-center">
              <div className="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center mx-auto mb-3">
                <span className="text-indigo-700 text-xl" style={{ fontWeight: 700 }}>{selectedUser.initials}</span>
              </div>
              <div className="text-slate-900 text-sm" style={{ fontWeight: 600 }}>{selectedUser.name}</div>
              <div className="text-slate-500 text-xs mt-0.5">{selectedUser.email}</div>
              <div className="mt-2"><StatusBadge status={selectedUser.status} /></div>
            </div>

            <div className="bg-slate-50 rounded-xl border border-slate-200 p-4">
              <div className="flex items-center justify-between mb-3">
                <span className="text-slate-700 text-xs" style={{ fontWeight: 700 }}>ROLE</span>
              </div>
              <div className="flex items-center gap-2 mb-3">
                <Shield size={14} className="text-indigo-500" />
                <span className="text-slate-800 text-sm" style={{ fontWeight: 600 }}>{selectedUser.role}</span>
              </div>
              {selectedRole && (
                <div className="space-y-1.5">
                  <div className="text-slate-500 text-xs mb-2" style={{ fontWeight: 600 }}>PERMISSIONS</div>
                  {selectedRole.permissions.map(p => (
                    <div key={p} className="flex items-center gap-1.5 text-xs text-slate-600">
                      <Check size={11} className="text-emerald-500 flex-shrink-0" />
                      <span>{p.replace(/_/g, ' ')}</span>
                    </div>
                  ))}
                </div>
              )}
            </div>

            <div className="space-y-2">
              {[
                { label: 'Authority Limit', value: selectedUser.authorityLimit > 0 ? `$${selectedUser.authorityLimit.toLocaleString()}` : '—' },
                { label: 'Last Login', value: selectedUser.lastLogin ? formatDateTime(selectedUser.lastLogin) : 'Never' },
                { label: 'Role ID', value: selectedUser.roleId },
              ].map(f => (
                <div key={f.label} className="flex justify-between items-center py-1.5 border-b border-slate-100">
                  <span className="text-slate-500 text-xs" style={{ fontWeight: 500 }}>{f.label}</span>
                  <span className="text-slate-700 text-xs">{f.value}</span>
                </div>
              ))}
            </div>
          </div>

          <div className="border-t border-slate-200 p-4 space-y-2 flex-shrink-0">
            <button onClick={() => setShowDelegation(true)} className="w-full flex items-center justify-center gap-2 border border-slate-200 bg-white text-slate-600 rounded-lg px-4 py-2.5 text-sm hover:bg-slate-50" style={{ fontWeight: 500 }}>
              <UserCheck size={14} /> Delegation Rules
            </button>
            <button onClick={() => setShowAuthority(true)} className="w-full flex items-center justify-center gap-2 border border-slate-200 bg-white text-slate-600 rounded-lg px-4 py-2.5 text-sm hover:bg-slate-50" style={{ fontWeight: 500 }}>
              <DollarSign size={14} /> Set Authority Limits
            </button>
            {selectedUser.status === 'active' && (
              <button onClick={() => setShowSuspend(true)} className="w-full flex items-center justify-center gap-2 border border-red-200 bg-red-50 text-red-600 rounded-lg px-4 py-2.5 text-sm hover:bg-red-100">
                <Ban size={14} /> Suspend User
              </button>
            )}
          </div>
        </div>
      )}

      {/* SlideOver: Invite User */}
      <SlideOver
        open={showInvite}
        onOpenChange={setShowInvite}
        title="Invite User"
        description="Send an invitation to a new team member."
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowInvite(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button onClick={() => setShowInvite(false)} className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Send Invitation</button>
          </div>
        }
      >
        <div className="space-y-5">
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Email Address</label>
            <input type="email" placeholder="user@company.com" className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-400 outline-none" />
          </div>
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Full Name</label>
            <input type="text" placeholder="First Last" className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-400 outline-none" />
          </div>
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Role</label>
            <select className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white">
              {roles.map(r => (
                <option key={r.id} value={r.id}>{r.name}</option>
              ))}
            </select>
          </div>
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Authority Limit</label>
            <div className="relative">
              <span className="absolute left-3 top-2.5 text-slate-400 text-sm">$</span>
              <input type="number" placeholder="0" className="w-full border border-slate-200 rounded-lg pl-7 pr-3 py-2.5 text-sm text-slate-800 focus:border-indigo-400 outline-none" />
            </div>
            <p className="text-slate-400 text-xs mt-1">Set to 0 for no approval authority.</p>
          </div>
        </div>
      </SlideOver>

      {/* SlideOver: Suspend User */}
      <SlideOver
        open={showSuspend}
        onOpenChange={setShowSuspend}
        title="Suspend User"
        description={selectedUser ? `Suspend ${selectedUser.name}'s account access.` : ''}
        width="sm"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowSuspend(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button onClick={() => setShowSuspend(false)} className="bg-red-600 hover:bg-red-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Suspend User</button>
          </div>
        }
      >
        <div className="space-y-5">
          <div className="bg-red-50 border border-red-200 rounded-xl p-4 flex gap-3">
            <AlertTriangle size={18} className="text-red-500 flex-shrink-0 mt-0.5" />
            <div>
              <div className="text-red-700 text-sm" style={{ fontWeight: 600 }}>Account will be immediately locked</div>
              <p className="text-red-600 text-xs mt-1">The user will lose access to all system features. Any pending approvals assigned to them will need to be reassigned.</p>
            </div>
          </div>
          {selectedUser && (
            <div className="bg-slate-50 rounded-xl border border-slate-200 p-4 space-y-2">
              <div className="flex justify-between text-sm"><span className="text-slate-500">User</span><span className="text-slate-700" style={{ fontWeight: 500 }}>{selectedUser.name}</span></div>
              <div className="flex justify-between text-sm"><span className="text-slate-500">Role</span><span className="text-slate-700">{selectedUser.role}</span></div>
              <div className="flex justify-between text-sm"><span className="text-slate-500">Last Login</span><span className="text-slate-700">{selectedUser.lastLogin ? formatDateTime(selectedUser.lastLogin) : 'Never'}</span></div>
            </div>
          )}
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Suspension Reason <span className="text-red-500">*</span></label>
            <textarea rows={3} required placeholder="A reason is required for audit purposes..." className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm resize-none focus:border-red-400 outline-none" />
          </div>
        </div>
      </SlideOver>

      {/* SlideOver: Delegation Rules */}
      <SlideOver
        open={showDelegation}
        onOpenChange={setShowDelegation}
        title="Delegation Rules"
        description={selectedUser ? `Delegation configuration for ${selectedUser.name}.` : ''}
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowDelegation(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Close</button>
            <button onClick={() => setShowDelegation(false)} className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Save Changes</button>
          </div>
        }
      >
        <div className="space-y-5">
          {userDelegations.length > 0 ? (
            userDelegations.map(d => {
              const from = getUserById(d.fromUserId);
              const to = getUserById(d.toUserId);
              return (
                <div key={d.id} className="bg-slate-50 rounded-xl border border-slate-200 p-4 space-y-2">
                  <div className="flex items-center gap-2 mb-2">
                    <UserCheck size={14} className="text-indigo-500" />
                    <span className="text-slate-700 text-sm" style={{ fontWeight: 600 }}>
                      {from?.name} → {to?.name}
                    </span>
                  </div>
                  <div className="flex justify-between text-xs"><span className="text-slate-500">Scope</span><span className="text-slate-700">{d.scope}</span></div>
                  <div className="flex justify-between text-xs"><span className="text-slate-500">Max Amount</span><span className="text-slate-700">${d.maxAmount.toLocaleString()}</span></div>
                  <div className="flex justify-between text-xs"><span className="text-slate-500">Expires</span><span className="text-slate-700">{d.expiresAt ? formatDate(d.expiresAt) : 'No expiry'}</span></div>
                </div>
              );
            })
          ) : (
            <div className="text-center py-8">
              <Users size={24} className="mx-auto mb-2 text-slate-300" />
              <p className="text-slate-400 text-sm">No delegation rules configured for this user.</p>
            </div>
          )}

          <div className="border-t border-slate-200 pt-5">
            <span className="text-slate-700 text-xs" style={{ fontWeight: 700 }}>ADD NEW DELEGATION</span>
            <div className="mt-3 space-y-3">
              <div>
                <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Delegate To</label>
                <select className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white">
                  <option value="">Select user...</option>
                  {users.filter(u => u.id !== selectedUserId).map(u => (
                    <option key={u.id} value={u.id}>{u.name} ({u.role})</option>
                  ))}
                </select>
              </div>
              <div>
                <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Scope</label>
                <select className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white">
                  <option>All approval types</option>
                  <option>Comparison approvals only</option>
                  <option>Risk escalations only</option>
                  <option>Policy exceptions only</option>
                </select>
              </div>
              <div>
                <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Max Amount</label>
                <div className="relative">
                  <span className="absolute left-3 top-2.5 text-slate-400 text-sm">$</span>
                  <input type="number" placeholder="0" className="w-full border border-slate-200 rounded-lg pl-7 pr-3 py-2.5 text-sm text-slate-800 focus:border-indigo-400 outline-none" />
                </div>
              </div>
              <div>
                <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Expiry Date (optional)</label>
                <input type="date" className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-400 outline-none" />
              </div>
            </div>
          </div>
        </div>
      </SlideOver>

      {/* SlideOver: Set Authority Limits */}
      <SlideOver
        open={showAuthority}
        onOpenChange={setShowAuthority}
        title="Set Authority Limits"
        description={selectedUser ? `Configure approval thresholds for ${selectedUser.name}.` : ''}
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowAuthority(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button onClick={() => setShowAuthority(false)} className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Save Limits</button>
          </div>
        }
      >
        <div className="space-y-5">
          {selectedUser && (
            <div className="bg-indigo-50 border border-indigo-200 rounded-xl p-4 flex items-center gap-3">
              <div className="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                <span className="text-indigo-700 text-sm" style={{ fontWeight: 700 }}>{selectedUser.initials}</span>
              </div>
              <div>
                <div className="text-indigo-700 text-sm" style={{ fontWeight: 600 }}>{selectedUser.name}</div>
                <div className="text-indigo-500 text-xs">{selectedUser.role} · Current limit: ${selectedUser.authorityLimit.toLocaleString()}</div>
              </div>
            </div>
          )}

          <div>
            <label className="text-slate-600 text-xs block mb-2" style={{ fontWeight: 700 }}>APPROVAL TYPE THRESHOLDS</label>
            <div className="space-y-3">
              {[
                { type: 'Comparison Approval', desc: 'Final comparison sign-off' },
                { type: 'Risk Escalation', desc: 'Risk item escalation decisions' },
                { type: 'Policy Exception', desc: 'Policy waiver approvals' },
                { type: 'Override Approval', desc: 'Recommendation override sign-off' },
              ].map(item => (
                <div key={item.type} className="bg-slate-50 rounded-xl border border-slate-200 p-3">
                  <div className="text-slate-700 text-sm mb-0.5" style={{ fontWeight: 500 }}>{item.type}</div>
                  <div className="text-slate-400 text-xs mb-2">{item.desc}</div>
                  <div className="relative">
                    <span className="absolute left-3 top-2 text-slate-400 text-sm">$</span>
                    <input type="number" defaultValue={selectedUser?.authorityLimit ?? 0} className="w-full border border-slate-200 rounded-lg pl-7 pr-3 py-2 text-sm text-slate-800 focus:border-indigo-400 outline-none bg-white" />
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </SlideOver>
    </div>
  );
}

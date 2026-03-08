import type { Meta, StoryObj } from '@storybook/react-vite';
import { AtomyQButton } from '../components/basic/AtomyQButton';
import { AtomyQBadge } from '../components/basic/AtomyQBadge';
import { AtomyQAlert } from '../components/feedback/AtomyQAlert';
import { AtomyQInput } from '../components/form/AtomyQInput';
import { permissionKeys, users } from '@/data/mockData';
import { Lock, Eye } from 'lucide-react';

const meta: Meta = {
  title: 'Patterns/Permission & Roles',
  parameters: { layout: 'padded' },
};

export default meta;

export const PermissionUIRules: StoryObj = {
  render: () => (
    <div className="max-w-3xl space-y-8">
      <div>
        <h2 className="text-xl font-semibold text-[var(--aq-text-primary)] mb-1">Permission & Role UI Rules</h2>
        <p className="text-sm text-[var(--aq-text-muted)] mb-6">
          ERP systems require strict role-based UI behaviour. The UI must clearly communicate what actions a user can and cannot perform.
        </p>
      </div>

      {/* Permission Keys */}
      <div>
        <h3 className="text-sm font-semibold uppercase tracking-wider text-[var(--aq-text-muted)] mb-3">Permission Keys</h3>
        <div className="border border-[var(--aq-border-default)] rounded-lg overflow-hidden">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-[var(--aq-bg-elevated)]">
                <th className="px-4 py-2 text-left text-xs font-semibold uppercase text-[var(--aq-text-muted)]">Resource</th>
                <th className="px-4 py-2 text-left text-xs font-semibold uppercase text-[var(--aq-text-muted)]">Permissions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-[var(--aq-border-subtle)]">
              {Object.entries(permissionKeys).map(([resource, perms]) => (
                <tr key={resource}>
                  <td className="px-4 py-2 font-medium capitalize">{resource}</td>
                  <td className="px-4 py-2">
                    <div className="flex flex-wrap gap-1.5">
                      {perms.map((p) => (
                        <span key={p} className="font-mono text-xs px-2 py-0.5 bg-[var(--aq-bg-elevated)] rounded">{p}</span>
                      ))}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* User Roles */}
      <div>
        <h3 className="text-sm font-semibold uppercase tracking-wider text-[var(--aq-text-muted)] mb-3">Role → Permission Matrix</h3>
        <div className="border border-[var(--aq-border-default)] rounded-lg overflow-hidden">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-[var(--aq-bg-elevated)]">
                <th className="px-4 py-2 text-left text-xs font-semibold uppercase text-[var(--aq-text-muted)]">User</th>
                <th className="px-4 py-2 text-left text-xs font-semibold uppercase text-[var(--aq-text-muted)]">Role</th>
                <th className="px-4 py-2 text-left text-xs font-semibold uppercase text-[var(--aq-text-muted)]">Key Permissions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-[var(--aq-border-subtle)]">
              {users.map((user) => (
                <tr key={user.id}>
                  <td className="px-4 py-2 font-medium">{user.name}</td>
                  <td className="px-4 py-2"><AtomyQBadge variant="outline">{user.role}</AtomyQBadge></td>
                  <td className="px-4 py-2">
                    <div className="flex flex-wrap gap-1">
                      {user.permissions.slice(0, 4).map((p) => (
                        <span key={p} className="font-mono text-[10px] px-1.5 py-0.5 bg-[var(--aq-bg-elevated)] rounded">{p}</span>
                      ))}
                      {user.permissions.length > 4 && (
                        <span className="text-[10px] text-[var(--aq-text-subtle)]">+{user.permissions.length - 4} more</span>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* UI States */}
      <div>
        <h3 className="text-sm font-semibold uppercase tracking-wider text-[var(--aq-text-muted)] mb-3">UI Treatment by Permission State</h3>
        <div className="space-y-4">
          <div className="p-4 rounded-lg border border-[var(--aq-border-default)]">
            <h4 className="text-sm font-medium mb-3">Has Permission: <code className="text-xs bg-[var(--aq-bg-elevated)] px-1 rounded">rfq.create</code></h4>
            <AtomyQButton variant="primary">Create RFQ</AtomyQButton>
          </div>

          <div className="p-4 rounded-lg border border-[var(--aq-border-default)]">
            <h4 className="text-sm font-medium mb-3">Missing Permission — Disabled State</h4>
            <AtomyQButton variant="primary" disabled>Create RFQ</AtomyQButton>
            <p className="text-xs text-[var(--aq-text-muted)] mt-2 flex items-center gap-1">
              <Lock className="size-3" /> You need <code className="bg-[var(--aq-bg-elevated)] px-1 rounded">rfq.create</code> permission
            </p>
          </div>

          <div className="p-4 rounded-lg border border-[var(--aq-border-default)]">
            <h4 className="text-sm font-medium mb-3">Missing Permission — Hidden Action</h4>
            <p className="text-xs text-[var(--aq-text-muted)]">
              When the user lacks <code className="bg-[var(--aq-bg-elevated)] px-1 rounded">rfq.delete</code>, the Delete button is completely hidden from the UI.
            </p>
          </div>

          <div className="p-4 rounded-lg border border-[var(--aq-border-default)]">
            <h4 className="text-sm font-medium mb-3">Read-Only State (Viewer role)</h4>
            <div className="space-y-3 max-w-sm opacity-80">
              <AtomyQInput label="RFQ Title" value="Industrial Pumping Equipment" disabled />
              <div className="flex items-center gap-2 text-xs text-[var(--aq-text-muted)]">
                <Eye className="size-3" /> View-only mode
              </div>
            </div>
          </div>

          <AtomyQAlert variant="warning" title="Permission Warning">
            You are viewing this RFQ in read-only mode. Contact your administrator for edit access.
          </AtomyQAlert>
        </div>
      </div>
    </div>
  ),
};

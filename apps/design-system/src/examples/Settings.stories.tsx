import type { Meta, StoryObj } from '@storybook/react';
import { AtomyQInput } from '../components/form/AtomyQInput';
import { AtomyQSelect } from '../components/form/AtomyQSelect';
import { AtomyQSwitch } from '../components/form/AtomyQSwitch';
import { AtomyQButton } from '../components/basic/AtomyQButton';
import { AtomyQBadge } from '../components/basic/AtomyQBadge';
import { AtomyQTabs, AtomyQTabContent } from '../components/navigation/AtomyQTabs';
import { AtomyQSidebar } from '../components/navigation/AtomyQSidebar';
import { AtomyQBreadcrumb } from '../components/navigation/AtomyQBreadcrumb';
import { AtomyQAvatar } from '../components/basic/AtomyQAvatar';
import { AtomyQAlert } from '../components/feedback/AtomyQAlert';
import { navigationItems, users } from '@/data/mockData';
import { Bell, Save, User, Shield, Globe, Database, Mail } from 'lucide-react';

const meta: Meta = {
  title: 'Examples/Settings',
  parameters: { layout: 'fullscreen' },
};

export default meta;

export const SettingsPage: StoryObj = {
  render: () => (
    <div className="flex h-screen bg-[var(--aq-bg-canvas)]">
      <AtomyQSidebar sections={navigationItems} activeHref="/admin/settings" />

      <div className="flex-1 flex flex-col overflow-hidden">
        <header className="flex items-center justify-between h-14 px-6 bg-[var(--aq-header-bg)] border-b border-[var(--aq-header-border)]">
          <AtomyQBreadcrumb items={[{ label: 'Administration', href: '/admin' }, { label: 'Settings' }]} />
          <div className="flex items-center gap-3">
            <AtomyQButton variant="ghost" size="icon-sm"><Bell className="size-4" /></AtomyQButton>
            <AtomyQAvatar fallback="AP" size="sm" />
          </div>
        </header>

        <main className="flex-1 overflow-y-auto p-6">
          <div className="max-w-[1000px] mx-auto space-y-6">
            <div>
              <h1 className="text-xl font-semibold text-[var(--aq-text-primary)]">Settings</h1>
              <p className="text-sm text-[var(--aq-text-muted)]">Manage system preferences and organisation settings</p>
            </div>

            <AtomyQTabs
              items={[
                { value: 'general', label: 'General' },
                { value: 'notifications', label: 'Notifications' },
                { value: 'security', label: 'Security' },
                { value: 'integrations', label: 'Integrations' },
              ]}
              defaultValue="general"
              variant="underline"
            >
              <AtomyQTabContent value="general">
                <div className="space-y-6 mt-6">
                  {/* Organisation */}
                  <div className="bg-white rounded-lg border border-[var(--aq-border-default)] shadow-sm">
                    <div className="px-6 py-4 border-b border-[var(--aq-border-default)] flex items-center gap-2">
                      <Globe className="size-4 text-[var(--aq-text-muted)]" />
                      <h3 className="text-sm font-semibold text-[var(--aq-text-primary)]">Organisation</h3>
                    </div>
                    <div className="px-6 py-4 space-y-4">
                      <div className="grid grid-cols-2 gap-4">
                        <AtomyQInput label="Organisation Name" value="Acme Corporation Sdn. Bhd." />
                        <AtomyQInput label="Tenant ID" value="tenant_acme_prod" disabled hint="Cannot be changed" />
                      </div>
                      <div className="grid grid-cols-2 gap-4">
                        <AtomyQSelect
                          label="Default Currency"
                          options={[
                            { value: 'MYR', label: 'MYR — Malaysian Ringgit' },
                            { value: 'USD', label: 'USD — US Dollar' },
                            { value: 'EUR', label: 'EUR — Euro' },
                            { value: 'SGD', label: 'SGD — Singapore Dollar' },
                          ]}
                          value="MYR"
                        />
                        <AtomyQSelect
                          label="Date Format"
                          options={[
                            { value: 'iso', label: 'YYYY-MM-DD (ISO)' },
                            { value: 'short', label: 'DD MMM YYYY' },
                            { value: 'us', label: 'MM/DD/YYYY' },
                          ]}
                          value="iso"
                        />
                      </div>
                      <AtomyQSelect
                        label="Timezone"
                        options={[
                          { value: 'asia-kl', label: 'Asia/Kuala_Lumpur (UTC+8)' },
                          { value: 'asia-sg', label: 'Asia/Singapore (UTC+8)' },
                          { value: 'utc', label: 'UTC' },
                        ]}
                        value="asia-kl"
                      />
                    </div>
                  </div>

                  {/* Procurement Defaults */}
                  <div className="bg-white rounded-lg border border-[var(--aq-border-default)] shadow-sm">
                    <div className="px-6 py-4 border-b border-[var(--aq-border-default)] flex items-center gap-2">
                      <Database className="size-4 text-[var(--aq-text-muted)]" />
                      <h3 className="text-sm font-semibold text-[var(--aq-text-primary)]">Procurement Defaults</h3>
                    </div>
                    <div className="px-6 py-4 space-y-4">
                      <AtomyQSwitch label="Auto-run AI comparison" description="Automatically trigger comparison when all vendors have submitted quotes" />
                      <AtomyQSwitch label="Auto-invite pre-qualified vendors" description="New RFQs automatically invite vendors matching the category" />
                      <AtomyQSwitch label="Require approval for awards" description="All vendor awards must go through the approval workflow" />
                      <AtomyQSelect
                        label="Default Approval Levels"
                        options={[
                          { value: '1', label: '1 level — Manager only' },
                          { value: '2', label: '2 levels — Manager + Director' },
                          { value: '3', label: '3 levels — Manager + Director + VP' },
                        ]}
                        value="2"
                        hint="Number of approval levels required for vendor awards"
                      />
                    </div>
                  </div>

                  {/* Notifications */}
                  <div className="bg-white rounded-lg border border-[var(--aq-border-default)] shadow-sm">
                    <div className="px-6 py-4 border-b border-[var(--aq-border-default)] flex items-center gap-2">
                      <Mail className="size-4 text-[var(--aq-text-muted)]" />
                      <h3 className="text-sm font-semibold text-[var(--aq-text-primary)]">Email Preferences</h3>
                    </div>
                    <div className="px-6 py-4 space-y-4">
                      <AtomyQSwitch label="Quote received notifications" description="Get notified when a vendor submits a quote" />
                      <AtomyQSwitch label="Approval request notifications" description="Get notified when an approval is required" />
                      <AtomyQSwitch label="Deadline reminders" description="Receive reminders 3 days before RFQ deadlines" />
                      <AtomyQSwitch label="Weekly digest" description="Weekly summary of procurement activity" />
                    </div>
                  </div>

                  <AtomyQAlert variant="info">
                    Settings changes are saved automatically. Some changes may require a page refresh to take effect.
                  </AtomyQAlert>

                  <div className="flex justify-end gap-2">
                    <AtomyQButton variant="outline">Reset to Defaults</AtomyQButton>
                    <AtomyQButton variant="primary"><Save className="size-4" /> Save Changes</AtomyQButton>
                  </div>
                </div>
              </AtomyQTabContent>
            </AtomyQTabs>
          </div>
        </main>
      </div>
    </div>
  ),
};

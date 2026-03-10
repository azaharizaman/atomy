import { useState } from 'react';
import {
  Plus, Settings, Trash2, Power, PowerOff, RefreshCw, Zap,
  AlertTriangle, CheckCircle2, XCircle, Clock, Activity,
  Server, Cloud, Mail, Shield, Key, HardDrive, ChevronDown,
  ExternalLink, Loader2, X,
} from 'lucide-react';
import { SlideOver } from '../components/SlideOver';
import {
  integrations, connectorCatalog, integrationJobs, statusColors, formatDateTime,
} from '../data/mockData';

const healthDot: Record<string, string> = {
  healthy: 'bg-emerald-500',
  degraded: 'bg-amber-500',
  outage: 'bg-red-500',
  disabled: 'bg-slate-400',
};

const healthLabel: Record<string, string> = {
  healthy: 'Healthy',
  degraded: 'Degraded',
  outage: 'Outage',
  disabled: 'Disabled',
};

const catalogIcons: Record<string, React.ReactNode> = {
  server: <Server size={20} className="text-indigo-500" />,
  cloud: <Cloud size={20} className="text-sky-500" />,
  mail: <Mail size={20} className="text-violet-500" />,
  shield: <Shield size={20} className="text-rose-500" />,
  key: <Key size={20} className="text-amber-500" />,
  'hard-drive': <HardDrive size={20} className="text-emerald-500" />,
};

type TestResult = { success: boolean; time?: number; error?: string };

export function IntegrationsConfig() {
  const [showConfigModal, setShowConfigModal] = useState(false);
  const [showDisableModal, setShowDisableModal] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [activeIntegrationId, setActiveIntegrationId] = useState<string | null>(null);
  const [testResults, setTestResults] = useState<Record<string, TestResult>>({});
  const [testingId, setTestingId] = useState<string | null>(null);
  const [configCatalogId, setConfigCatalogId] = useState('');
  const [configEndpoint, setConfigEndpoint] = useState('');
  const [configApiKey, setConfigApiKey] = useState('');
  const [configFrequency, setConfigFrequency] = useState('15 min');

  const configuredCatalogIds = integrations.map(i => i.catalogId);
  const availableConnectors = connectorCatalog.filter(c => !configuredCatalogIds.includes(c.id));

  const activeIntegration = integrations.find(i => i.id === activeIntegrationId) ?? null;

  const handleTestConnection = (integrationId: string) => {
    setTestingId(integrationId);
    setTimeout(() => {
      const integration = integrations.find(i => i.id === integrationId);
      if (integration?.health === 'degraded' || integration?.health === 'outage') {
        setTestResults(prev => ({ ...prev, [integrationId]: { success: false, error: 'Connection timeout after 30s' } }));
      } else {
        const time = Math.floor(Math.random() * 200) + 50;
        setTestResults(prev => ({ ...prev, [integrationId]: { success: true, time } }));
      }
      setTestingId(null);
    }, 1500);
  };

  const openDisable = (id: string) => {
    setActiveIntegrationId(id);
    setShowDisableModal(true);
  };

  const openDelete = (id: string) => {
    setActiveIntegrationId(id);
    setShowDeleteModal(true);
  };

  const openConfig = (catalogId?: string) => {
    setConfigCatalogId(catalogId ?? '');
    setConfigEndpoint('');
    setConfigApiKey('');
    setConfigFrequency('15 min');
    setShowConfigModal(true);
  };

  const openEditConfig = (id: string) => {
    const integration = integrations.find(i => i.id === id);
    if (!integration) return;
    setActiveIntegrationId(id);
    setConfigCatalogId(integration.catalogId);
    setConfigEndpoint(integration.endpoint);
    setConfigApiKey('••••••••••••••••');
    setConfigFrequency(integration.syncFrequency);
    setShowConfigModal(true);
  };

  const getCatalog = (catalogId: string) => connectorCatalog.find(c => c.id === catalogId);

  const recentJobs = (integrationId: string) =>
    integrationJobs.filter(j => j.integrationId === integrationId).slice(0, 3);

  return (
    <div className="min-h-screen">
      {/* Header */}
      <div className="flex items-center justify-between mb-5">
        <div>
          <h1 className="text-xl text-slate-900" style={{ fontWeight: 700 }}>Integrations</h1>
          <p className="text-sm text-slate-500 mt-0.5">Configure and monitor connections to external systems</p>
        </div>
        <button
          onClick={() => openConfig()}
          className="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700 transition-colors"
          style={{ fontWeight: 500 }}
        >
          <Plus size={16} />
          Add Integration
        </button>
      </div>

      {/* ── Configured Integrations Grid ── */}
      <div className="mb-8">
        <h2 className="text-sm text-slate-900 mb-3" style={{ fontWeight: 600 }}>
          Active Connections ({integrations.length})
        </h2>
        <div className="grid grid-cols-3 gap-4">
          {integrations.map(integration => {
            const catalog = getCatalog(integration.catalogId);
            const jobs = recentJobs(integration.id);
            const result = testResults[integration.id];
            const isTesting = testingId === integration.id;

            return (
              <div key={integration.id} className="bg-white border border-slate-200 rounded-xl p-5 hover:shadow-sm transition-shadow">
                {/* Card Header */}
                <div className="flex items-start justify-between mb-3">
                  <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-center">
                      {catalog ? catalogIcons[catalog.icon] ?? <Server size={20} className="text-slate-400" /> : <Server size={20} className="text-slate-400" />}
                    </div>
                    <div>
                      <p className="text-sm text-slate-900" style={{ fontWeight: 600 }}>{integration.name}</p>
                      <span className="text-[11px] text-slate-500">
                        {catalog?.type ?? 'Unknown'}
                      </span>
                    </div>
                  </div>
                </div>

                {/* Health + Sync */}
                <div className="flex items-center gap-4 mb-3">
                  <div className="flex items-center gap-1.5">
                    <span className={`w-2 h-2 rounded-full ${healthDot[integration.health]}`} />
                    <span className={`text-xs ${
                      integration.health === 'healthy' ? 'text-emerald-700' :
                      integration.health === 'degraded' ? 'text-amber-700' :
                      integration.health === 'outage' ? 'text-red-700' : 'text-slate-500'
                    }`} style={{ fontWeight: 500 }}>
                      {healthLabel[integration.health]}
                    </span>
                  </div>
                  <span className={`text-[11px] px-2 py-0.5 rounded-full ${statusColors[integration.health]}`} style={{ fontWeight: 500 }}>
                    {integration.enabled ? 'Enabled' : 'Disabled'}
                  </span>
                </div>

                <div className="space-y-1.5 mb-4">
                  <div className="flex items-center gap-2 text-xs text-slate-500">
                    <Clock size={12} />
                    <span>Last sync: {formatDateTime(integration.lastSync)}</span>
                  </div>
                  <div className="flex items-center gap-2 text-xs text-slate-500">
                    <RefreshCw size={12} />
                    <span>Frequency: {integration.syncFrequency}</span>
                  </div>
                  {integration.failureCount > 0 && (
                    <div className="flex items-center gap-2 text-xs text-red-600">
                      <AlertTriangle size={12} />
                      <span>{integration.failureCount} recent failure{integration.failureCount > 1 ? 's' : ''}</span>
                    </div>
                  )}
                </div>

                {/* Test result inline */}
                {(result || isTesting) && (
                  <div className={`mb-3 px-3 py-2 rounded-lg text-xs ${
                    isTesting ? 'bg-slate-50 text-slate-500' :
                    result?.success ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' :
                    'bg-red-50 text-red-700 border border-red-200'
                  }`}>
                    {isTesting ? (
                      <span className="flex items-center gap-2">
                        <Loader2 size={12} className="animate-spin" /> Testing connection…
                      </span>
                    ) : result?.success ? (
                      <span className="flex items-center gap-2">
                        <CheckCircle2 size={12} /> Connected — {result.time}ms response
                      </span>
                    ) : (
                      <span className="flex items-center gap-2">
                        <XCircle size={12} /> {result?.error}
                      </span>
                    )}
                  </div>
                )}

                {/* Recent Jobs */}
                {jobs.length > 0 && (
                  <div className="mb-4 space-y-1">
                    {jobs.map(job => (
                      <div key={job.id} className="flex items-center justify-between text-[11px]">
                        <span className="text-slate-500">{formatDateTime(job.startedAt)}</span>
                        <span className={job.status === 'completed' ? 'text-emerald-600' : 'text-red-600'} style={{ fontWeight: 500 }}>
                          {job.status}
                        </span>
                      </div>
                    ))}
                  </div>
                )}

                {/* Actions */}
                <div className="flex items-center gap-2 pt-3 border-t border-slate-100">
                  <button
                    onClick={() => handleTestConnection(integration.id)}
                    disabled={isTesting}
                    className="flex items-center gap-1.5 px-2.5 py-1.5 text-xs border border-slate-200 rounded-lg text-slate-700 hover:bg-slate-50 transition-colors disabled:opacity-50"
                    style={{ fontWeight: 500 }}
                  >
                    <Zap size={12} /> Test
                  </button>
                  <button
                    onClick={() => openEditConfig(integration.id)}
                    className="flex items-center gap-1.5 px-2.5 py-1.5 text-xs border border-slate-200 rounded-lg text-slate-700 hover:bg-slate-50 transition-colors"
                    style={{ fontWeight: 500 }}
                  >
                    <Settings size={12} /> Edit
                  </button>
                  {integration.enabled ? (
                    <button
                      onClick={() => openDisable(integration.id)}
                      className="flex items-center gap-1.5 px-2.5 py-1.5 text-xs border border-slate-200 rounded-lg text-amber-700 hover:bg-amber-50 transition-colors"
                      style={{ fontWeight: 500 }}
                    >
                      <PowerOff size={12} /> Disable
                    </button>
                  ) : (
                    <button
                      className="flex items-center gap-1.5 px-2.5 py-1.5 text-xs border border-slate-200 rounded-lg text-emerald-700 hover:bg-emerald-50 transition-colors"
                      style={{ fontWeight: 500 }}
                    >
                      <Power size={12} /> Enable
                    </button>
                  )}
                  <button
                    onClick={() => openDelete(integration.id)}
                    className="ml-auto flex items-center gap-1.5 px-2.5 py-1.5 text-xs border border-slate-200 rounded-lg text-red-600 hover:bg-red-50 transition-colors"
                    style={{ fontWeight: 500 }}
                  >
                    <Trash2 size={12} />
                  </button>
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* ── Connector Catalog ── */}
      {availableConnectors.length > 0 && (
        <div>
          <h2 className="text-sm text-slate-900 mb-3" style={{ fontWeight: 600 }}>
            Available Connectors
          </h2>
          <div className="grid grid-cols-4 gap-3">
            {availableConnectors.map(connector => (
              <div key={connector.id} className="bg-white border border-dashed border-slate-300 rounded-xl p-4 hover:border-indigo-300 hover:shadow-sm transition-all">
                <div className="flex items-center gap-3 mb-2">
                  <div className="w-9 h-9 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-center">
                    {catalogIcons[connector.icon] ?? <Server size={18} className="text-slate-400" />}
                  </div>
                  <div>
                    <p className="text-sm text-slate-900" style={{ fontWeight: 600 }}>{connector.name}</p>
                    <span className="text-[11px] text-slate-500">{connector.type}</span>
                  </div>
                </div>
                <p className="text-xs text-slate-500 mb-3 line-clamp-2 leading-relaxed">{connector.description}</p>
                <button
                  onClick={() => openConfig(connector.id)}
                  className="w-full flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs border border-indigo-200 rounded-lg text-indigo-600 hover:bg-indigo-50 transition-colors"
                  style={{ fontWeight: 500 }}
                >
                  <Plus size={13} /> Add
                </button>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* ── Configure Integration SlideOver ── */}
      <SlideOver
        open={showConfigModal}
        onOpenChange={setShowConfigModal}
        title={activeIntegrationId ? 'Edit Integration' : 'Configure Integration'}
        description="Set up connection parameters and sync settings"
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button
              onClick={() => { setShowConfigModal(false); setActiveIntegrationId(null); }}
              className="px-4 py-2 text-sm border border-slate-200 rounded-lg text-slate-700 hover:bg-slate-50"
              style={{ fontWeight: 500 }}
            >
              Cancel
            </button>
            <button
              onClick={() => { setShowConfigModal(false); setActiveIntegrationId(null); }}
              className="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
              style={{ fontWeight: 500 }}
            >
              Save Integration
            </button>
          </div>
        }
      >
        <div className="space-y-5">
          {/* Connector Type */}
          <div>
            <label className="block text-xs text-slate-500 mb-1.5" style={{ fontWeight: 500 }}>Connector Type</label>
            <div className="relative">
              <select
                value={configCatalogId}
                onChange={e => setConfigCatalogId(e.target.value)}
                disabled={!!activeIntegrationId}
                className="w-full appearance-none text-sm border border-slate-200 rounded-lg px-3 py-2.5 bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 disabled:bg-slate-50 disabled:text-slate-500"
              >
                <option value="">Select a connector…</option>
                {connectorCatalog.map(c => (
                  <option key={c.id} value={c.id}>{c.name} ({c.type})</option>
                ))}
              </select>
              <ChevronDown size={14} className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
            </div>
          </div>

          {/* Endpoint URL */}
          <div>
            <label className="block text-xs text-slate-500 mb-1.5" style={{ fontWeight: 500 }}>Endpoint URL</label>
            <div className="relative">
              <ExternalLink size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
              <input
                type="url"
                value={configEndpoint}
                onChange={e => setConfigEndpoint(e.target.value)}
                placeholder="https://api.example.com/v1"
                className="w-full pl-9 pr-3 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400"
              />
            </div>
          </div>

          {/* API Key / Auth */}
          <div>
            <label className="block text-xs text-slate-500 mb-1.5" style={{ fontWeight: 500 }}>API Key / Authentication</label>
            <div className="relative">
              <Key size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
              <input
                type="password"
                value={configApiKey}
                onChange={e => setConfigApiKey(e.target.value)}
                placeholder="Enter API key or token"
                className="w-full pl-9 pr-3 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400"
              />
            </div>
          </div>

          {/* Field Mapping */}
          <div>
            <label className="block text-xs text-slate-500 mb-1.5" style={{ fontWeight: 500 }}>Field Mapping</label>
            <div className="border border-slate-200 rounded-lg overflow-hidden">
              <table className="w-full text-sm">
                <thead>
                  <tr className="bg-slate-50/70 border-b border-slate-200">
                    <th className="px-4 py-2 text-left text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>Atomy Field</th>
                    <th className="px-4 py-2 text-center text-xs text-slate-400">→</th>
                    <th className="px-4 py-2 text-left text-xs text-slate-500 uppercase tracking-wider" style={{ fontWeight: 600 }}>External Field</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {[
                    { local: 'Vendor Code', remote: 'vendor_id' },
                    { local: 'PO Number', remote: 'purchase_order_number' },
                    { local: 'Total Value', remote: 'total_amount' },
                    { local: 'Currency', remote: 'currency_code' },
                    { local: 'Line Items', remote: 'order_lines[]' },
                  ].map((mapping, idx) => (
                    <tr key={idx}>
                      <td className="px-4 py-2 text-slate-700">{mapping.local}</td>
                      <td className="px-4 py-2 text-center text-slate-300">→</td>
                      <td className="px-4 py-2">
                        <input
                          type="text"
                          defaultValue={mapping.remote}
                          className="w-full text-sm border border-slate-200 rounded px-2 py-1 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 text-slate-600 font-mono text-xs"
                        />
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>

          {/* Sync Frequency */}
          <div>
            <label className="block text-xs text-slate-500 mb-1.5" style={{ fontWeight: 500 }}>Sync Frequency</label>
            <div className="relative">
              <select
                value={configFrequency}
                onChange={e => setConfigFrequency(e.target.value)}
                className="w-full appearance-none text-sm border border-slate-200 rounded-lg px-3 py-2.5 bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
              >
                <option value="real-time">Real-time</option>
                <option value="5 min">Every 5 minutes</option>
                <option value="15 min">Every 15 minutes</option>
                <option value="30 min">Every 30 minutes</option>
                <option value="1 hour">Hourly</option>
                <option value="on-demand">On-demand only</option>
              </select>
              <ChevronDown size={14} className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
            </div>
          </div>
        </div>
      </SlideOver>

      {/* ── Confirm Disable SlideOver ── */}
      <SlideOver
        open={showDisableModal}
        onOpenChange={open => { setShowDisableModal(open); if (!open) setActiveIntegrationId(null); }}
        title="Confirm Disable"
        description={`Disable ${activeIntegration?.name ?? 'integration'}?`}
        width="sm"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button
              onClick={() => { setShowDisableModal(false); setActiveIntegrationId(null); }}
              className="px-4 py-2 text-sm border border-slate-200 rounded-lg text-slate-700 hover:bg-slate-50"
              style={{ fontWeight: 500 }}
            >
              Cancel
            </button>
            <button
              onClick={() => { setShowDisableModal(false); setActiveIntegrationId(null); }}
              className="px-4 py-2 text-sm bg-amber-600 text-white rounded-lg hover:bg-amber-700"
              style={{ fontWeight: 500 }}
            >
              Disable Integration
            </button>
          </div>
        }
      >
        <div className="space-y-4">
          <div className="bg-amber-50 border border-amber-200 rounded-lg p-4">
            <div className="flex gap-3">
              <AlertTriangle size={18} className="text-amber-600 flex-shrink-0 mt-0.5" />
              <div>
                <p className="text-sm text-amber-800" style={{ fontWeight: 500 }}>
                  Disabling this integration will pause all data synchronization.
                </p>
                <p className="text-xs text-amber-700 mt-1">
                  No new data will flow between Atomy and this system. Pending handoffs may fail. You can re-enable the integration at any time.
                </p>
              </div>
            </div>
          </div>
          {activeIntegration && (
            <div className="border border-slate-200 rounded-lg p-4 space-y-2">
              <div className="flex items-center justify-between">
                <span className="text-sm text-slate-900" style={{ fontWeight: 600 }}>{activeIntegration.name}</span>
                <div className="flex items-center gap-1.5">
                  <span className={`w-2 h-2 rounded-full ${healthDot[activeIntegration.health]}`} />
                  <span className="text-xs text-slate-600">{healthLabel[activeIntegration.health]}</span>
                </div>
              </div>
              <p className="text-xs text-slate-500">{activeIntegration.endpoint}</p>
              <div className="text-xs text-slate-500">
                Last sync: {formatDateTime(activeIntegration.lastSync)}
              </div>
            </div>
          )}
        </div>
      </SlideOver>

      {/* ── Confirm Delete SlideOver ── */}
      <SlideOver
        open={showDeleteModal}
        onOpenChange={open => { setShowDeleteModal(open); if (!open) setActiveIntegrationId(null); }}
        title="Confirm Delete"
        description={`Permanently remove ${activeIntegration?.name ?? 'integration'}?`}
        width="sm"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button
              onClick={() => { setShowDeleteModal(false); setActiveIntegrationId(null); }}
              className="px-4 py-2 text-sm border border-slate-200 rounded-lg text-slate-700 hover:bg-slate-50"
              style={{ fontWeight: 500 }}
            >
              Cancel
            </button>
            <button
              onClick={() => { setShowDeleteModal(false); setActiveIntegrationId(null); }}
              className="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700"
              style={{ fontWeight: 500 }}
            >
              Delete Integration
            </button>
          </div>
        }
      >
        <div className="space-y-4">
          <div className="bg-red-50 border border-red-200 rounded-lg p-4">
            <div className="flex gap-3">
              <Trash2 size={18} className="text-red-600 flex-shrink-0 mt-0.5" />
              <div>
                <p className="text-sm text-red-800" style={{ fontWeight: 500 }}>
                  This action is permanent and cannot be undone.
                </p>
                <p className="text-xs text-red-700 mt-1">
                  Deleting this integration will remove all configuration, credentials, and field mappings. Historical sync logs will be retained for audit purposes.
                </p>
              </div>
            </div>
          </div>
          {activeIntegration && (
            <div className="border border-slate-200 rounded-lg p-4 space-y-2">
              <div className="flex items-center justify-between">
                <span className="text-sm text-slate-900" style={{ fontWeight: 600 }}>{activeIntegration.name}</span>
                <span className={`text-[11px] px-2 py-0.5 rounded-full ${statusColors[activeIntegration.health]}`} style={{ fontWeight: 500 }}>
                  {activeIntegration.health}
                </span>
              </div>
              <p className="text-xs text-slate-500">{activeIntegration.endpoint}</p>
            </div>
          )}
        </div>
      </SlideOver>
    </div>
  );
}

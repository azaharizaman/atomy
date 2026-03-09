import { useState } from 'react';
import {
  ChevronRight, Activity, Server, Cloud, Mail, Shield, Key, HardDrive,
  CheckCircle, AlertTriangle, XCircle, Clock, RefreshCw, RotateCcw,
  Search, Filter, Wifi, WifiOff, Zap, ArrowLeft, ChevronDown
} from 'lucide-react';
import { Link } from 'react-router';
import { SlideOver } from '../components/SlideOver';
import {
  integrations, integrationJobs, connectorCatalog, statusColors,
  formatDateTime
} from '../data/mockData';

type HealthFilter = 'all' | 'degraded' | 'failed';

const catalogIcons: Record<string, typeof Server> = {
  server: Server,
  cloud: Cloud,
  mail: Mail,
  shield: Shield,
  key: Key,
  'hard-drive': HardDrive,
};

const healthDotColor: Record<string, string> = {
  healthy: 'bg-emerald-500',
  degraded: 'bg-amber-500',
  outage: 'bg-red-500',
  disabled: 'bg-slate-400',
};

const healthBgColor: Record<string, string> = {
  healthy: 'bg-emerald-50 border-emerald-200',
  degraded: 'bg-amber-50 border-amber-200',
  outage: 'bg-red-50 border-red-200',
  disabled: 'bg-slate-50 border-slate-200',
};

const healthTextColor: Record<string, string> = {
  healthy: 'text-emerald-700',
  degraded: 'text-amber-700',
  outage: 'text-red-700',
  disabled: 'text-slate-500',
};

function getSystemHealth(): { label: string; color: string; icon: typeof CheckCircle } {
  const hasOutage = integrations.some(i => i.health === 'outage');
  const hasDegraded = integrations.some(i => i.health === 'degraded');
  if (hasOutage) return { label: 'Outage Detected', color: 'text-red-700', icon: XCircle };
  if (hasDegraded) return { label: 'Some Degraded', color: 'text-amber-700', icon: AlertTriangle };
  return { label: 'All Healthy', color: 'text-emerald-700', icon: CheckCircle };
}

export function IntegrationMonitor() {
  const [healthFilter, setHealthFilter] = useState<HealthFilter>('all');
  const [showRetryModal, setShowRetryModal] = useState(false);
  const [activeJobId, setActiveJobId] = useState<string | null>(null);
  const [searchQuery, setSearchQuery] = useState('');

  const systemHealth = getSystemHealth();
  const SystemIcon = systemHealth.icon;

  const failedJobs = integrationJobs.filter(j => j.status === 'failed');
  const retryableJobs = failedJobs.filter(j => j.retryCount < j.maxRetries);
  const activeJob = integrationJobs.find(j => j.id === activeJobId);
  const activeJobIntegration = activeJob ? integrations.find(i => i.id === activeJob.integrationId) : null;

  const filteredIntegrations = integrations.filter(i => {
    if (healthFilter === 'degraded' && i.health !== 'degraded') return false;
    if (healthFilter === 'failed' && i.health !== 'outage') return false;
    if (searchQuery) {
      const q = searchQuery.toLowerCase();
      return i.name.toLowerCase().includes(q) || i.endpoint.toLowerCase().includes(q);
    }
    return true;
  });

  const filteredJobs = integrationJobs.filter(j => {
    if (healthFilter === 'failed' && j.status !== 'failed') return false;
    if (searchQuery) {
      const integration = integrations.find(i => i.id === j.integrationId);
      const q = searchQuery.toLowerCase();
      return j.id.toLowerCase().includes(q) || integration?.name.toLowerCase().includes(q) || false;
    }
    return true;
  });

  const healthyCount = integrations.filter(i => i.health === 'healthy').length;
  const degradedCount = integrations.filter(i => i.health === 'degraded').length;
  const outageCount = integrations.filter(i => i.health === 'outage').length;

  return (
    <div className="flex flex-col h-full bg-slate-50">
      {/* Header */}
      <div className="bg-white border-b border-slate-200 px-6 py-4 flex-shrink-0">
        <div className="flex items-center gap-1.5 text-xs text-slate-500 mb-3">
          <Link to="/" className="hover:text-slate-700">Home</Link>
          <ChevronRight size={11} />
          <span className="text-slate-700" style={{ fontWeight: 500 }}>Integration Monitor</span>
        </div>

        <div className="flex items-start justify-between">
          <div>
            <h1 className="text-slate-900 text-lg" style={{ fontWeight: 700 }}>Integration Monitor</h1>
            <p className="text-xs text-slate-500 mt-1">Monitor connector health, API jobs, and retry failed operations</p>
          </div>
          <div className="flex items-center gap-3">
            <div className={`flex items-center gap-2 px-3 py-2 rounded-xl border ${
              systemHealth.label === 'Outage Detected' ? 'bg-red-50 border-red-200' :
              systemHealth.label === 'Some Degraded' ? 'bg-amber-50 border-amber-200' :
              'bg-emerald-50 border-emerald-200'
            }`}>
              <SystemIcon size={14} className={systemHealth.color} />
              <span className={`text-sm ${systemHealth.color}`} style={{ fontWeight: 700 }}>{systemHealth.label}</span>
            </div>
            <button className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50" style={{ fontWeight: 500 }}>
              <RefreshCw size={14} />
              Refresh All
            </button>
          </div>
        </div>

        {/* Summary Stats */}
        <div className="grid grid-cols-4 gap-3 mt-4">
          {[
            { label: 'Total Integrations', value: integrations.length, color: 'text-slate-800', icon: Zap },
            { label: 'Healthy', value: healthyCount, color: 'text-emerald-700', icon: CheckCircle },
            { label: 'Degraded', value: degradedCount, color: 'text-amber-700', icon: AlertTriangle },
            { label: 'Failed Jobs', value: failedJobs.length, color: 'text-red-700', icon: XCircle },
          ].map(stat => (
            <div key={stat.label} className="bg-slate-50 rounded-lg px-4 py-3 border border-slate-100">
              <div className="flex items-center gap-1.5 text-xs text-slate-500 mb-1">
                <stat.icon size={11} />
                {stat.label}
              </div>
              <div className={`text-xl ${stat.color}`} style={{ fontWeight: 700 }}>{stat.value}</div>
            </div>
          ))}
        </div>
      </div>

      {/* Filter Bar */}
      <div className="bg-white border-b border-slate-200 px-6 py-3 flex items-center gap-3 flex-shrink-0">
        <div className="relative flex-1 max-w-xs">
          <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input
            type="text"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            placeholder="Search integrations or jobs..."
            className="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-lg text-sm text-slate-800 focus:border-indigo-400 outline-none"
          />
        </div>
        <div className="flex items-center gap-1.5">
          <Filter size={13} className="text-slate-400" />
          {(['all', 'degraded', 'failed'] as HealthFilter[]).map(f => (
            <button
              key={f}
              onClick={() => setHealthFilter(f)}
              className={`px-3 py-1.5 rounded-lg text-xs transition-colors ${
                healthFilter === f ? 'bg-indigo-100 text-indigo-700' : 'text-slate-500 hover:bg-slate-100'
              }`}
              style={{ fontWeight: healthFilter === f ? 600 : 400 }}
            >
              {f === 'all' ? 'All' : f.charAt(0).toUpperCase() + f.slice(1)}
            </button>
          ))}
        </div>
      </div>

      {/* Main Content */}
      <div className="flex-1 overflow-auto p-5 space-y-6">
        {/* Health Cards Row */}
        <div>
          <div className="flex items-center gap-2 mb-3">
            <Activity size={14} className="text-slate-500" />
            <span className="text-sm text-slate-700" style={{ fontWeight: 600 }}>Connector Health</span>
            <span className="text-xs text-slate-400 ml-1">{filteredIntegrations.length} integration{filteredIntegrations.length !== 1 ? 's' : ''}</span>
          </div>
          <div className="grid grid-cols-3 gap-3">
            {filteredIntegrations.map(integration => {
              const catalog = connectorCatalog.find(c => c.id === integration.catalogId);
              const IconComp = catalogIcons[catalog?.icon || 'server'] || Server;
              return (
                <div key={integration.id} className={`rounded-xl border p-4 ${healthBgColor[integration.health]} transition-colors`}>
                  <div className="flex items-start gap-3">
                    <div className="w-9 h-9 rounded-lg bg-white/80 flex items-center justify-center flex-shrink-0 border border-slate-200/50">
                      <IconComp size={16} className="text-slate-600" />
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-2 mb-1">
                        <span className="text-sm text-slate-800 truncate" style={{ fontWeight: 600 }}>{integration.name}</span>
                      </div>
                      <div className="flex items-center gap-1.5 mb-2">
                        <span className={`w-2 h-2 rounded-full ${healthDotColor[integration.health]}`} />
                        <span className={`text-xs capitalize ${healthTextColor[integration.health]}`} style={{ fontWeight: 600 }}>
                          {integration.health}
                        </span>
                        <span className={`ml-1 text-xs px-1.5 py-0.5 rounded ${statusColors[integration.health] || ''}`} style={{ fontWeight: 500 }}>
                          {catalog?.type}
                        </span>
                      </div>
                      <div className="space-y-1 text-xs text-slate-500">
                        <div className="flex items-center gap-1.5">
                          <Clock size={10} />
                          <span>Last sync: {formatDateTime(integration.lastSync)}</span>
                        </div>
                        <div className="flex items-center gap-1.5">
                          <RefreshCw size={10} />
                          <span>Frequency: {integration.syncFrequency}</span>
                        </div>
                        {integration.failureCount > 0 && (
                          <div className="flex items-center gap-1.5 text-red-600">
                            <XCircle size={10} />
                            <span style={{ fontWeight: 500 }}>{integration.failureCount} failure{integration.failureCount !== 1 ? 's' : ''}</span>
                          </div>
                        )}
                      </div>
                    </div>
                    <div className={`w-3 h-3 rounded-full flex-shrink-0 mt-1 ${
                      integration.enabled ? healthDotColor[integration.health] : 'bg-slate-300'
                    }`} />
                  </div>
                </div>
              );
            })}
          </div>
        </div>

        {/* Failed Job Log */}
        <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="flex items-center gap-3 px-5 py-3.5 border-b border-slate-100 bg-slate-50/50">
            <div className="w-7 h-7 rounded-lg bg-red-100 flex items-center justify-center">
              <XCircle size={14} className="text-red-600" />
            </div>
            <div>
              <span className="text-sm text-slate-800" style={{ fontWeight: 700 }}>Job Log</span>
              <span className="text-xs text-slate-400 ml-2">{filteredJobs.length} job{filteredJobs.length !== 1 ? 's' : ''}</span>
            </div>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b border-slate-100">
                  {['Job ID', 'Integration', 'Type', 'Status', 'Started', 'Completed', 'Error', 'Retries', 'Actions'].map(h => (
                    <th key={h} className="text-left text-xs text-slate-500 px-4 py-2.5" style={{ fontWeight: 600 }}>{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-50">
                {filteredJobs.map(job => {
                  const integration = integrations.find(i => i.id === job.integrationId);
                  const canRetry = job.status === 'failed' && job.retryCount < job.maxRetries;
                  return (
                    <tr key={job.id} className={`hover:bg-slate-50 transition-colors ${job.status === 'failed' ? 'bg-red-50/30' : ''}`}>
                      <td className="px-4 py-3">
                        <span className="text-xs text-slate-800 font-mono" style={{ fontWeight: 500 }}>{job.id}</span>
                      </td>
                      <td className="px-4 py-3">
                        <span className="text-xs text-slate-700" style={{ fontWeight: 500 }}>{integration?.name || '—'}</span>
                      </td>
                      <td className="px-4 py-3">
                        <span className="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded capitalize">{job.type}</span>
                      </td>
                      <td className="px-4 py-3">
                        <span className={`text-xs px-2 py-0.5 rounded ${statusColors[job.status] || ''}`} style={{ fontWeight: 600 }}>
                          {job.status}
                        </span>
                      </td>
                      <td className="px-4 py-3 text-xs text-slate-500">{formatDateTime(job.startedAt)}</td>
                      <td className="px-4 py-3 text-xs text-slate-500">{formatDateTime(job.completedAt)}</td>
                      <td className="px-4 py-3 max-w-[200px]">
                        {job.errorMessage ? (
                          <span className="text-xs text-red-600 truncate block" title={job.errorMessage}>{job.errorMessage}</span>
                        ) : (
                          <span className="text-xs text-slate-400">—</span>
                        )}
                      </td>
                      <td className="px-4 py-3">
                        <span className={`text-xs ${job.retryCount >= job.maxRetries ? 'text-red-600' : 'text-slate-600'}`} style={{ fontWeight: 500 }}>
                          {job.retryCount}/{job.maxRetries}
                        </span>
                      </td>
                      <td className="px-4 py-3">
                        {canRetry && (
                          <button
                            onClick={() => { setActiveJobId(job.id); setShowRetryModal(true); }}
                            className="flex items-center gap-1 text-xs border border-red-200 bg-red-50 text-red-700 rounded-lg px-2.5 py-1 hover:bg-red-100 transition-colors"
                            style={{ fontWeight: 500 }}
                          >
                            <RotateCcw size={10} />
                            Retry
                          </button>
                        )}
                        {job.status === 'completed' && (
                          <span className="flex items-center gap-1 text-xs text-emerald-600">
                            <CheckCircle size={10} />
                            Done
                          </span>
                        )}
                        {job.status === 'failed' && job.retryCount >= job.maxRetries && (
                          <span className="text-xs text-red-500" style={{ fontWeight: 500 }}>Max retries</span>
                        )}
                      </td>
                    </tr>
                  );
                })}
                {filteredJobs.length === 0 && (
                  <tr>
                    <td colSpan={9} className="text-center py-12 text-slate-400">
                      <CheckCircle size={28} className="mx-auto mb-2 opacity-40" />
                      <div className="text-sm" style={{ fontWeight: 500 }}>No jobs match current filter</div>
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>

        {/* Retry Queue */}
        {retryableJobs.length > 0 && (
          <div className="bg-white rounded-xl border border-amber-200 shadow-sm overflow-hidden">
            <div className="flex items-center gap-3 px-5 py-3.5 border-b border-amber-100 bg-amber-50/50">
              <div className="w-7 h-7 rounded-lg bg-amber-100 flex items-center justify-center">
                <RefreshCw size={14} className="text-amber-600" />
              </div>
              <div>
                <span className="text-sm text-amber-800" style={{ fontWeight: 700 }}>Retry Queue</span>
                <span className="text-xs text-amber-600 ml-2">{retryableJobs.length} job{retryableJobs.length !== 1 ? 's' : ''} can be retried</span>
              </div>
              <button className="ml-auto flex items-center gap-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg px-3 py-1.5 text-xs" style={{ fontWeight: 500 }}>
                <RefreshCw size={11} />
                Retry All
              </button>
            </div>
            <div className="divide-y divide-amber-50">
              {retryableJobs.map(job => {
                const integration = integrations.find(i => i.id === job.integrationId);
                return (
                  <div key={job.id} className="flex items-center gap-4 px-5 py-3 hover:bg-amber-50/30 transition-colors">
                    <div className="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0">
                      <WifiOff size={14} className="text-red-600" />
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-2">
                        <span className="text-xs text-slate-800 font-mono" style={{ fontWeight: 500 }}>{job.id}</span>
                        <span className="text-xs text-slate-500">{integration?.name}</span>
                      </div>
                      <div className="text-xs text-red-600 mt-0.5 truncate">{job.errorMessage}</div>
                    </div>
                    <div className="text-xs text-slate-500">
                      Attempt {job.retryCount}/{job.maxRetries}
                    </div>
                    <button
                      onClick={() => { setActiveJobId(job.id); setShowRetryModal(true); }}
                      className="flex items-center gap-1 text-xs bg-red-600 hover:bg-red-700 text-white rounded-lg px-3 py-1.5"
                      style={{ fontWeight: 500 }}
                    >
                      <RotateCcw size={10} />
                      Retry
                    </button>
                  </div>
                );
              })}
            </div>
          </div>
        )}
      </div>

      {/* Bottom Bar */}
      <div className="bg-white border-t border-slate-200 px-6 py-3 flex items-center justify-between flex-shrink-0">
        <Link to="/" className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50">
          <ArrowLeft size={14} />
          Back
        </Link>
        <div className="flex items-center gap-2 text-xs text-slate-400">
          <Wifi size={11} />
          <span>{integrations.filter(i => i.enabled).length} active connectors</span>
          <span className="text-slate-300">|</span>
          <span>{outageCount} outage{outageCount !== 1 ? 's' : ''}</span>
        </div>
      </div>

      {/* Retry Integration Job SlideOver */}
      <SlideOver
        open={showRetryModal}
        onOpenChange={setShowRetryModal}
        title="Retry Integration Job"
        description={activeJob ? `${activeJob.id} — ${activeJobIntegration?.name}` : ''}
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowRetryModal(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button className="bg-red-600 hover:bg-red-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>
              <span className="flex items-center gap-2"><RefreshCw size={14} /> Retry Now</span>
            </button>
          </div>
        }
      >
        {activeJob && activeJobIntegration && (() => {
          const catalog = connectorCatalog.find(c => c.id === activeJobIntegration.catalogId);
          const IconComp = catalogIcons[catalog?.icon || 'server'] || Server;
          return (
            <div className="space-y-5">
              {/* Job Details */}
              <div className="flex items-center gap-3 bg-slate-50 border border-slate-200 rounded-xl p-4">
                <div className="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center">
                  <IconComp size={18} className="text-slate-600" />
                </div>
                <div className="flex-1 min-w-0">
                  <div className="text-sm text-slate-800" style={{ fontWeight: 600 }}>{activeJobIntegration.name}</div>
                  <div className="text-xs text-slate-500">{catalog?.type} · {activeJobIntegration.endpoint}</div>
                </div>
                <span className={`text-xs px-2 py-0.5 rounded ${statusColors[activeJobIntegration.health]}`} style={{ fontWeight: 600 }}>
                  {activeJobIntegration.health}
                </span>
              </div>

              {/* Error Display */}
              <div className="bg-red-50 border border-red-200 rounded-xl p-4">
                <div className="flex items-start gap-2">
                  <XCircle size={15} className="text-red-500 mt-0.5 flex-shrink-0" />
                  <div>
                    <div className="text-sm text-red-700 mb-1" style={{ fontWeight: 600 }}>Error Message</div>
                    <p className="text-xs text-red-600 leading-relaxed">{activeJob.errorMessage}</p>
                    <div className="text-xs text-red-400 mt-2">
                      Failed at: {formatDateTime(activeJob.completedAt)}
                    </div>
                  </div>
                </div>
              </div>

              {/* Job Info Grid */}
              <div className="border border-slate-200 rounded-xl overflow-hidden">
                <div className="bg-slate-50 px-4 py-2.5 border-b border-slate-200">
                  <span className="text-xs text-slate-500" style={{ fontWeight: 600 }}>JOB DETAILS</span>
                </div>
                <div className="divide-y divide-slate-50">
                  {[
                    { label: 'Job ID', value: activeJob.id },
                    { label: 'Type', value: activeJob.type },
                    { label: 'Status', value: activeJob.status },
                    { label: 'Started', value: formatDateTime(activeJob.startedAt) },
                    { label: 'Completed', value: formatDateTime(activeJob.completedAt) },
                  ].map(row => (
                    <div key={row.label} className="flex items-center justify-between px-4 py-2.5">
                      <span className="text-xs text-slate-500">{row.label}</span>
                      <span className="text-xs text-slate-700 font-mono" style={{ fontWeight: 500 }}>{row.value}</span>
                    </div>
                  ))}
                </div>
              </div>

              {/* Retry Count */}
              <div className={`rounded-xl border p-4 ${
                activeJob.retryCount >= activeJob.maxRetries - 1 ? 'bg-amber-50 border-amber-200' : 'bg-blue-50 border-blue-200'
              }`}>
                <div className="flex items-center justify-between mb-2">
                  <span className="text-xs text-slate-600" style={{ fontWeight: 600 }}>Retry Progress</span>
                  <span className={`text-xs ${
                    activeJob.retryCount >= activeJob.maxRetries - 1 ? 'text-amber-700' : 'text-blue-700'
                  }`} style={{ fontWeight: 700 }}>
                    {activeJob.retryCount} / {activeJob.maxRetries}
                  </span>
                </div>
                <div className="w-full h-2 bg-white rounded-full overflow-hidden">
                  <div
                    className={`h-full rounded-full transition-all ${
                      activeJob.retryCount >= activeJob.maxRetries - 1 ? 'bg-amber-500' : 'bg-blue-500'
                    }`}
                    style={{ width: `${(activeJob.retryCount / activeJob.maxRetries) * 100}%` }}
                  />
                </div>
                {activeJob.retryCount >= activeJob.maxRetries - 1 && (
                  <div className="flex items-center gap-1.5 mt-2">
                    <AlertTriangle size={11} className="text-amber-600" />
                    <span className="text-xs text-amber-700">This is the final retry attempt</span>
                  </div>
                )}
              </div>

              <div className="bg-slate-50 border border-slate-200 rounded-xl p-3">
                <div className="flex items-center gap-2">
                  <AlertTriangle size={14} className="text-slate-500 flex-shrink-0" />
                  <span className="text-xs text-slate-600 leading-relaxed">
                    Retrying will re-execute the <b>{activeJob.type}</b> job against <b>{activeJobIntegration.name}</b>.
                    Confirm the integration endpoint is reachable before retrying.
                  </span>
                </div>
              </div>
            </div>
          );
        })()}
      </SlideOver>
    </div>
  );
}

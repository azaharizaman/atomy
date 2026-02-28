import { getFeatureFlags } from "@/lib/api";

export default async function FeatureFlagsPage() {
  let flags: Awaited<ReturnType<typeof getFeatureFlags>> = [];
  let error: string | null = null;

  try {
    flags = await getFeatureFlags();
  } catch (e) {
    error = e && typeof e === "object" && "message" in e ? String(e.message) : "Failed to load feature flags";
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight text-[var(--foreground)]">
          Feature Flags
        </h1>
        <p className="mt-1 text-zinc-400">
          Feature toggles and rollout configuration
        </p>
      </div>

      {error && (
        <div className="rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-red-200">
          {error}
        </div>
      )}

      <div className="overflow-hidden rounded-xl border border-[var(--border)]">
        <table className="w-full">
          <thead>
            <tr className="border-b border-[var(--border)] bg-[var(--surface)]">
              <th className="px-4 py-3 text-left text-sm font-medium text-zinc-400">
                Flag
              </th>
              <th className="px-4 py-3 text-left text-sm font-medium text-zinc-400">
                Status
              </th>
              <th className="px-4 py-3 text-left text-sm font-medium text-zinc-400">
                Strategy
              </th>
              <th className="px-4 py-3 text-left text-sm font-medium text-zinc-400">
                Value
              </th>
              <th className="px-4 py-3 text-left text-sm font-medium text-zinc-400">
                Scope
              </th>
            </tr>
          </thead>
          <tbody>
            {flags.length === 0 && !error ? (
              <tr>
                <td colSpan={5} className="px-4 py-12 text-center text-zinc-500">
                  No feature flags found
                </td>
              </tr>
            ) : (
              flags.map((f) => (
                <tr
                  key={f.name}
                  className="border-b border-[var(--border)] last:border-0 hover:bg-zinc-800/30"
                >
                  <td className="px-4 py-3 font-mono text-sm text-[var(--foreground)]">
                    {f.name}
                  </td>
                  <td className="px-4 py-3">
                    <span
                      className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${
                        f.enabled
                          ? "bg-emerald-500/20 text-emerald-400"
                          : "bg-zinc-500/20 text-zinc-400"
                      }`}
                    >
                      {f.enabled ? "Enabled" : "Disabled"}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-sm text-zinc-400">
                    {f.strategy ?? "—"}
                  </td>
                  <td className="px-4 py-3 font-mono text-sm text-zinc-500">
                    {f.value != null ? String(f.value) : "—"}
                  </td>
                  <td className="px-4 py-3 text-sm text-zinc-500">
                    {f.scope ?? "—"}
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}

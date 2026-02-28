import { getModules } from "@/lib/api";

export default async function ModulesPage() {
  let modules: Awaited<ReturnType<typeof getModules>> = [];
  let error: string | null = null;

  try {
    modules = await getModules();
  } catch (e) {
    error = e && typeof e === "object" && "message" in e ? String(e.message) : "Failed to load modules";
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight text-[var(--foreground)]">
          Modules
        </h1>
        <p className="mt-1 text-zinc-400">
          Available and installed Nexus modules
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
                Module
              </th>
              <th className="px-4 py-3 text-left text-sm font-medium text-zinc-400">
                Version
              </th>
              <th className="px-4 py-3 text-left text-sm font-medium text-zinc-400">
                Status
              </th>
              <th className="px-4 py-3 text-left text-sm font-medium text-zinc-400">
                Installed
              </th>
            </tr>
          </thead>
          <tbody>
            {modules.length === 0 && !error ? (
              <tr>
                <td colSpan={4} className="px-4 py-12 text-center text-zinc-500">
                  No modules found
                </td>
              </tr>
            ) : (
              modules.map((m) => (
                <tr
                  key={m.id}
                  className="border-b border-[var(--border)] last:border-0 hover:bg-zinc-800/30"
                >
                  <td className="px-4 py-3">
                    <div>
                      <span className="font-medium text-[var(--foreground)]">
                        {m.name}
                      </span>
                      {m.description && (
                        <p className="text-sm text-zinc-500">{m.description}</p>
                      )}
                    </div>
                  </td>
                  <td className="px-4 py-3 text-sm text-zinc-400">
                    {m.version ?? "—"}
                  </td>
                  <td className="px-4 py-3">
                    <span
                      className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${
                        m.isInstalled
                          ? "bg-emerald-500/20 text-emerald-400"
                          : "bg-zinc-500/20 text-zinc-400"
                      }`}
                    >
                      {m.isInstalled ? "Installed" : "Available"}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-sm text-zinc-500">
                    {m.installedAt
                      ? new Date(m.installedAt).toLocaleDateString()
                      : "—"}
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

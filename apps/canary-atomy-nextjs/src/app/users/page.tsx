import { getUsers } from "@/lib/api";

export default async function UsersPage() {
  let users: Awaited<ReturnType<typeof getUsers>> = [];
  let error: string | null = null;

  try {
    users = await getUsers();
  } catch (e) {
    error = e && typeof e === "object" && "message" in e ? String(e.message) : "Failed to load users";
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight text-[var(--foreground)]">
          Users
        </h1>
        <p className="mt-1 text-zinc-400">
          Tenant-scoped user accounts
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
                Name
              </th>
              <th className="px-4 py-3 text-left text-sm font-medium text-zinc-400">
                Email
              </th>
              <th className="px-4 py-3 text-left text-sm font-medium text-zinc-400">
                Status
              </th>
              <th className="px-4 py-3 text-left text-sm font-medium text-zinc-400">
                Roles
              </th>
              <th className="px-4 py-3 text-left text-sm font-medium text-zinc-400">
                Created
              </th>
            </tr>
          </thead>
          <tbody>
            {users.length === 0 && !error ? (
              <tr>
                <td colSpan={5} className="px-4 py-12 text-center text-zinc-500">
                  No users found
                </td>
              </tr>
            ) : (
              users.map((u) => (
                <tr
                  key={u.id}
                  className="border-b border-[var(--border)] last:border-0 hover:bg-zinc-800/30"
                >
                  <td className="px-4 py-3 font-medium text-[var(--foreground)]">
                    {u.name ?? "—"}
                  </td>
                  <td className="px-4 py-3 text-sm text-zinc-400">{u.email}</td>
                  <td className="px-4 py-3">
                    <span
                      className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${
                        u.status === "active"
                          ? "bg-emerald-500/20 text-emerald-400"
                          : "bg-zinc-500/20 text-zinc-400"
                      }`}
                    >
                      {u.status ?? "—"}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-sm text-zinc-500">
                    {Array.isArray(u.roles) ? u.roles.join(", ") : "—"}
                  </td>
                  <td className="px-4 py-3 text-sm text-zinc-500">
                    {u.createdAt
                      ? new Date(u.createdAt).toLocaleDateString()
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

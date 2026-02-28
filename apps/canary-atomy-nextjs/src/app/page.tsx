import Link from "next/link";
import { getModules, getUsers, getFeatureFlags } from "@/lib/api";

export default async function DashboardPage() {
  let modulesCount = 0;
  let usersCount = 0;
  let flagsCount = 0;
  let apiError: string | null = null;

  try {
    const [modules, users, flags] = await Promise.all([
      getModules(),
      getUsers(),
      getFeatureFlags(),
    ]);
    modulesCount = modules.length;
    usersCount = users.length;
    flagsCount = flags.length;
  } catch (e) {
    apiError = e && typeof e === "object" && "message" in e ? String(e.message) : "Failed to connect to API";
  }

  const cards = [
    {
      href: "/modules",
      label: "Modules",
      count: modulesCount,
      description: "Available and installed Nexus modules",
    },
    {
      href: "/users",
      label: "Users",
      count: usersCount,
      description: "Tenant-scoped user accounts",
    },
    {
      href: "/feature-flags",
      label: "Feature Flags",
      count: flagsCount,
      description: "Feature toggles and rollout config",
    },
  ];

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight text-[var(--foreground)]">
          Dashboard
        </h1>
        <p className="mt-1 text-zinc-400">
          Overview of your Atomy Nexus instance
        </p>
      </div>

      {apiError && (
        <div className="rounded-lg border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-amber-200">
          <p className="font-medium">API connection issue</p>
          <p className="mt-1 text-sm text-amber-200/80">{apiError}</p>
          <p className="mt-2 text-sm">
            Ensure <code className="rounded bg-zinc-800 px-1">canary-atomy-api</code> is running on{" "}
            <code className="rounded bg-zinc-800 px-1">
              {process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000"}
            </code>
          </p>
        </div>
      )}

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {cards.map(({ href, label, count, description }) => (
          <Link
            key={href}
            href={href}
            className="group rounded-xl border border-[var(--border)] bg-[var(--surface)] p-6 transition-colors hover:border-[var(--accent-muted)]/50 hover:bg-zinc-800/50"
          >
            <div className="flex items-baseline justify-between">
              <span className="text-sm font-medium text-zinc-400">{label}</span>
              <span className="text-2xl font-semibold text-[var(--accent)]">
                {count}
              </span>
            </div>
            <p className="mt-2 text-sm text-zinc-500">{description}</p>
            <span className="mt-4 inline-block text-sm font-medium text-[var(--accent)] opacity-0 transition-opacity group-hover:opacity-100">
              View →
            </span>
          </Link>
        ))}
      </div>
    </div>
  );
}

import Link from "next/link";
import { getModules, getUsers, getFeatureFlags } from "@/lib/api";
import { ContentHeader } from "@/components/ContentHeader";
import { ContentCard } from "@/components/ContentCard";

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
    apiError =
      e && typeof e === "object" && "message" in e
        ? String((e as { message: string }).message)
        : "Failed to connect to API";
  }

  const cards = [
    {
      href: "/modules",
      title: "Modules",
      subtitle: "Available and installed Nexus modules",
      count: `${modulesCount} items`,
      editedAt: "just now",
    },
    {
      href: "/users",
      title: "Users",
      subtitle: "Tenant-scoped user accounts",
      count: `${usersCount} users`,
      editedAt: "recently",
    },
    {
      href: "/feature-flags",
      title: "Feature Flags",
      subtitle: "Feature toggles and rollout config",
      count: `${flagsCount} flags`,
      editedAt: "recently",
    },
  ];

  return (
    <div className="flex flex-col">
      <div className="border-b border-[var(--border)] bg-white px-8 py-6">
        <ContentHeader
          title="Dashboard"
          tabs={[
            { id: "folder", label: "Folder", href: "/" },
            { id: "modules", label: "Modules", href: "/modules" },
            { id: "users", label: "Users", href: "/users" },
            { id: "feature-flags", label: "Feature Flags", href: "/feature-flags" },
          ]}
          activeTab="folder"
          itemCount={3}
          showViewToggle={true}
        />
      </div>

      <div className="flex-1 px-8 py-6">
        {apiError && (
          <div className="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-800">
            <p className="font-medium">API connection issue</p>
            <p className="mt-1 text-sm text-amber-700/90">{apiError}</p>
            <p className="mt-2 text-sm">
              Ensure{" "}
              <code className="rounded bg-amber-100 px-1.5 py-0.5 font-mono text-sm">
                canary-atomy-api
              </code>{" "}
              is running on{" "}
              <code className="rounded bg-amber-100 px-1.5 py-0.5 font-mono text-sm">
                {process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000"}
              </code>
            </p>
          </div>
        )}

        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          {cards.map(({ href, title, subtitle, count, editedAt }) => (
            <ContentCard
              key={href}
              href={href}
              title={title}
              subtitle={subtitle}
              count={count}
              editedAt={editedAt}
            />
          ))}
        </div>
      </div>
    </div>
  );
}

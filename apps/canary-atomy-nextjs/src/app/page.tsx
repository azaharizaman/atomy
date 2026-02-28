import { getModules, getUsers, getFeatureFlags } from "@/lib/api";
import { ContentHeader } from "@/components/ContentHeader";
import { ContentCard } from "@/components/ContentCard";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { AlertTriangle } from "lucide-react";

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
      <div className="border-b bg-card px-8 py-6">
        <ContentHeader
          title="Dashboard"
          tabs={[
            { id: "folder", label: "Folder", href: "/" },
            { id: "modules", label: "Modules", href: "/modules" },
            { id: "users", label: "Users", href: "/users" },
            { id: "feature-flags", label: "Feature Flags", href: "/feature-flags" },
          ]}
          activeTab="folder"
          itemCount={cards.length}
          showViewToggle={true}
        />
      </div>

      <div className="flex-1 px-8 py-6">
        {apiError && (
          <Alert variant="destructive" className="mb-6">
            <AlertTriangle className="h-4 w-4" />
            <AlertTitle>API connection issue</AlertTitle>
            <AlertDescription>
              {apiError}. Ensure canary-atomy-api is running on {process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000"}.
            </AlertDescription>
          </Alert>
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

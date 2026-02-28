import { getFeatureFlags } from "@/lib/api";
import { ContentHeader } from "@/components/ContentHeader";
import { ContentCard } from "@/components/ContentCard";
import { Bookmark } from "lucide-react";

export default async function FeatureFlagsPage() {
  let flags: Awaited<ReturnType<typeof getFeatureFlags>> = [];
  let error: string | null = null;

  try {
    flags = await getFeatureFlags();
  } catch (e) {
    error =
      e && typeof e === "object" && "message" in e
        ? String((e as { message: string }).message)
        : "Failed to load feature flags";
  }

  return (
    <div className="flex flex-col">
      <div className="border-b border-[var(--border)] bg-white px-8 py-6">
        <ContentHeader
          title="Feature Flags"
          tabs={[
            { id: "folder", label: "Folder", href: "/" },
            { id: "modules", label: "Modules", href: "/modules" },
            { id: "users", label: "Users", href: "/users" },
            { id: "feature-flags", label: "Feature Flags", href: "/feature-flags" },
          ]}
          activeTab="feature-flags"
          itemCount={flags.length}
          showViewToggle={true}
        />
      </div>

      <div className="flex-1 px-8 py-6">
        {error && (
          <div className="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
            {error}
          </div>
        )}

        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          {flags.length === 0 && !error ? (
            <div className="col-span-full flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-[var(--border)] bg-[var(--surface)] py-16 text-center">
              <Bookmark className="h-12 w-12 text-[var(--text-muted-light)]" />
              <p className="mt-4 text-[var(--text-muted)]">No feature flags found</p>
            </div>
          ) : (
            flags.map((f) => (
              <ContentCard
                key={f.name}
                title={f.name}
                subtitle={f.strategy ?? undefined}
                count={f.enabled ? "Enabled" : "Disabled"}
                editedAt={f.scope ?? undefined}
              />
            ))
          )}
        </div>
      </div>
    </div>
  );
}

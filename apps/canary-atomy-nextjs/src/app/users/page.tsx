import { getUsers } from "@/lib/api";
import { ContentHeader } from "@/components/ContentHeader";
import { ContentCard } from "@/components/ContentCard";
import { Users } from "lucide-react";

export default async function UsersPage() {
  let users: Awaited<ReturnType<typeof getUsers>> = [];
  let error: string | null = null;

  try {
    users = await getUsers();
  } catch (e) {
    error =
      e && typeof e === "object" && "message" in e
        ? String((e as { message: string }).message)
        : "Failed to load users";
  }

  return (
    <div className="flex flex-col">
      <div className="border-b border-[var(--border)] bg-white px-8 py-6">
        <ContentHeader
          title="Users"
          tabs={[
            { id: "folder", label: "Folder", href: "/" },
            { id: "modules", label: "Modules", href: "/modules" },
            { id: "users", label: "Users", href: "/users" },
            { id: "feature-flags", label: "Feature Flags", href: "/feature-flags" },
          ]}
          activeTab="users"
          itemCount={users.length}
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
          {users.length === 0 && !error ? (
            <div className="col-span-full flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-[var(--border)] bg-[var(--surface)] py-16 text-center">
              <Users className="h-12 w-12 text-[var(--text-muted-light)]" />
              <p className="mt-4 text-[var(--text-muted)]">No users found</p>
            </div>
          ) : (
            users.map((u) => (
              <ContentCard
                key={u.id}
                title={u.name ?? u.email}
                subtitle={u.email}
                count={u.status ?? "—"}
                editedAt={
                  u.createdAt
                    ? new Date(u.createdAt).toLocaleDateString()
                    : undefined
                }
              />
            ))
          )}
        </div>
      </div>
    </div>
  );
}

"use client";

import React, { useEffect, useState } from "react";
import { getUsers, suspendUser, activateUser, User } from "@/lib/api";
import { ContentHeader } from "@/components/ContentHeader";
import { ContentCard } from "@/components/ContentCard";
import { Users as UsersIcon, UserX, UserCheck, Loader2 } from "lucide-react";
import { useAuth } from "@/lib/auth";

export default function UsersPage() {
  const { auth } = useAuth();
  const [users, setUsers] = useState<User[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [actionLoading, setActionId] = useState<string | null>(null);

  const loadUsers = async () => {
    setIsLoading(true);
    try {
      const data = await getUsers();
      setUsers(data);
      setError(null);
    } catch (e: any) {
      setError(e.message || "Failed to load users");
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    loadUsers();
  }, []);

  const handleSuspend = async (id: string) => {
    setActionId(id);
    try {
      await suspendUser(id, "Suspended via Canary UI");
      await loadUsers();
    } catch (e: any) {
      alert(e.message || "Failed to suspend user");
    } finally {
      setActionId(null);
    }
  };

  const handleActivate = async (id: string) => {
    setActionId(id);
    try {
      await activateUser(id);
      await loadUsers();
    } catch (e: any) {
      alert(e.message || "Failed to activate user");
    } finally {
      setActionId(null);
    }
  };

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

        {isLoading ? (
          <div className="flex items-center justify-center py-20">
            <Loader2 className="h-8 w-8 animate-spin text-[var(--accent)]" />
          </div>
        ) : (
          <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            {users.length === 0 && !error ? (
              <div className="col-span-full flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-[var(--border)] bg-[var(--surface)] py-16 text-center">
                <UsersIcon className="h-12 w-12 text-[var(--text-muted-light)]" />
                <p className="mt-4 text-[var(--text-muted)]">No users found</p>
              </div>
            ) : (
              users.map((u) => (
                <div key={u.id} className="group relative">
                  <ContentCard
                    title={u.name ?? u.email}
                    subtitle={u.email}
                    count={u.status ?? "—"}
                    editedAt={
                      u.createdAt
                        ? new Date(u.createdAt).toLocaleDateString()
                        : undefined
                    }
                  />
                  
                  {auth && (
                    <div className="absolute top-4 right-4 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                      {u.status === "active" ? (
                        <button
                          onClick={() => handleSuspend(u.id)}
                          disabled={actionLoading === u.id}
                          className="p-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-colors"
                          title="Suspend User"
                        >
                          {actionLoading === u.id ? (
                            <Loader2 className="h-4 w-4 animate-spin" />
                          ) : (
                            <UserX className="h-4 w-4" />
                          )}
                        </button>
                      ) : (
                        <button
                          onClick={() => handleActivate(u.id)}
                          disabled={actionLoading === u.id}
                          className="p-2 rounded-lg bg-green-50 text-green-600 hover:bg-green-100 transition-colors"
                          title="Activate User"
                        >
                          {actionLoading === u.id ? (
                            <Loader2 className="h-4 w-4 animate-spin" />
                          ) : (
                            <UserCheck className="h-4 w-4" />
                          )}
                        </button>
                      )}
                    </div>
                  )}
                </div>
              ))
            )}
          </div>
        )}
      </div>
    </div>
  );
}

"use client";

import React, { useEffect, useState } from "react";
import { getUsers, suspendUser, activateUser, User } from "@/lib/api";
import { ContentHeader } from "@/components/ContentHeader";
import { DataTable } from "@/components/DataTable";
import { ColumnDef } from "@tanstack/react-table";
import { UserX, UserCheck, Loader2, MoreHorizontal } from "lucide-react";
import { useAuth } from "@/lib/auth";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

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

  const columns: ColumnDef<User>[] = [
    {
      accessorKey: "name",
      header: "Name",
      cell: ({ row }) => {
        const user = row.original;
        return (
          <div className="flex flex-col">
            <span className="font-medium">{user.name || "N/A"}</span>
            <span className="text-xs text-muted-foreground">{user.email}</span>
          </div>
        );
      },
    },
    {
      accessorKey: "status",
      header: "Status",
      cell: ({ row }) => {
        const status = row.getValue("status") as string;
        return (
          <Badge variant={status === "active" ? "default" : "destructive"}>
            {status}
          </Badge>
        );
      },
    },
    {
      accessorKey: "createdAt",
      header: "Joined",
      cell: ({ row }) => {
        const date = row.getValue("createdAt") as string;
        return date ? new Date(date).toLocaleDateString() : "—";
      },
    },
    {
      id: "actions",
      cell: ({ row }) => {
        const user = row.original;

        return (
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0">
                <span className="sr-only">Open menu</span>
                <MoreHorizontal className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuLabel>Actions</DropdownMenuLabel>
              <DropdownMenuItem
                onClick={() => navigator.clipboard.writeText(user.id)}
              >
                Copy user ID
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              {user.status === "active" ? (
                <DropdownMenuItem
                  className="text-destructive"
                  onClick={() => handleSuspend(user.id)}
                  disabled={actionLoading === user.id}
                >
                  <UserX className="mr-2 h-4 w-4" />
                  Suspend User
                </DropdownMenuItem>
              ) : (
                <DropdownMenuItem
                  className="text-green-600"
                  onClick={() => handleActivate(user.id)}
                  disabled={actionLoading === user.id}
                >
                  <UserCheck className="mr-2 h-4 w-4" />
                  Activate User
                </DropdownMenuItem>
              )}
            </DropdownMenuContent>
          </DropdownMenu>
        );
      },
    },
  ];

  return (
    <div className="flex flex-col">
      <div className="border-b bg-card px-8 py-6">
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
          showViewToggle={false}
        />
      </div>

      <div className="flex-1 px-8 py-6">
        {error && (
          <div className="mb-6 rounded-xl border border-destructive/50 bg-destructive/10 px-4 py-3 text-destructive">
            {error}
          </div>
        )}

        {isLoading ? (
          <div className="flex items-center justify-center py-20">
            <Loader2 className="h-8 w-8 animate-spin text-primary" />
          </div>
        ) : (
          <DataTable columns={columns} data={users} searchKey="name" />
        )}
      </div>
    </div>
  );
}

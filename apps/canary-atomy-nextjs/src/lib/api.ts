/**
 * API client for Canary Atomy API (API Platform).
 * Fetches from /api/* endpoints with optional HTTP Basic auth.
 */

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";
const API_AUTH = process.env.NEXT_PUBLIC_API_AUTH || "";

export interface ApiError {
  message: string;
  status: number;
}

export interface AuthContextType {
  userId: string;
  email: string;
  tenantId: string;
  accessToken: string;
  refreshToken: string;
  sessionId: string;
  roles: string[];
}

async function fetchApi<T>(
  path: string,
  options: RequestInit = {}
): Promise<T> {
  const url = `${API_URL}${path.startsWith("/") ? path : `/${path}`}`;
  const headers: HeadersInit = {
    "Content-Type": "application/json",
    Accept: "application/ld+json",
    ...options.headers,
  };

  // Try Bearer token first from localStorage if available (client-side)
  if (typeof window !== "undefined") {
    const auth = localStorage.getItem("auth");
    if (auth) {
      const { accessToken } = JSON.parse(auth);
      if (accessToken) {
        (headers as Record<string, string>)["Authorization"] = `Bearer ${accessToken}`;
      }
    }
  }

  // Fallback to Basic auth if provided and no Bearer token
  if (!(headers as Record<string, string>)["Authorization"] && API_AUTH) {
    (headers as Record<string, string>)["Authorization"] = `Basic ${API_AUTH}`;
  }

  const res = await fetch(url, { ...options, headers });

  if (!res.ok) {
    const text = await res.text();
    let message = text;
    try {
      const json = JSON.parse(text);
      message = json["hydra:description"] ?? json.message ?? text;
    } catch {
      // use raw text
    }
    throw { message, status: res.status } as ApiError;
  }

  return res.json() as Promise<T>;
}

/** API Platform collection response (hydra) */
export interface HydraCollection<T> {
  "hydra:member": T[];
  "hydra:totalItems"?: number;
}

export interface User {
  id: string;
  email: string;
  name: string;
  status: string;
  roles: string[];
  createdAt: string;
}

export interface Module {
  id: string;
  moduleId: string;
  name: string;
  description: string;
  version: string;
  isInstalled: boolean;
  installedAt: string | null;
  installedBy: string | null;
}

export interface FeatureFlag {
  name: string;
  enabled: boolean;
  strategy: string;
  value: unknown;
  override: string | null;
  metadata: Record<string, unknown> | null;
  scope: string | null;
}

export async function login(email: string, password: string, tenantId: string): Promise<AuthContextType> {
  return fetchApi<AuthContextType>("/auth/login", {
    method: "POST",
    body: JSON.stringify({ email, password, tenantId }),
    headers: { Accept: "application/json" }, // Override default LD+JSON
  });
}

export async function logout(userId: string, tenantId: string, sessionId?: string): Promise<void> {
  await fetchApi<void>("/auth/logout", {
    method: "POST",
    body: JSON.stringify({ userId, tenantId, sessionId }),
    headers: { Accept: "application/json" },
  });
}

export async function refresh(refreshToken: string, tenantId: string): Promise<{ accessToken: string }> {
  return fetchApi<{ accessToken: string }>("/auth/refresh", {
    method: "POST",
    body: JSON.stringify({ refreshToken, tenantId }),
    headers: { Accept: "application/json" },
  });
}

export async function suspendUser(id: string, reason: string): Promise<void> {
  await fetchApi<void>(`/users/${id}/suspend`, {
    method: "POST",
    body: JSON.stringify({ reason }),
    headers: { Accept: "application/json" },
  });
}

export async function activateUser(id: string): Promise<void> {
  await fetchApi<void>(`/users/${id}/activate`, {
    method: "POST",
    headers: { Accept: "application/json" },
  });
}

export async function getUsers(): Promise<User[]> {
  const data = await fetchApi<HydraCollection<User>>("/api/users");
  return data["hydra:member"] ?? [];
}

export async function getModules(): Promise<Module[]> {
  const data = await fetchApi<HydraCollection<Module>>("/api/modules");
  return data["hydra:member"] ?? [];
}

export async function getFeatureFlags(): Promise<FeatureFlag[]> {
  const data = await fetchApi<HydraCollection<FeatureFlag>>("/api/feature-flags");
  return data["hydra:member"] ?? [];
}

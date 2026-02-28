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

  if (API_AUTH) {
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

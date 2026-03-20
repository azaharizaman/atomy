/**
 * Shared CORS + JSON route stubs for Playwright tests (request-derived Origin).
 */

export const buildCorsHeaders = (origin: string) => ({
  'access-control-allow-origin': origin,
  'access-control-allow-credentials': 'true',
  'access-control-allow-headers': 'Content-Type, Authorization',
  'access-control-allow-methods': 'GET,POST,OPTIONS',
});

export function getRequestOrigin(route: {
  request: () => { url: () => string; headers: () => Record<string, string> };
}): string {
  const req = route.request();
  const h = req.headers();
  const origin = h['origin'] ?? h['Origin'];
  if (origin) return origin;
  try {
    return new URL(req.url()).origin;
  } catch {
    const base = process.env.PLAYWRIGHT_BASE_URL ?? 'http://localhost:3100';
    try {
      return new URL(base).origin;
    } catch {
      return 'http://localhost:3100';
    }
  }
}

export async function fulfillJsonRoute(
  route: {
    request: () => { method: () => string; url: () => string; headers: () => Record<string, string> };
    fulfill: (opts: {
      status: number;
      headers?: Record<string, string>;
      contentType?: string;
      body?: string;
    }) => Promise<void>;
  },
  body: unknown,
  status = 200,
): Promise<void> {
  const reqOrigin = getRequestOrigin(route);
  const corsHeaders = buildCorsHeaders(reqOrigin);
  if (route.request().method() === 'OPTIONS') {
    await route.fulfill({ status: 204, headers: corsHeaders });
    return;
  }
  await route.fulfill({
    status,
    headers: corsHeaders,
    contentType: 'application/json',
    body: JSON.stringify(body),
  });
}

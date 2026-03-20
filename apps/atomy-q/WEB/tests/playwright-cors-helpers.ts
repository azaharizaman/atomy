/**
 * Shared CORS + JSON route stubs for Playwright tests (request-derived Origin).
 */

const DEFAULT_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
const DEFAULT_HEADERS = ['Content-Type', 'Authorization', 'Accept', 'X-Requested-With', 'X-XSRF-TOKEN'];

export function buildCorsHeaders(
  origin: string,
  methods: string[] = DEFAULT_METHODS,
  headers: string[] = DEFAULT_HEADERS,
): Record<string, string> {
  const methodList = [...new Set([...methods, 'OPTIONS'])];
  const headerList = [...new Set(headers)];
  return {
    'access-control-allow-origin': origin,
    'access-control-allow-credentials': 'true',
    'access-control-allow-headers': headerList.join(', '),
    'access-control-allow-methods': methodList.join(', '),
  };
}

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
  corsOptions?: { methods?: string[]; headers?: string[] },
): Promise<void> {
  const reqOrigin = getRequestOrigin(route);
  const corsHeaders = buildCorsHeaders(reqOrigin, corsOptions?.methods, corsOptions?.headers);
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

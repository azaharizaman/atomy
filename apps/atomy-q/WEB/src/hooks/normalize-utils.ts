'use client';

export function isObject(value: unknown): value is Record<string, unknown> {
  return value !== null && typeof value === 'object' && !Array.isArray(value);
}

export function toText(value: unknown): string | null {
  const type = typeof value;
  if (value === null || value === undefined || (type !== 'string' && type !== 'number' && type !== 'boolean')) {
    return null;
  }

  const text = String(value).trim();
  return text === '' ? null : text;
}

export function unwrapResponse(payload: unknown): unknown {
  if (!isObject(payload)) {
    return payload;
  }

  if (payload.data !== undefined) {
    return payload.data;
  }

  return payload;
}

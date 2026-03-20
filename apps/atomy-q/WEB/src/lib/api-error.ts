import axios from 'axios';

export interface ParsedApiError {
  status?: number;
  /** Primary human-readable message when the API provides one */
  message?: string;
  /** Laravel-style field → messages (Validator `errors` or exception `details`) */
  fieldErrors?: Record<string, string[]>;
  /** Optional machine-oriented code when present on the payload */
  code?: string;
}

function normalizeFieldErrors(raw: Record<string, unknown>): Record<string, string[]> {
  const out: Record<string, string[]> = {};
  for (const [key, value] of Object.entries(raw)) {
    if (Array.isArray(value)) {
      const msgs = value.filter((v): v is string => typeof v === 'string' && v.trim() !== '');
      if (msgs.length > 0) {
        out[key] = msgs;
      }
    } else if (typeof value === 'string' && value.trim() !== '') {
      out[key] = [value];
    }
  }
  return out;
}

/**
 * Maps Axios errors (and generic Errors) to a stable shape for UI and logging.
 * Supports Laravel Validator payloads (`errors`), global exception handler (`error` + `details`), and `message`.
 */
export function parseApiError(error: unknown): ParsedApiError {
  if (!axios.isAxiosError(error)) {
    if (error instanceof Error) {
      return { message: error.message };
    }
    return {};
  }

  const status = error.response?.status;
  const data = error.response?.data;

  if (data === null || data === undefined || typeof data !== 'object' || Array.isArray(data)) {
    return { status };
  }

  const rec = data as Record<string, unknown>;

  const messageFromString = (v: unknown): string | undefined =>
    typeof v === 'string' && v.trim() !== '' ? v.trim() : undefined;

  const message =
    messageFromString(rec.message) ??
    messageFromString(rec.error);

  const code = messageFromString(rec.code);

  let fieldErrors: Record<string, string[]> | undefined;

  if (rec.errors !== null && rec.errors !== undefined && typeof rec.errors === 'object' && !Array.isArray(rec.errors)) {
    fieldErrors = normalizeFieldErrors(rec.errors as Record<string, unknown>);
  }

  if (rec.details !== null && rec.details !== undefined && typeof rec.details === 'object' && !Array.isArray(rec.details)) {
    const details = normalizeFieldErrors(rec.details as Record<string, unknown>);
    fieldErrors = fieldErrors === undefined ? details : { ...fieldErrors, ...details };
  }

  return {
    status,
    message,
    fieldErrors: fieldErrors !== undefined && Object.keys(fieldErrors).length > 0 ? fieldErrors : undefined,
    code,
  };
}

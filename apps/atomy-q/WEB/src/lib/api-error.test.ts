import { describe, it, expect } from 'vitest';
import axios, { AxiosError } from 'axios';
import { parseApiError } from './api-error';

function axiosErrorWithData(status: number, data: unknown): AxiosError {
  const err = new AxiosError('Request failed');
  err.response = {
    status,
    statusText: 'Err',
    data,
    headers: {},
    config: err.config ?? {},
  };
  return err;
}

describe('parseApiError', () => {
  it('maps axios 422 validation payload with errors key', () => {
    const err = axiosErrorWithData(422, { errors: { email: ['invalid'] } });
    expect(axios.isAxiosError(err)).toBe(true);
    const r = parseApiError(err);
    expect(r.status).toBe(422);
    expect(r.fieldErrors?.email).toEqual(['invalid']);
  });

  it('maps Laravel exception handler validation shape (error + details)', () => {
    const err = axiosErrorWithData(422, {
      error: 'Validation failed',
      details: { title: ['required'] },
    });
    const r = parseApiError(err);
    expect(r.status).toBe(422);
    expect(r.message).toBe('Validation failed');
    expect(r.fieldErrors?.title).toEqual(['required']);
  });

  it('prefers message over error when both are strings', () => {
    const err = axiosErrorWithData(400, { message: 'Bad', error: 'Also bad' });
    const r = parseApiError(err);
    expect(r.message).toBe('Bad');
  });

  it('returns message for generic Error', () => {
    const r = parseApiError(new Error('network'));
    expect(r.message).toBe('network');
  });
});

/**
 * Alpha release-gate journey against the real local Laravel API.
 *
 * Requires a seeded API user and a running API at NEXT_PUBLIC_API_URL/E2E_API_URL.
 * Mocked Playwright specs remain fast regression checks; this file is release
 * evidence only when NEXT_PUBLIC_USE_MOCKS=false.
 */
import { expect, test } from '@playwright/test';
import { seedAuthSession } from './playwright-auth-bootstrap';

test.describe('alpha-gate real API journey', () => {
  test.beforeEach(() => {
    test.skip(process.env.NEXT_PUBLIC_USE_MOCKS === 'true', 'alpha gate requires real API mode');
  });

  test('buyer can enter the alpha procurement workspace and inspect RFQ evidence readiness', async ({
    page,
    request,
  }) => {
    const apiBase = process.env.NEXT_PUBLIC_API_URL ?? process.env.E2E_API_URL ?? 'http://localhost:8000/api/v1';
    const email = process.env.E2E_EMAIL ?? 'user1@nordfjord.example.com';
    const password = process.env.E2E_PASSWORD ?? 'secret';

    const loginRes = await request.post(`${apiBase}/auth/login`, {
      data: { email, password },
    });
    if (!loginRes.ok()) {
      throw new Error(`API login failed: ${loginRes.status()} ${await loginRes.text()}`);
    }

    const loginData = await loginRes.json();
    const token = loginData.access_token ?? loginData.token;
    if (!token) {
      throw new Error('No access token in login response.');
    }

    const user = loginData.user;
    if (!user || typeof user !== 'object') {
      throw new Error('Login response did not include a user payload.');
    }

    await seedAuthSession(page, user as Parameters<typeof seedAuthSession>[1], {
      token: String(token),
      refreshToken: loginData.refresh_token ?? null,
    });

    const timestamp = Date.now();
    const title = `Alpha gate RFQ ${timestamp}`;
    const createRes = await request.post(`${apiBase}/rfqs`, {
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'application/json',
        'Idempotency-Key': `alpha-gate-rfq-${timestamp}`,
      },
      data: {
        title,
        description: 'Alpha release-gate RFQ created through the real API.',
        category: 'Industrial Services',
        department: 'Procurement',
        estimated_value: 45000,
        submission_deadline: '2030-05-05T00:00:00.000Z',
      },
    });
    if (!createRes.ok()) {
      throw new Error(`Create RFQ failed: ${createRes.status()} ${await createRes.text()}`);
    }

    const createData = await createRes.json();
    const rfqId = createData.data?.id ?? createData.id;
    if (!rfqId) {
      throw new Error('No RFQ id in create response.');
    }

    await page.goto('/');
    await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();

    await page.goto('/rfqs');
    await expect(page.getByRole('heading', { name: 'Requisitions' })).toBeVisible();
    await expect(page.getByText(title)).toBeVisible({ timeout: 15000 });

    await page.goto(`/rfqs/${encodeURIComponent(String(rfqId))}/documents`);
    await expect(page.getByRole('heading', { level: 1, name: 'Evidence Vault' })).toBeVisible({ timeout: 15000 });
    await expect(page.getByRole('button', { name: /attach supporting evidence/i })).toBeEnabled();
    await expect(page.getByRole('button', { name: /finalize award pack/i })).toBeDisabled();
    await expect(page.getByText(/final comparison/i).first()).toBeVisible();
  });
});

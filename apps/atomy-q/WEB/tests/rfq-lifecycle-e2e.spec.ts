/**
 * Narrow live-API smoke for RFQ creation and browser overview rendering.
 *
 * Requires a working local test API at http://localhost:8000/api/v1 and a seeded
 * user1@example.com / secret account. Mocked RFQ browser journeys live in
 * rfq-alpha-journeys.spec.ts.
 */
import { expect, test } from '@playwright/test';
import { seedAuthSession } from './playwright-auth-bootstrap';

const realApiLifecycleTest = process.env.RUN_REAL_API_TESTS === 'true' ? test : test.skip;

test.describe('RFQ lifecycle live API smoke', () => {
  realApiLifecycleTest('creates an RFQ through the API and renders its overview in the browser', async ({
    page,
    request,
  }) => {
    const apiBase = process.env.NEXT_PUBLIC_API_URL ?? process.env.E2E_API_URL ?? 'http://localhost:8000/api/v1';
    const email = process.env.E2E_EMAIL ?? 'user1@example.com';
    const password = process.env.E2E_PASSWORD ?? 'secret';

    const loginRes = await request.post(`${apiBase}/auth/login`, {
      data: { email, password },
    });
    if (!loginRes.ok()) {
      throw new Error(
        `API login failed: ${loginRes.status()}. Ensure API is running and DB seeded (user1@example.com / secret).`,
      );
    }

    const loginData = await loginRes.json();
    const token = loginData.access_token ?? loginData.token;
    if (!token) {
      throw new Error('No access_token in login response.');
    }

    const user = loginData.user;
    if (!user || typeof user !== 'object') {
      throw new Error('Login response did not include a user payload.');
    }

    await seedAuthSession(page, user as Parameters<typeof seedAuthSession>[1], {
      token: String(token),
      refreshToken: loginData.refresh_token ?? null,
    });

    const title = `E2E RFQ real API smoke ${Date.now()}`;
    const createRes = await request.post(`${apiBase}/rfqs`, {
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'application/json',
        'Idempotency-Key': `e2e-rfq-real-api-smoke-${Date.now()}`,
      },
      data: {
        title,
        description: 'E2E test RFQ',
        category: 'IT Hardware',
        department: 'Procurement',
        estimated_value: 25000,
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

    await page.goto(`/rfqs/${encodeURIComponent(String(rfqId))}/overview`);
    await expect(page).toHaveURL(
      new RegExp(`/rfqs/${String(rfqId).replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}/overview`),
    );
    await expect(page.getByText(title)).toBeVisible({ timeout: 15000 });
    await expect(page.getByRole('heading', { name: 'Schedule' })).toBeVisible();
  });
});

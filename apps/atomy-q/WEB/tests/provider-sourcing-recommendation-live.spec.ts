import { expect, test } from '@playwright/test';

import { seedAuthSession } from './playwright-auth-bootstrap';

const liveEnabled = process.env.AI_PROVIDER_SOURCING_RECOMMENDATION_E2E === 'true';
const rfqId = process.env.SOURCING_RECOMMENDATION_RFQ_ID?.trim() ?? '';
const recommendedVendorName = process.env.SOURCING_RECOMMENDATION_RECOMMENDED_VENDOR?.trim() ?? '';
const manualVendorName = process.env.SOURCING_RECOMMENDATION_MANUAL_VENDOR?.trim() ?? '';

function escapeRegExp(string: string): string {
  return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

test.describe('provider-backed sourcing recommendation e2e with live backend', () => {
  test.describe.configure({ mode: 'serial' });
  test.setTimeout(300_000);
  test.skip(!liveEnabled, 'Set AI_PROVIDER_SOURCING_RECOMMENDATION_E2E=true to run live sourcing recommendation e2e.');
  test.skip(rfqId === '', 'Set SOURCING_RECOMMENDATION_RFQ_ID to the RFQ under test.');
  test.skip(
    recommendedVendorName === '',
    'Set SOURCING_RECOMMENDATION_RECOMMENDED_VENDOR to the vendor expected to surface as recommended.',
  );
  test.skip(
    manualVendorName === '',
    'Set SOURCING_RECOMMENDATION_MANUAL_VENDOR to a different approved vendor to verify manual shortlist save.',
  );

  test('renders live recommendation output and saves a manual shortlist', async ({ page, request }) => {
    const apiBase = process.env.NEXT_PUBLIC_API_URL ?? process.env.E2E_API_URL ?? 'http://localhost:8000/api/v1';
    const email = process.env.E2E_EMAIL ?? 'user1@example.com';
    const password = process.env.E2E_PASSWORD ?? 'secret';

    expect(process.env.AI_MODE).toBe('provider');
    expect(process.env.NEXT_PUBLIC_AI_MODE).toBe('provider');

    const loginResponse = await request.post(`${apiBase}/auth/login`, {
      data: { email, password },
    });
    expect(loginResponse.ok()).toBeTruthy();

    const loginPayload = await loginResponse.json();
    const token = String(loginPayload.access_token ?? loginPayload.token ?? '');
    expect(token).not.toBe('');

    if (!loginPayload.user || typeof loginPayload.user !== 'object' || !loginPayload.user.id) {
      const hasUser = !!loginPayload.user;
      const hasUserId = !!loginPayload.user?.id;
      const hasAccessToken = !!(loginPayload.access_token ?? loginPayload.token);
      throw new Error(`Login response invalid: user=${hasUser}, id=${hasUserId}, token=${hasAccessToken}`);
    }

    const user = loginPayload.user as {
      id: string;
      name: string;
      email: string;
      role: string;
      tenant_id?: string;
      tenantId?: string;
    };

    await seedAuthSession(
      page,
      {
        id: user.id,
        name: user.name,
        email: user.email,
        role: user.role,
        tenantId: String(user.tenant_id ?? user.tenantId ?? ''),
      },
      {
        token,
        refreshToken: loginPayload.refresh_token ?? loginPayload.refreshToken,
      },
    );

    await page.goto(`/rfqs/${rfqId}/vendors`);

    await expect(page.getByText('Selected approved vendors')).toBeVisible();
    await expect(page.getByText('AI recommendation summary')).toBeVisible();
    await expect(page.getByRole('checkbox', { name: new RegExp(`select ${escapeRegExp(recommendedVendorName)}`, 'i') })).not.toBeChecked();

    const recommendedButton = page.getByRole('button', {
      name: new RegExp(`why ${escapeRegExp(recommendedVendorName)} is recommended`, 'i'),
    });

    if (await recommendedButton.count()) {
      await recommendedButton.first().click();
    }

    await page.getByRole('checkbox', { name: new RegExp(`select ${escapeRegExp(manualVendorName)}`, 'i') }).check();
    
    const savePromise = page.waitForResponse(resp => 
      resp.url().includes('/selected-vendors') && resp.request().method() === 'PUT'
    );
    await page.getByRole('button', { name: /save selection/i }).click();
    await savePromise;

    await page.reload();
    await expect(page.getByText(new RegExp(escapeRegExp(manualVendorName), 'i'))).toBeVisible();
  });
});

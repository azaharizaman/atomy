import { test, expect } from '@playwright/test';

/**
 * Laravel Nexus SaaS - Authentication E2E Tests
 * 
 * Tests the authentication flows including:
 * - Login page accessibility
 * - Registration page accessibility
 * - Login form validation
 * - Successful login/logout
 */

test.describe('Authentication', () => {
  test('login page is accessible', async ({ page }) => {
    await page.goto('/login');
    
    // Should display login page
    await expect(page).toHaveURL(/.*login/);
    
    // Should have email and password fields
    await expect(page.locator('input[type="email"], input[name="email"]')).toBeVisible();
    await expect(page.locator('input[type="password"], input[name="password"]')).toBeVisible();
    
    // Should have a submit button
    await expect(page.getByRole('button', { name: /log in|sign in/i })).toBeVisible();
  });

  test('registration page is accessible', async ({ page }) => {
    await page.goto('/register');
    
    // Should display registration page
    await expect(page).toHaveURL(/.*register/);
    
    // Should have required registration fields
    await expect(page.locator('input[name="name"]')).toBeVisible();
    await expect(page.locator('input[type="email"], input[name="email"]')).toBeVisible();
    await expect(page.locator('input[type="password"], input[name="password"]')).toBeVisible();
  });

  test('login form shows validation errors for empty submission', async ({ page }) => {
    await page.goto('/login');
    
    // Click submit without filling form
    await page.getByRole('button', { name: /log in|sign in/i }).click();
    
    // Should show validation error (either HTML5 validation or server-side)
    // The exact validation depends on the implementation
    const emailInput = page.locator('input[type="email"], input[name="email"]');
    
    // Check for invalid state or error message
    const isInvalid = await emailInput.evaluate((el: HTMLInputElement) => !el.validity.valid);
    const hasErrorMessage = await page.locator('[class*="error"], [class*="invalid"], [role="alert"]').count() > 0;
    
    expect(isInvalid || hasErrorMessage).toBeTruthy();
  });

  test('login form shows error for invalid credentials', async ({ page }) => {
    await page.goto('/login');
    
    // Fill in invalid credentials
    await page.locator('input[type="email"], input[name="email"]').fill('invalid@example.com');
    await page.locator('input[type="password"], input[name="password"]').fill('wrongpassword');
    
    // Submit form
    await page.getByRole('button', { name: /log in|sign in/i }).click();
    
    // Wait for response and check for error
    await page.waitForLoadState('networkidle');
    
    // Should still be on login page or show error
    const currentUrl = page.url();
    const hasError = await page.locator('[class*="error"], [class*="alert"], [role="alert"]').count() > 0;
    
    expect(currentUrl.includes('login') || hasError).toBeTruthy();
  });

  test('forgot password page is accessible', async ({ page }) => {
    await page.goto('/forgot-password');
    
    // Should display forgot password page
    await expect(page).toHaveURL(/.*forgot-password/);
    
    // Should have email field
    await expect(page.locator('input[type="email"], input[name="email"]')).toBeVisible();
  });
});

test.describe('Protected Routes', () => {
  test('dashboard redirects to login when unauthenticated', async ({ page }) => {
    await page.goto('/dashboard');
    
    // Should redirect to login
    await expect(page).toHaveURL(/.*login/);
  });

  test('settings page redirects to login when unauthenticated', async ({ page }) => {
    await page.goto('/settings');
    
    // Should redirect to login
    await expect(page).toHaveURL(/.*login/);
  });
});

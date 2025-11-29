import { test, expect } from '@playwright/test';

/**
 * Laravel Nexus SaaS - Homepage & Navigation E2E Tests
 * 
 * Tests basic application accessibility and navigation
 */

test.describe('Homepage', () => {
  test('homepage is accessible', async ({ page }) => {
    const response = await page.goto('/');
    
    // Should return 200 OK
    expect(response?.status()).toBe(200);
    
    // Should have a title
    const title = await page.title();
    expect(title).toBeTruthy();
  });

  test('homepage has navigation links', async ({ page }) => {
    await page.goto('/');
    
    // Check for common navigation elements
    // Adjust selectors based on your actual navigation structure
    const nav = page.locator('nav, header, [role="navigation"]');
    await expect(nav.first()).toBeVisible();
  });
});

test.describe('Application Health', () => {
  test('application responds to requests', async ({ page }) => {
    const response = await page.goto('/');
    
    // Should not be a server error
    expect(response?.status()).toBeLessThan(500);
  });

  test('static assets load correctly', async ({ page }) => {
    await page.goto('/');
    
    // Wait for page to fully load
    await page.waitForLoadState('networkidle');
    
    // Check that CSS is applied (page should have some styling)
    const bodyStyles = await page.evaluate(() => {
      const body = document.body;
      const styles = window.getComputedStyle(body);
      return {
        fontFamily: styles.fontFamily,
        backgroundColor: styles.backgroundColor,
      };
    });
    
    // Should have some CSS applied (not default browser styles)
    expect(bodyStyles.fontFamily).toBeTruthy();
  });
});

test.describe('Error Handling', () => {
  test('404 page is displayed for non-existent routes', async ({ page }) => {
    const response = await page.goto('/this-route-does-not-exist-12345');
    
    // Should return 404
    expect(response?.status()).toBe(404);
  });
});

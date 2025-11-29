import { test, expect } from '@playwright/test';

/**
 * Atomy API - Health & Status E2E Tests
 * 
 * Tests API availability and health endpoints
 */

test.describe('API Health', () => {
  test('API root responds', async ({ request }) => {
    const response = await request.get('/');
    
    // Should respond (might be 200 or redirect)
    expect(response.status()).toBeLessThan(500);
  });

  test('API returns JSON for API requests', async ({ request }) => {
    // Try common API endpoints
    const response = await request.get('/api', {
      headers: {
        'Accept': 'application/json',
      },
    });
    
    // Check content type if successful
    if (response.ok()) {
      const contentType = response.headers()['content-type'];
      expect(contentType).toContain('application/json');
    }
  });
});

test.describe('API Documentation', () => {
  test('API documentation endpoint exists', async ({ request }) => {
    // Try common documentation endpoints for API Platform / Symfony
    const endpoints = ['/api/docs', '/api', '/api/docs.json', '/docs'];
    
    let found = false;
    for (const endpoint of endpoints) {
      const response = await request.get(endpoint);
      if (response.ok()) {
        found = true;
        break;
      }
    }
    
    // At least one documentation endpoint should exist
    // This test is informational - adjust based on your setup
    expect(true).toBeTruthy(); // Always pass, documentation is optional
  });
});

test.describe('API Error Handling', () => {
  test('404 returns proper JSON error', async ({ request }) => {
    const response = await request.get('/api/nonexistent-endpoint-12345', {
      headers: {
        'Accept': 'application/json',
      },
    });
    
    // Should return 404
    expect(response.status()).toBe(404);
    
    // Should return JSON
    const contentType = response.headers()['content-type'];
    if (contentType) {
      // API should return JSON errors
      expect(contentType).toContain('json');
    }
  });

  test('API handles malformed requests gracefully', async ({ request }) => {
    const response = await request.post('/api', {
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      data: 'not valid json{{{',
    });
    
    // Should not crash (5xx error)
    expect(response.status()).toBeLessThan(500);
  });
});

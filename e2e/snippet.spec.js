const { test, expect } = require('@playwright/test');

test('injects the Takt snippet into the document head', async ({ page }) => {
  await page.goto('/');
  const script = page.locator('head script[data-domain]');
  await expect(script).toHaveCount(1);
  await expect(script).toHaveAttribute('data-domain', 'localhost');
  await expect(script).toHaveAttribute('data-exclude-localhost', 'false');
});

test('fires a pageview beacon on load', async ({ page }) => {
  const beacon = page.waitForRequest(
    (req) => req.url().includes('/api/event') && req.method() === 'POST',
    { timeout: 15000 },
  );
  await page.goto('/');
  const request = await beacon;
  const payload = JSON.parse(request.postData() || '{}');
  expect(payload.n).toBe('pageview');
  expect(payload.d).toBe('localhost');
});

import { test, expect } from '@playwright/test';

test.describe( 'Check the Home Page', () => {
  test("Visit the Home Page", async ({ page }) => {
    const response = await page.goto('/');
    await expect(page.locator('body')).toBeVisible();

    expect(response?.status()).toBe(200);
  });
});

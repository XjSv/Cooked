import { test, expect } from '@playwright/test';

test.describe( 'Check the Home Page', () => {
  test("Visit the Home Page", async ({ page }) => {
    const response = await page.goto('https://dev.mimisrecipes.ddev.site/');
    await expect(page.getByText('Welcome to Mimis Recipes!')).toBeVisible();

    // Check the status code 200
    expect(response?.status()).toBe(200);
  });
});

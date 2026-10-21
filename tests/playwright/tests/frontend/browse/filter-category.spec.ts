import { test, expect } from '@playwright/test';

test.describe('Filter Browse Recipes (anonymous user)', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  test('Filter recipes by category', async ({ page }) => {
    await page.goto('/browse-recipes', { waitUntil: 'networkidle' });

    await expect(page.locator('.cooked-recipe-search')).toBeDefined();

    await page.click('.cooked-browse-select');
    await expect(page.locator('.cooked-browse-select-block')).toBeVisible();
    await page.click('.cooked-tax-column:has-text("Categories") a[href*="/recipe-category/bread"]');

    await page.waitForURL('**/browse-recipes/recipe-category/bread');
    await page.waitForLoadState('networkidle');

    const firstRecipe = page.locator('.cooked-recipe').first();
    await expect(firstRecipe.locator('.cooked-recipe-name')).toHaveText('Albanian Bread Stuffed with Cheese (Pogaça me Djathë)');
    await expect(firstRecipe).toHaveAttribute('id', 'cooked-recipe-3587');
  });
});

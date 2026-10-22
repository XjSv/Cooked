import { test, expect } from '@playwright/test';
import { SEED_RECIPES } from '../../../utils/seed';

test.describe('Filter Browse Recipes (anonymous user)', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  test('Filter recipes by category', async ({ page }) => {
    await page.goto('/browse-recipes', { waitUntil: 'networkidle' });

    await expect(page.locator('.cooked-recipe-search')).toBeDefined();

    await page.click('.cooked-browse-select');
    await expect(page.locator('.cooked-browse-select-block')).toBeVisible();
    await page.click('.cooked-tax-column:has-text("Categories") a[href*="/recipe-category/bread"]');

    await page.waitForURL(/\/browse-recipes\/recipe-category\/bread\/?$/);
    await page.waitForLoadState('networkidle');

    // Global setup puts a recipe in Bread on every site.
    const titles = page.locator('.cooked-recipe .cooked-recipe-card-title');
    await expect(titles.filter({ hasText: SEED_RECIPES.bread.title })).toBeVisible();
    await expect(titles.filter({ hasText: SEED_RECIPES.beef.title })).toHaveCount(0);
  });
});

import { test, expect } from '@playwright/test';
import { SEED_RECIPES } from '../../../utils/seed';

test.describe('Sort Browse Recipes (anonymous user)', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  test('Sort recipes by date', async ({ page }) => {
    await page.goto('/browse-recipes', { waitUntil: 'networkidle' });

    await expect(page.locator('.cooked-recipe-search')).toBeDefined();

    await page.selectOption('.cooked-sortby-select', 'date_asc');
    await page.evaluate(() => {
      document.querySelector('.cooked-sortby-select')!.closest('form')!.dispatchEvent(new Event('submit'));
    });

    await page.waitForURL(/\/browse-recipes\/sort\/date_asc\/?$/);
    await page.waitForLoadState('networkidle');

    // The oldest seed recipe comes first.
    const firstRecipe = page.locator('.cooked-recipe .cooked-recipe-card-title').first();
    await expect(firstRecipe).toHaveText(SEED_RECIPES.bread.title);

    await page.selectOption('.cooked-sortby-select', 'date_desc');
    await page.evaluate(() => {
      document.querySelector('.cooked-sortby-select')!.closest('form')!.dispatchEvent(new Event('submit'));
    });

    await page.waitForURL(/\/browse-recipes\/sort\/date_desc\/?$/);
    await page.waitForLoadState('networkidle');

    // Newest first: the oldest seed recipe is no longer at the top.
    const newestRecipe = page.locator('.cooked-recipe .cooked-recipe-card-title').first();
    await expect(newestRecipe).toBeVisible();
    await expect(newestRecipe).not.toHaveText(SEED_RECIPES.bread.title);
  });
});

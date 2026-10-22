import { test, expect } from '@playwright/test';
import { SEED_RECIPES } from '../../../utils/seed';

test.describe('Search Browse Recipes (anonymous user)', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  test('Search recipes by title (beef)', async ({ page }) => {
    await page.goto('/browse-recipes', { waitUntil: 'networkidle' });

    await expect(page.locator('.cooked-recipe-search:not(.cooked-search-compact)')).toBeDefined();

    await page.fill('.cooked-recipe-search:not(.cooked-search-compact) .cooked-browse-search', 'beef');

    await page.evaluate(() => {
      document.querySelector('.cooked-recipe-search:not(.cooked-search-compact) .cooked-browse-search')!.closest('form')!.dispatchEvent(new Event('submit'));
    });

    await page.waitForURL(/\/browse-recipes\/search\/beef\/sort\/date_desc\/?$/);
    await page.waitForLoadState('networkidle');

    const titles = page.locator('.cooked-recipe .cooked-recipe-card-title');
    await expect(titles.filter({ hasText: SEED_RECIPES.beef.title })).toBeVisible();
    await expect(titles.filter({ hasText: SEED_RECIPES.chicken.title })).toHaveCount(0);
  });

  test('Search recipes by title (chicken)', async ({ page }) => {
    await page.goto('/browse-recipes', { waitUntil: 'networkidle' });

    await expect(page.locator('.cooked-recipe-search:not(.cooked-search-compact)')).toBeDefined();

    await page.fill('.cooked-recipe-search:not(.cooked-search-compact) .cooked-browse-search', 'chicken');

    await page.evaluate(() => {
      document.querySelector('.cooked-recipe-search:not(.cooked-search-compact) .cooked-browse-search')!.closest('form')!.dispatchEvent(new Event('submit'));
    });

    await page.waitForURL(/\/browse-recipes\/search\/chicken\/sort\/date_desc\/?$/);
    await page.waitForLoadState('networkidle');

    const titles = page.locator('.cooked-recipe .cooked-recipe-card-title');
    await expect(titles.filter({ hasText: SEED_RECIPES.chicken.title })).toBeVisible();
    await expect(titles.filter({ hasText: SEED_RECIPES.beef.title })).toHaveCount(0);
  });
});

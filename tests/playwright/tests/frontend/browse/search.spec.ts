import { test, expect } from '@playwright/test';

test.describe('Search Browse Recipes (anonymous user)', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  test('Search recipes by title (beef)', async ({ page }) => {
    await page.goto('/browse-recipes', { waitUntil: 'networkidle' });

    await expect(page.locator('.cooked-recipe-search:not(.cooked-search-compact)')).toBeDefined();

    await page.fill('.cooked-recipe-search:not(.cooked-search-compact) .cooked-browse-search', 'beef');

    await page.evaluate(() => {
      document.querySelector('.cooked-recipe-search:not(.cooked-search-compact) .cooked-browse-search').closest('form').dispatchEvent(new Event('submit'));
    });

    await page.waitForURL('**/browse-recipes/search/beef/sort/date_desc');
    await page.waitForLoadState('networkidle');

    const firstRecipe = page.locator('.cooked-recipe').first();
    await expect(firstRecipe.locator('.cooked-recipe-name')).toHaveText('Albanian Pan-Fried Meatballs (Qofte të Skuqura)');
    await expect(firstRecipe).toHaveAttribute('id', 'cooked-recipe-2761');
  });

  test('Search recipes by title (chicken)', async ({ page }) => {
    await page.goto('/browse-recipes', { waitUntil: 'networkidle' });

    await expect(page.locator('.cooked-recipe-search:not(.cooked-search-compact)')).toBeDefined();

    await page.fill('.cooked-recipe-search:not(.cooked-search-compact) .cooked-browse-search', 'chicken');

    await page.evaluate(() => {
      document.querySelector('.cooked-recipe-search:not(.cooked-search-compact) .cooked-browse-search').closest('form').dispatchEvent(new Event('submit'));
    });

    await page.waitForURL('**/browse-recipes/search/chicken/sort/date_desc');
    await page.waitForLoadState('networkidle');

    const firstRecipe = page.locator('.cooked-recipe').first();
    await expect(firstRecipe.locator('.cooked-recipe-name')).toHaveText('Meatball Soup (Supë me Pasha Qofte)');
    await expect(firstRecipe).toHaveAttribute('id', 'cooked-recipe-2886');
  });
});

import { test, expect } from '@playwright/test';

test.describe('Sort Browse Recipes (anonymous user)', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  test('Sort recipes by date', async ({ page }) => {
    await page.goto('/browse-recipes', { waitUntil: 'networkidle' });

    await expect(page.locator('.cooked-recipe-search')).toBeDefined();

    await page.selectOption('.cooked-sortby-select', 'date_asc');
    await page.evaluate(() => {
      document.querySelector('.cooked-sortby-select').closest('form').dispatchEvent(new Event('submit'));
    });

    await page.waitForURL('**/browse-recipes/sort/date_asc');
    await page.waitForLoadState('networkidle');

    const firstRecipe = page.locator('.cooked-recipe').first();
    await expect(firstRecipe.locator('.cooked-recipe-name')).toHaveText('Albanian Flatbread (Pite në Tigan)');
    await expect(firstRecipe).toHaveAttribute('id', 'cooked-recipe-1866');

    await page.selectOption('.cooked-sortby-select', 'date_desc');
    await page.evaluate(() => {
      document.querySelector('.cooked-sortby-select').closest('form').dispatchEvent(new Event('submit'));
    });

    await page.waitForURL('**/browse-recipes/sort/date_desc');
    await page.waitForLoadState('networkidle');

    const secondRecipe = page.locator('.cooked-recipe').first();
    await expect(secondRecipe.locator('.cooked-recipe-name')).toHaveText('Albanian Bread Stuffed with Cheese (Pogaça me Djathë)');
    await expect(secondRecipe).toHaveAttribute('id', 'cooked-recipe-3587');
  });
});

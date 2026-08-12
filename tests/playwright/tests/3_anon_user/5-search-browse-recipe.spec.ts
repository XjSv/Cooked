import { test, expect } from '@playwright/test';

test.describe('Search Browse Recipes (anonymous user)', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  test('Search recipes by title (beef)', async ({ page }) => {
    await page.goto('/browse-recipes', { waitUntil: 'networkidle' });

    // Check if the search section is present
    await expect(page.locator('.basil-main-template .cooked-recipe-search')).toBeDefined();

    // Enter "beef" in the search input
    await page.fill('.basil-main-template .cooked-browse-search', 'beef');

    // Trigger form submission via JavaScript event
    await page.evaluate(() => {
      document.querySelector('.basil-main-template .cooked-browse-search').closest('form').dispatchEvent(new Event('submit'));
    });

    // Wait for navigation and URL change
    await page.waitForURL('**/browse-recipes/search/beef/sort/date_desc');

    // Wait for the page to finish loading after the search
    await page.waitForLoadState('networkidle');

    // Check if the first recipe is Albanian Flatbread
    const firstRecipe = page.locator('.cooked-recipe').first();
    await expect(firstRecipe.locator('.cooked-recipe-name')).toHaveText('Albanian Pan-Fried Meatballs (Qofte të Skuqura)');
    await expect(firstRecipe).toHaveAttribute('id', 'cooked-recipe-2761');
  });

  test('Search recipes by title (chicken)', async ({ page }) => {
    await page.goto('/browse-recipes', { waitUntil: 'networkidle' });

    // Check if the search section is present
    await expect(page.locator('.basil-main-template .cooked-recipe-search')).toBeDefined();

    // Enter "beef" in the search input
    await page.fill('.basil-main-template .cooked-browse-search', 'chicken');

    // Trigger form submission via JavaScript event
    await page.evaluate(() => {
      document.querySelector('.basil-main-template .cooked-browse-search').closest('form').dispatchEvent(new Event('submit'));
    });

    // Wait for navigation and URL change
    await page.waitForURL('**/browse-recipes/search/chicken/sort/date_desc');

    // Wait for the page to finish loading after the search
    await page.waitForLoadState('networkidle');

    // Check if the first recipe is Albanian Flatbread
    const firstRecipe = page.locator('.cooked-recipe').first();
    await expect(firstRecipe.locator('.cooked-recipe-name')).toHaveText('Meatball Soup (Supë me Pasha Qofte)');
    await expect(firstRecipe).toHaveAttribute('id', 'cooked-recipe-2886');
  });
});
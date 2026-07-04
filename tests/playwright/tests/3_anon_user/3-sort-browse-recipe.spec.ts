import { test, expect } from '@playwright/test';

test.describe('Sort Browse Recipes (anonymous user)', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  test('Sort recipes by title', async ({ page }) => {
    await page.goto('/browse-recipes', { waitUntil: 'networkidle' });

    // Check if the search section is present
    await expect(page.locator('.cooked-recipe-search')).toBeDefined();

    // Select "Oldest first" from the sort dropdown
    await page.selectOption('.cooked-sortby-select', 'date_asc');

    // Trigger form submission via JavaScript event
    await page.evaluate(() => {
      document.querySelector('.cooked-sortby-select').closest('form').dispatchEvent(new Event('submit'));
    });

    // Wait for navigation and URL change
    await page.waitForURL('**/browse-recipes/sort/date_asc');

    // Wait for the page to finish loading after the search
    await page.waitForLoadState('networkidle');

    // Check if the first recipe is Albanian Flatbread
    const firstRecipe = page.locator('.cooked-recipe').first();
    await expect(firstRecipe.locator('.cooked-recipe-name')).toHaveText('Albanian Flatbread (Pite në Tigan)');
    await expect(firstRecipe).toHaveAttribute('id', 'cooked-recipe-1866');

    // Select "Oldest first" from the sort dropdown
    await page.selectOption('.cooked-sortby-select', 'date_desc');

    // Trigger form submission via JavaScript event
    await page.evaluate(() => {
      document.querySelector('.cooked-sortby-select').closest('form').dispatchEvent(new Event('submit'));
    });

    // Wait for navigation and URL change
    await page.waitForURL('**/browse-recipes/sort/date_desc');

    // Wait for the page to finish loading after the search
    await page.waitForLoadState('networkidle');

    // Check if the first recipe is "Albanian Bread Stuffed with Cheese (Pogaça me Djathë)"
    const secondRecipe = page.locator('.cooked-recipe').first();
    await expect(secondRecipe.locator('.cooked-recipe-name')).toHaveText('Albanian Bread Stuffed with Cheese (Pogaça me Djathë)');
    await expect(secondRecipe).toHaveAttribute('id', 'cooked-recipe-3587');
  });

  test('Sort recipes by rating (desc)', async ({ page }) => {
    await page.goto('/browse-recipes', { waitUntil: 'networkidle' });

    // Check if the search section is present
    await expect(page.locator('.cooked-recipe-search')).toBeDefined();

    // Select "Rating" from the sort dropdown
    await page.selectOption('.cooked-sortby-select', 'rating_desc');

    // Trigger form submission via JavaScript event
    await page.evaluate(() => {
      document.querySelector('.cooked-sortby-select').closest('form').dispatchEvent(new Event('submit'));
    });

    // Wait for navigation and URL change
    await page.waitForURL('**/browse-recipes/sort/rating_desc');

    // Wait for the page to finish loading after the search
    await page.waitForLoadState('networkidle');

    // Check if the first recipe is "Albanian Bread Stuffed with Cheese (Pogaça me Djathë)"
    const firstRecipe = page.locator('.cooked-recipe').first();
    await expect(firstRecipe.locator('.cooked-recipe-name')).toHaveText('Albanian Soda Bread (Albanian Kulac)');
    await expect(firstRecipe).toHaveAttribute('id', 'cooked-recipe-1865');
  });

  test('Sort recipes by rating (asc)', async ({ page }) => {
    await page.goto('/browse-recipes', { waitUntil: 'networkidle' });

    // Check if the search section is present
    await expect(page.locator('.cooked-recipe-search')).toBeDefined();

    // Select "Rating" from the sort dropdown
    await page.selectOption('.cooked-sortby-select', 'rating_asc');

    // Trigger form submission via JavaScript event
    await page.evaluate(() => {
      document.querySelector('.cooked-sortby-select').closest('form').dispatchEvent(new Event('submit'));
    });

    // Wait for navigation and URL change
    await page.waitForURL('**/browse-recipes/sort/rating_asc');

    // Wait for the page to finish loading after the search
    await page.waitForLoadState('networkidle');

    // Check if the first recipe is "Albanian Bread Stuffed with Cheese (Pogaça me Djathë)"
    const firstRecipe = page.locator('.cooked-recipe').first();
    await expect(firstRecipe.locator('.cooked-recipe-name')).toHaveText('Albanian Soda Bread For Stuffing (Kulaç Për Përshesh)');
    await expect(firstRecipe).toHaveAttribute('id', 'cooked-recipe-1835');
  });
});
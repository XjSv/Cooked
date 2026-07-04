import { test, expect } from '@playwright/test';

test.describe('Filter Browse Recipes (anonymous user)', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  test('Filter recipes by category', async ({ page }) => {
    await page.goto('/browse-recipes', { waitUntil: 'networkidle' });

    // Check if the search section is present
    await expect(page.locator('.cooked-recipe-search')).toBeDefined();

    // Click cooked-browse-select
    await page.click('.cooked-browse-select');

    // Expect cooked-browse-select-block to be visible
    await expect(page.locator('.cooked-browse-select-block')).toBeVisible();

    // Click the "Bread" category
    await page.click('.cooked-tax-column:has-text("Categories") a[href*="/recipe-category/bread"]');

    // Wait for navigation and URL change
    await page.waitForURL('**/browse-recipes/recipe-category/bread');

    // Wait for the page to finish loading after the search
    await page.waitForLoadState('networkidle');

    // Check if the first recipe is "Albanian Bread Stuffed with Cheese (Pogaça me Djathë)"
    const firstRecipe = page.locator('.cooked-recipe').first();
    await expect(firstRecipe.locator('.cooked-recipe-name')).toHaveText('Albanian Bread Stuffed with Cheese (Pogaça me Djathë)');
    await expect(firstRecipe).toHaveAttribute('id', 'cooked-recipe-3587');
  });

  test('Filter recipes by cooking method', async ({ page }) => {
    await page.goto('/browse-recipes', { waitUntil: 'networkidle' });

    // Check if the search section is present
    await expect(page.locator('.cooked-recipe-search')).toBeDefined();

    // Click cooked-browse-select
    await page.click('.cooked-browse-select');

    // Expect cooked-browse-select-block to be visible
    await expect(page.locator('.cooked-browse-select-block')).toBeVisible();

    await page.click('.cooked-tax-column:has-text("Cooking Methods") a[href*="/cooking-method/boiling"]');

    // Wait for navigation and URL change
    await page.waitForURL('**/browse-recipes/cooking-method/boiling');

    // Wait for the page to finish loading after the search
    await page.waitForLoadState('networkidle');

    // Check if the first recipe is "Russian Salad (Sallatë Ruse)"
    const firstRecipe = page.locator('.cooked-recipe').first();
    await expect(firstRecipe.locator('.cooked-recipe-name')).toHaveText('Russian Salad (Sallatë Ruse)');
    await expect(firstRecipe).toHaveAttribute('id', 'cooked-recipe-3216');
  });

  test('Filter recipes by cuisine', async ({ page }) => {
    await page.goto('/browse-recipes', { waitUntil: 'networkidle' });

    // Check if the search section is present
    await expect(page.locator('.cooked-recipe-search')).toBeDefined();

    // Click cooked-browse-select
    await page.click('.cooked-browse-select');

    // Expect cooked-browse-select-block to be visible
    await expect(page.locator('.cooked-browse-select-block')).toBeVisible();

    await page.click('.cooked-tax-column:has-text("Cuisines") a[href*="/cuisine/greek"]');

    // Wait for navigation and URL change
    await page.waitForURL('**/browse-recipes/cuisine/greek');

    // Wait for the page to finish loading after the search
    await page.waitForLoadState('networkidle');

    // Check if the first recipe is "Greek Creamy Egg, Lemon & Chicken Soup (Supë Pule me Limon)"
    const firstRecipe = page.locator('.cooked-recipe').first();
    await expect(firstRecipe.locator('.cooked-recipe-name')).toHaveText('Greek Creamy Egg, Lemon & Chicken Soup (Supë Pule me Limon)');
    await expect(firstRecipe).toHaveAttribute('id', 'cooked-recipe-1832');
  });
});
import { test, expect } from '@playwright/test';
import { SEED_RECIPES } from '../../../utils/seed';

const browseSearch = '.cooked-recipe-search:not(.cooked-search-compact)';
const categoryCheckbox = (slug: string) =>
  `${browseSearch} .cooked-browse-select-block input[name="cooked_category_filter[]"][value="${slug}"]`;
const chip = (name: string) => `.cooked-filter-chips .cooked-filter-chip:has-text("${name}")`;

test.describe('Filter Browse Recipes (anonymous user)', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  test('Filter recipes by category', async ({ page }) => {
    await page.goto('/browse-recipes', { waitUntil: 'networkidle' });

    await expect(page.locator('.cooked-recipe-search')).toBeDefined();

    await page.click('.cooked-browse-select');
    await expect(page.locator('.cooked-browse-select-block')).toBeVisible();

    // Mark the document so a full page load would lose it.
    await page.evaluate(() => {
      (window as Window & { __cookedFilterPage?: Element }).__cookedFilterPage = document.body;
    });

    await page.check(categoryCheckbox('bread'));

    await expect(page).toHaveURL(/\/browse-recipes\/recipe-category\/bread\/sort\/date_desc\/?$/);
    await expect(page.locator(chip('Bread'))).toBeVisible();

    const titles = page.locator('.cooked-recipe .cooked-recipe-card-title');
    await expect(titles.filter({ hasText: SEED_RECIPES.bread.title })).toBeVisible();
    await expect(titles.filter({ hasText: SEED_RECIPES.beef.title })).toHaveCount(0);

    // The filter updated the grid in place instead of submitting the form.
    const sameDocument = await page.evaluate(
      () => (window as Window & { __cookedFilterPage?: Element }).__cookedFilterPage === document.body
    );
    expect(sameDocument).toBe(true);
  });

  test('Combine categories and remove chips', async ({ page }) => {
    await page.goto('/browse-recipes', { waitUntil: 'networkidle' });
    await page.click('.cooked-browse-select');

    await page.check(categoryCheckbox('bread'));
    await expect(page).toHaveURL(/\/browse-recipes\/recipe-category\/bread\/sort\/date_desc\/?$/);

    await page.check(categoryCheckbox('breakfast'));

    await expect(page).toHaveURL(/\/browse-recipes\/recipe-category\/bread,breakfast\/sort\/date_desc\/?$/);
    await expect(page.locator('.cooked-filter-chip')).toHaveCount(2);
    await expect(page.locator(chip('Bread'))).toBeVisible();
    await expect(page.locator(chip('Breakfast'))).toBeVisible();

    const titles = page.locator('.cooked-recipe .cooked-recipe-card-title');
    await expect(titles.filter({ hasText: SEED_RECIPES.bread.title })).toBeVisible();

    // The filter panel overlays the results; close it before reaching the chips.
    await page.keyboard.press('Escape');
    await page.locator(chip('Bread')).click();

    await expect(page).toHaveURL(/\/browse-recipes\/recipe-category\/breakfast\/sort\/date_desc\/?$/);
    await expect(page.locator('.cooked-filter-chip')).toHaveCount(1);
    await expect(page.locator(chip('Bread'))).toHaveCount(0);
    await expect(page.locator(categoryCheckbox('bread'))).not.toBeChecked();
    await expect(titles.filter({ hasText: SEED_RECIPES.bread.title })).toBeVisible();

    await page.click('.cooked-browse-select');
    await page.click(`${browseSearch} .cooked-filter-all`);

    await expect(page).toHaveURL(/\/browse-recipes\/sort\/date_desc\/?$/);
    await expect(page.locator('.cooked-filter-chip')).toHaveCount(0);
    await expect(page.locator(categoryCheckbox('breakfast'))).not.toBeChecked();
  });
});

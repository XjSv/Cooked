import { test } from '@playwright/test';
import { expectAccessible } from '../../utils/a11y';

const browseSearch = '.cooked-recipe-search:not(.cooked-search-compact)';

test.describe('Browse filters accessibility (anonymous user)', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  test('browse filter panel - no accessibility violations', async ({ page }) => {
    await page.goto('/browse-recipes', { waitUntil: 'networkidle' });
    await page.locator(browseSearch).waitFor();
    await page.locator(`${browseSearch} .cooked-browse-select`).click();
    await page.locator(`${browseSearch} .cooked-browse-select-block`).waitFor();
    await expectAccessible(page);
  });

  test('browse category bread - no accessibility violations', async ({ page }) => {
    await page.goto('/browse-recipes', { waitUntil: 'networkidle' });
    await page.locator(`${browseSearch}`).waitFor();
    await page.locator(`${browseSearch} .cooked-browse-select`).click();
    await page.locator(`${browseSearch} .cooked-browse-select-block`).waitFor();
    await page
      .locator(`${browseSearch} .cooked-browse-select-block input[name="cooked_category_filter[]"][value="bread"]`)
      .check();
    await page.waitForURL(/\/browse-recipes\/recipe-category\/bread\/sort\/date_desc\/?$/);
    await page.waitForLoadState('networkidle');
    await page.locator('.cooked-filter-chips .cooked-filter-chip:has-text("Bread")').waitFor();
    await page.locator('.cooked-recipe').first().waitFor();
    await expectAccessible(page);
  });
});

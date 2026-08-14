import { test } from '@playwright/test';
import { expectAccessible } from '../../utils/a11y';

const browseSearch = '.cooked-recipe-search:not(.cooked-search-compact)';

test.describe('Browse search accessibility (anonymous user)', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  test('browse search results - no accessibility violations', async ({ page }) => {
    await page.goto('/browse-recipes', { waitUntil: 'networkidle' });
    await page.locator(browseSearch).waitFor();
    await page.locator(`${browseSearch} .cooked-browse-search`).fill('beef');
    await page.evaluate((selector) => {
      document.querySelector(selector)?.closest('form')?.dispatchEvent(new Event('submit'));
    }, `${browseSearch} .cooked-browse-search`);
    await page.waitForURL('**/browse-recipes/search/beef/sort/date_desc');
    await page.waitForLoadState('networkidle');
    await page.locator('.cooked-recipe').first().waitFor();
    await expectAccessible(page);
  });
});

import { test } from '@playwright/test';
import { expectAccessible } from '../../utils/a11y';

const browseSearch = '.cooked-recipe-search:not(.cooked-search-compact)';

test.describe('Browse recipes accessibility (anonymous user)', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  test('browse recipes - no accessibility violations', async ({ page }) => {
    await page.goto('/browse-recipes', { waitUntil: 'networkidle' });
    await page.locator(browseSearch).waitFor();
    await expectAccessible(page);
  });
});

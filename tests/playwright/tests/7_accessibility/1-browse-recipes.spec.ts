import { test } from '@playwright/test';
import { expectAccessible } from '../../utils/a11y';

const main = '#basil-main .basil-main-template';

test.describe('Browse recipes accessibility (anonymous user)', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  test('browse recipes — no accessibility violations', async ({ page }) => {
    await page.goto('/browse-recipes', { waitUntil: 'networkidle' });
    await page.locator(`${main} .cooked-recipe-search`).waitFor();
    await expectAccessible(page);
  });
});

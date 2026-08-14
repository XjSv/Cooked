import { test } from '@playwright/test';
import { expectAccessible } from '../../utils/a11y';

const STABLE_RECIPE_ID = 3587;

test.describe('Recipe single accessibility (anonymous user)', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  test('recipe single - no accessibility violations', async ({ page }) => {
    await page.goto(`/?post_type=cp_recipe&p=${STABLE_RECIPE_ID}`, { waitUntil: 'networkidle' });
    await page.locator('.cooked-recipe-info, .cooked-recipe-ingredients').first().waitFor();
    await expectAccessible(page);
  });
});

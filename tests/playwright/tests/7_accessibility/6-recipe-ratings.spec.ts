import { test as base, expect } from '@playwright/test';
import { ensureValidAuth, getAuthPath } from '../../utils/auth';
import { expectAccessible } from '../../utils/a11y';

const STABLE_RECIPE_ID = 3587;
const main = '#basil-main .basil-main-template';

const test = base.extend({
  adminContext: async ({ browser }, use) => {
    const context = await browser.newContext({
      storageState: getAuthPath('mtresova'),
    });
    const page = await context.newPage();
    await ensureValidAuth(page, 'mtresova', 'password');
    await use(context);
    await page.close();
    await context.close();
  },
});

test.describe('Recipe ratings accessibility', () => {
  test('enable guest star ratings', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();
    await adminPage.goto('/wp-admin/admin.php?page=cooked_settings#engagement', { waitUntil: 'networkidle' });

    const checkbox = adminPage.locator('input[name="cooked_settings[enable_guest_ratings][]"]');
    if (!(await checkbox.isChecked())) {
      await adminPage.locator('input[name="cooked_settings[enable_guest_ratings][]"] ~ span.switchery').click();
    }

    const select = adminPage.locator('select[name="cooked_settings[rating_type]"]');
    const currentValue = await select.evaluate((el) => (el as HTMLSelectElement).value);
    if (currentValue !== 'stars') {
      await select.selectOption('stars');
    }

    await adminPage.getByRole('button', { name: 'Update Settings' }).click();
    await expect(adminPage.getByText('Cooked settings has been updated!')).toBeDefined();
  });

  test.describe('anonymous user', () => {
    test.use({ storageState: { cookies: [], origins: [] } });

    test('recipe ratings — no accessibility violations', async ({ page }) => {
      await page.goto(`/?post_type=cp_recipe&p=${STABLE_RECIPE_ID}`, { waitUntil: 'networkidle' });
      await page.locator(`${main} .cooked-recipe-info, ${main} .cooked-recipe-ingredients`).first().waitFor();
      await page.locator(`${main} .cooked-rating-stars`).first().waitFor();
      await expectAccessible(page);
    });
  });
});

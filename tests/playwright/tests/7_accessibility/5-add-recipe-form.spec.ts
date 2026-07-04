import { test as base } from '@playwright/test';
import { ensureValidAuth, getAuthPath } from '../../utils/auth';
import { expectAccessible } from '../../utils/a11y';

const main = '#basil-main .basil-main-template';

const test = base.extend({
  contribContext: async ({ browser }, use) => {
    const context = await browser.newContext({
      storageState: getAuthPath('testUserContributor'),
    });
    const page = await context.newPage();
    await ensureValidAuth(page, 'testUserContributor', 'password');
    await use(context);
    await page.close();
    await context.close();
  },
});

test.describe('Add recipe form accessibility (contributor user)', () => {
  test('add recipe form — no accessibility violations', async ({ contribContext }) => {
    const contribPage = await contribContext.newPage();
    await contribPage.goto('/profile/add/', { waitUntil: 'networkidle' });
    await contribPage.locator(`${main} input[name="_recipe_settings[post_title]"]`).waitFor();
    await expectAccessible(contribPage);
  });
});

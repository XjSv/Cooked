import { test } from '../../utils/fixtures';
import { expectAccessible } from '../../utils/a11y';
import { fillAndPublishAdminRecipe } from '../../utils/form';
import { deletePost } from '../../utils/wp-cli';

test.describe.configure({ mode: 'serial' });

let recipeId = '';

test.describe('Recipe single accessibility', () => {
  test('creates a recipe via the admin editor', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();
    recipeId = await fillAndPublishAdminRecipe(adminPage, 'E2E A11y Single ' + Date.now());
  });

  test.describe('anonymous user', () => {
    test.use({ storageState: { cookies: [], origins: [] } });

    test('recipe single - no accessibility violations', async ({ page }) => {
      if (!recipeId) {
        throw new Error('Recipe ID is not set');
      }

      await page.goto('/?post_type=cp_recipe&p=' + recipeId, { waitUntil: 'networkidle' });
      await page.locator('.cooked-recipe-info, .cooked-recipe-ingredients').first().waitFor();
      await expectAccessible(page);
    });
  });
});

test.afterAll(() => {
  deletePost(recipeId);
});

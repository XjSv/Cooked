import { test } from '../../utils/fixtures';
import { expectAccessible } from '../../utils/a11y';
import { fullRecipeId } from '../../utils/seed';
import { ADMIN_USER } from '../../utils/users';
import { wpCliArgs } from '../../utils/wp-cli';

// The recipe editor's new layout, on the seed's full recipe so every tab has content: the Cooked
// meta box (Layout tab) and the Allergens box, in light and dark mode, with the Difficulty select
// open and a help tooltip showing; the Ingredients, Directions, Nutrition and Gallery tabs; and
// the Shortcodes tab with every options table open. TinyMCE's
// toolbars are WordPress core markup (unnamed buttons nested in role="button" containers on
// every classic editor), so the scans skip them.

const TINYMCE_TOOLBARS = '.mce-toolbar-grp';

const seedRecipeId = fullRecipeId;

for (const theme of ['light', 'dark'] as const) {
  test.describe(`Recipe editor accessibility (${theme})`, () => {
    test.describe.configure({ mode: 'serial' });

    test.beforeAll(() => {
      if (theme === 'dark') {
        wpCliArgs(['user', 'meta', 'update', ADMIN_USER.user, 'cooked_editor_dark_mode', 'enabled']);
      }
    });

    test.afterAll(() => {
      if (theme === 'dark') {
        wpCliArgs(['user', 'meta', 'delete', ADMIN_USER.user, 'cooked_editor_dark_mode']);
      }
    });

    test('Layout tab and Allergens', async ({ adminContext }) => {
      const page = await adminContext.newPage();
      await page.goto(`/wp-admin/post.php?post=${seedRecipeId()}&action=edit`, { waitUntil: 'networkidle' });
      await page.locator('body.cooked-recipe-editor').waitFor();

      await test.step('fields', async () => {
        await expectAccessible(page, { include: ['#cooked_recipe_settings', '#cooked_allergens'], exclude: [TINYMCE_TOOLBARS] });
      });

      await test.step('difficulty select open', async () => {
        await page.locator('#cooked-difficulty-level + .ts-wrapper .ts-control').click();
        await page.locator('#cooked-difficulty-level + .ts-wrapper .ts-dropdown').waitFor();
        await expectAccessible(page, { include: ['#cooked_recipe_settings'], exclude: [TINYMCE_TOOLBARS] });
        await page.keyboard.press('Escape');
      });

      await test.step('help tooltip', async () => {
        await page.locator('.cooked-recipe-fields .cooked-tooltip-icon').first().focus();
        await page.locator('.cooked-tooltip-floating.cooked-tooltip-open').waitFor();
        // Scan once the grow-in animation has finished (it fades in from transparent).
        await page.waitForFunction(() => {
          const bubble = document.querySelector('.cooked-tooltip-floating .cooked-tooltip-bubble');
          return !!bubble && getComputedStyle(bubble).opacity === '1';
        });
        await expectAccessible(page, { include: ['.cooked-tooltip-floating'] });
      });
    });

    for (const tab of ['ingredients', 'directions', 'gallery'] as const) {
      test(`${tab[0].toUpperCase()}${tab.slice(1)} tab`, async ({ adminContext }) => {
        const page = await adminContext.newPage();
        await page.goto(`/wp-admin/post.php?post=${seedRecipeId()}&action=edit`, { waitUntil: 'networkidle' });
        await page.locator(`#cooked-recipe-tab-${tab}`).click();
        await page.locator(`#cooked-recipe-tab-content-${tab}`).waitFor();
        await page.waitForFunction(() => document.getAnimations().length === 0);
        await expectAccessible(page, { include: [`#cooked-recipe-tab-content-${tab}`], exclude: [TINYMCE_TOOLBARS] });
      });
    }

    test('Nutrition tab', async ({ adminContext }) => {
      const page = await adminContext.newPage();
      await page.goto(`/wp-admin/post.php?post=${seedRecipeId()}&action=edit`, { waitUntil: 'networkidle' });
      await page.locator('#cooked-recipe-tab-nutrition').click();
      await page.locator('#cooked-recipe-tab-content-nutrition .cooked-nutrition-layout').waitFor();

      // Some values, so the label's rows and % Daily Values show.
      await page.fill('#servings', '4');
      await page.fill('#calories', '320');
      await page.fill('#fat', '12');
      await page.fill('#sat_fat', '3');
      await expectAccessible(page, { include: ['#cooked-recipe-tab-content-nutrition'] });
    });

    test('Shortcodes tab', async ({ adminContext }) => {
      const page = await adminContext.newPage();
      await page.goto(`/wp-admin/post.php?post=${seedRecipeId()}&action=edit`, { waitUntil: 'networkidle' });
      await page.locator('#cooked-recipe-tab-shortcodes').click();
      await page.locator('#cooked-recipe-tab-content-shortcodes .cooked-shortcode-list').first().waitFor();

      for (const summary of await page.locator('.cooked-shortcode-options > summary').all()) {
        await summary.click();
      }
      // Scan once they have eased open (they fade in).
      await page.waitForFunction(() => document.getAnimations().length === 0);
      await expectAccessible(page, { include: ['#cooked-recipe-tab-content-shortcodes'] });
    });
  });
}

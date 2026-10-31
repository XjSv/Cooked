import { test, expect, type Page } from '@playwright/test';
import { deletePost, getCookedSettings, setCookedSettings, wpCliArgs } from '../../utils/wp-cli';

test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });

const stamp = Date.now();
const recipeTitle = `E2E Disable Public ${stamp}`;
const pageTitle = `E2E Cooked Recipe Page ${stamp}`;
const marker = `E2E-DISABLE-PUBLIC-${stamp}`;
const recipeSlug = `e2e-disable-public-${stamp}`;
const pageSlug = `e2e-cooked-recipe-page-${stamp}`;

let originalSettings: Record<string, unknown> = {};
let recipeId = 0;
let pageId = 0;
let recipePath = '';
let pagePath = '';

function advancedList(settings: Record<string, unknown>): string[] {
  const value = settings.advanced;
  return Array.isArray(value) ? value.map(String) : [];
}

function setPublicRecipesDisabled(disabled: boolean): void {
  const advanced = advancedList(originalSettings).filter((item) => item !== 'disable_public_recipes');
  if (disabled) {
    advanced.push('disable_public_recipes');
  }
  setCookedSettings({ ...originalSettings, advanced });
  wpCliArgs(['rewrite', 'flush', '--hard']);
}

function permalinkPath(id: number): string {
  const url = wpCliArgs(['post', 'get', String(id), '--field=url']);
  return new URL(url).pathname;
}

function createFixtures(): { recipeId: number; pageId: number } {
  const php = `
    $recipe_id = wp_insert_post(array(
      'post_type' => 'cp_recipe',
      'post_status' => 'publish',
      'post_title' => ${JSON.stringify(recipeTitle)},
      'post_name' => ${JSON.stringify(recipeSlug)},
    ), true);
    if (is_wp_error($recipe_id)) {
      WP_CLI::error($recipe_id->get_error_message());
    }
    update_post_meta($recipe_id, '_recipe_settings', array(
      'cooked_version' => '1.0.0',
      'content' => '<p>[cooked-title]</p><p>[cooked-ingredients]</p>',
      'excerpt' => ${JSON.stringify(marker)},
      'prep_time' => 5,
      'cook_time' => 10,
      'total_time' => 15,
      'difficulty_level' => '1',
      'nutrition' => array('servings' => 2),
      'ingredients' => array(
        array(
          'amount' => '1',
          'measurement' => '',
          'name' => ${JSON.stringify(marker)},
          'section_heading_name' => '',
        ),
      ),
      'directions' => array(),
    ));
    $page_id = wp_insert_post(array(
      'post_type' => 'page',
      'post_status' => 'publish',
      'post_title' => ${JSON.stringify(pageTitle)},
      'post_name' => ${JSON.stringify(pageSlug)},
      'post_content' => '[cooked-recipe id="' . intval($recipe_id) . '"]',
    ), true);
    if (is_wp_error($page_id)) {
      WP_CLI::error($page_id->get_error_message());
    }
    echo wp_json_encode(array(
      'recipeId' => (int) $recipe_id,
      'pageId' => (int) $page_id,
    ));
  `;
  return JSON.parse(wpCliArgs(['eval', php])) as { recipeId: number; pageId: number };
}

async function expectShortcodeRecipe(page: Page): Promise<void> {
  await page.goto(pagePath, { waitUntil: 'domcontentloaded' });
  const embed = page.locator(`#cooked-recipe-embed-${recipeId}`);
  await expect(embed).toBeVisible();
  await expect(embed).toContainText(recipeTitle);
  await expect(embed.locator('.cooked-recipe-ingredients').first()).toContainText(marker);
}

test.beforeAll(() => {
  originalSettings = getCookedSettings();
  setPublicRecipesDisabled(false);

  const created = createFixtures();
  recipeId = created.recipeId;
  pageId = created.pageId;
  recipePath = permalinkPath(recipeId);
  pagePath = permalinkPath(pageId);
});

test.afterAll(() => {
  deletePost(recipeId);
  deletePost(pageId);
  if (Object.keys(originalSettings).length) {
    setCookedSettings(originalSettings);
    wpCliArgs(['rewrite', 'flush', '--hard']);
  }
});

test.describe('Disable Public Recipes', () => {
  test('shows the recipe on its permalink and on a [cooked-recipe] page when the setting is off', async ({ page }) => {
    setPublicRecipesDisabled(false);

    const response = await page.goto(recipePath, { waitUntil: 'domcontentloaded' });
    expect(response?.status()).toBe(200);
    await expect(page.locator('h1.entry-title')).toHaveText(recipeTitle);
    await expect(page.locator('.entry-content .cooked-recipe-ingredients').first()).toContainText(marker);

    await expectShortcodeRecipe(page);
  });

  test('hides the recipe permalink and still shows it on a [cooked-recipe] page when the setting is on', async ({ page }) => {
    setPublicRecipesDisabled(true);

    const permalink = await page.request.get(recipePath, { maxRedirects: 0 });
    expect(permalink.status()).toBe(301);
    expect(new URL(permalink.headers().location || '').pathname).toBe('/');

    const byId = await page.request.get(`/?post_type=cp_recipe&p=${recipeId}`, { maxRedirects: 0 });
    expect(byId.status()).toBe(404);
    expect(await byId.text()).not.toContain(marker);

    await expectShortcodeRecipe(page);
  });
});

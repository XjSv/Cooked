import { test, expect, type Page } from '@playwright/test';
import { ensureValidAuth } from '../../utils/auth';
import { ADMIN_USER } from '../../utils/users';
import { deletePost, getCookedSettings, setCookedSettings, wpCliArgs } from '../../utils/wp-cli';

test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });

const stamp = Date.now();
const recipeTitle = `E2E Disable Public ${stamp}`;
const pageTitle = `E2E Cooked Recipe Page ${stamp}`;
const plainTitle = `E2E Plain Page ${stamp}`;
const marker = `E2E-DISABLE-PUBLIC-${stamp}`;
const recipeSlug = `e2e-disable-public-${stamp}`;
const pageSlug = `e2e-cooked-recipe-page-${stamp}`;
const plainSlug = `e2e-plain-page-${stamp}`;

let originalSettings: Record<string, unknown> = {};
let recipeId = 0;
let pageId = 0;
let plainPageId = 0;
let recipePath = '';
let pagePath = '';
let plainPagePath = '';

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

function createFixtures(): { recipeId: number; pageId: number; plainPageId: number } {
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
      'content' => '<p>[cooked-title]</p><p>[cooked-info right="print"]</p><p>[cooked-ingredients]</p>',
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
    $plain_id = wp_insert_post(array(
      'post_type' => 'page',
      'post_status' => 'publish',
      'post_title' => ${JSON.stringify(plainTitle)},
      'post_name' => ${JSON.stringify(plainSlug)},
      'post_content' => '<p>No recipe here.</p>',
    ), true);
    if (is_wp_error($plain_id)) {
      WP_CLI::error($plain_id->get_error_message());
    }
    echo wp_json_encode(array(
      'recipeId' => (int) $recipe_id,
      'pageId' => (int) $page_id,
      'plainPageId' => (int) $plain_id,
    ));
  `;
  return JSON.parse(wpCliArgs(['eval', php])) as { recipeId: number; pageId: number; plainPageId: number };
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
  plainPageId = created.plainPageId;
  recipePath = permalinkPath(recipeId);
  pagePath = permalinkPath(pageId);
  plainPagePath = permalinkPath(plainPageId);
});

test.afterAll(() => {
  deletePost(recipeId);
  deletePost(pageId);
  deletePost(plainPageId);
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

  test('hides the print URL and anonymous REST when the setting is on', async ({ page }) => {
    setPublicRecipesDisabled(true);

    const printed = await page.request.get(`${recipePath}?print=1`, { maxRedirects: 0 });
    expect(printed.status()).toBe(301);
    expect(new URL(printed.headers().location || '').pathname).toBe('/');
    expect(await printed.text()).not.toContain(marker);

    const rest = await page.request.get(
      `/wp-json/wp/v2/cooked_recipe?search=${encodeURIComponent(recipeTitle)}`,
    );
    expect(rest.status()).toBe(401);
    expect(await rest.text()).not.toContain(recipeTitle);
  });

  test('lets a recipe editor read recipes over REST when the setting is on', async ({ browser }) => {
    setPublicRecipesDisabled(true);

    const context = await browser.newContext();
    const page = await context.newPage();
    await ensureValidAuth(page, ADMIN_USER.user, ADMIN_USER.password);
    const nonce = await page.evaluate(() => {
      const settings = (window as { wpApiSettings?: { nonce?: string } }).wpApiSettings;
      return settings?.nonce || '';
    });
    expect(nonce).not.toBe('');

    const rest = await context.request.get(
      `/wp-json/wp/v2/cooked_recipe?search=${encodeURIComponent(recipeTitle)}`,
      { headers: { 'X-WP-Nonce': nonce } },
    );
    expect(rest.status()).toBe(200);
    expect(rest.headers()['content-type'] || '').toContain('json');
    expect(await rest.text()).toContain(recipeTitle);
    await context.close();
  });

  test('prints an embedded recipe from the current page when the setting is on', async ({ page }) => {
    setPublicRecipesDisabled(true);

    await page.goto(pagePath, { waitUntil: 'domcontentloaded' });
    const href = await page.locator('.cooked-print-icon').getAttribute('href');
    const printUrl = new URL(href || '', page.url());
    expect(printUrl.searchParams.get('cooked_print')).toBe(String(recipeId));
    expect(printUrl.pathname).not.toContain(recipeSlug);

    await page.goto(printUrl.toString(), { waitUntil: 'domcontentloaded' });
    await expect(page.locator('body')).toContainText(marker);
    await expect(page.locator('meta[name="robots"][content*="noindex"]')).not.toHaveCount(0);
    await expect(page.locator('#cooked-print-options')).toBeVisible();

    printUrl.searchParams.set('servings', '4');
    await page.goto(printUrl.toString(), { waitUntil: 'domcontentloaded' });
    await expect(page.locator('.cooked-ing-amount').first()).toHaveAttribute('data-decimal', '2');
  });

  test('does not print a recipe from a page that does not embed it', async ({ page }) => {
    setPublicRecipesDisabled(true);

    const response = await page.request.get(`${plainPagePath}?cooked_print=${recipeId}`);
    expect(response.status()).toBe(200);
    expect(await response.text()).not.toContain(marker);
  });
});

import { test, expect, type Page } from '@playwright/test';
import { ensureValidAuth } from '../../utils/auth';
import { CONTRIB_USER } from '../../utils/users';
import { deletePost, getCookedSettings, setCookedSettings, wpCliArgs } from '../../utils/wp-cli';

test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });

const stamp = Date.now();
const protectedTitle = `E2E Print Password ${stamp}`;
const publicTitle = `E2E Print Public ${stamp}`;
const pageTitle = `E2E Print Password Page ${stamp}`;
const recipePassword = `PrintTest9-${stamp}`;
const excerptMarker = `E2E-PRINT-EXCERPT-${stamp}`;
const seoMarker = `E2E-PRINT-SEO-${stamp}`;
const ingredientMarker = `E2E-PRINT-INGREDIENT-${stamp}`;
const notesMarker = `E2E-PRINT-NOTES-${stamp}`;
const nutritionMarker = `424242${String(stamp).slice(-4)}`;
const publicExcerptMarker = `E2E-PRINT-PUBLIC-EXCERPT-${stamp}`;
const draftTitle = `E2E Print Password Draft ${stamp}`;
const protectedSlug = `e2e-print-password-${stamp}`;
const publicSlug = `e2e-print-public-${stamp}`;
const pageSlug = `e2e-print-password-page-${stamp}`;

let originalSettings: Record<string, unknown> = {};
let testSettings: Record<string, unknown> = {};
let protectedId = 0;
let publicId = 0;
let pageId = 0;
let draftId = 0;
let protectedPath = '';
let publicPath = '';
let pagePath = '';

function advancedList(settings: Record<string, unknown>): string[] {
  const value = settings.advanced;
  return Array.isArray(value) ? value.map(String) : [];
}

function displayOptions(settings: Record<string, unknown>): string[] {
  const value = settings.recipe_info_display_options;
  return Array.isArray(value) ? value.map(String) : [];
}

function setPublicRecipesDisabled(disabled: boolean): void {
  const advanced = advancedList(testSettings).filter((item) => item !== 'disable_public_recipes');
  if (disabled) {
    advanced.push('disable_public_recipes');
  }
  setCookedSettings({ ...testSettings, advanced });
  wpCliArgs(['rewrite', 'flush', '--hard']);
}

function permalinkPath(id: number): string {
  const url = wpCliArgs(['post', 'get', String(id), '--field=url']);
  return new URL(url).pathname;
}

function createFixtures(): { protectedId: number; publicId: number; pageId: number; draftId: number } {
  const php = `
    $protected_id = wp_insert_post(array(
      'post_type' => 'cp_recipe',
      'post_status' => 'publish',
      'post_title' => ${JSON.stringify(protectedTitle)},
      'post_name' => ${JSON.stringify(protectedSlug)},
      'post_password' => ${JSON.stringify(recipePassword)},
    ), true);
    if (is_wp_error($protected_id)) {
      WP_CLI::error($protected_id->get_error_message());
    }
    update_post_meta($protected_id, '_recipe_settings', array(
      'cooked_version' => '1.0.0',
      'content' => '<p>[cooked-title]</p><p>[cooked-excerpt]</p><p>[cooked-ingredients]</p><p>[cooked-notes show_header=true]</p>',
      'excerpt' => ${JSON.stringify(excerptMarker)},
      'seo_description' => ${JSON.stringify(seoMarker)},
      'notes' => ${JSON.stringify(notesMarker)},
      'prep_time' => 5,
      'cook_time' => 10,
      'total_time' => 15,
      'nutrition' => array(
        'servings' => 2,
        'calories' => ${JSON.stringify(nutritionMarker)},
      ),
      'ingredients' => array(
        array(
          'amount' => '1',
          'measurement' => '',
          'name' => ${JSON.stringify(ingredientMarker)},
          'section_heading_name' => '',
        ),
      ),
      'directions' => array(),
    ));
    $public_id = wp_insert_post(array(
      'post_type' => 'cp_recipe',
      'post_status' => 'publish',
      'post_title' => ${JSON.stringify(publicTitle)},
      'post_name' => ${JSON.stringify(publicSlug)},
    ), true);
    if (is_wp_error($public_id)) {
      WP_CLI::error($public_id->get_error_message());
    }
    update_post_meta($public_id, '_recipe_settings', array(
      'cooked_version' => '1.0.0',
      'content' => '<p>[cooked-title]</p><p>[cooked-info right="print"]</p><p>[cooked-excerpt]</p>',
      'excerpt' => ${JSON.stringify(publicExcerptMarker)},
      'prep_time' => 5,
      'cook_time' => 10,
      'total_time' => 15,
      'nutrition' => array('servings' => 2),
      'ingredients' => array(),
      'directions' => array(),
    ));
    $page_id = wp_insert_post(array(
      'post_type' => 'page',
      'post_status' => 'publish',
      'post_title' => ${JSON.stringify(pageTitle)},
      'post_name' => ${JSON.stringify(pageSlug)},
      'post_content' => '[cooked-recipe id="' . intval($protected_id) . '"][cooked-recipe id="' . intval($public_id) . '"]',
    ), true);
    if (is_wp_error($page_id)) {
      WP_CLI::error($page_id->get_error_message());
    }
    $contributor = get_user_by('login', ${JSON.stringify(CONTRIB_USER.user)});
    if ( ! $contributor ) {
      WP_CLI::error('Contributor user is missing.');
    }
    $draft_id = wp_insert_post(array(
      'post_type' => 'post',
      'post_status' => 'draft',
      'post_author' => (int) $contributor->ID,
      'post_title' => ${JSON.stringify(draftTitle)},
      'post_content' => '[cooked-recipe id="' . intval($protected_id) . '"][cooked-nutrition id="' . intval($protected_id) . '"]',
    ), true);
    if (is_wp_error($draft_id)) {
      WP_CLI::error($draft_id->get_error_message());
    }
    echo wp_json_encode(array(
      'protectedId' => (int) $protected_id,
      'publicId' => (int) $public_id,
      'pageId' => (int) $page_id,
      'draftId' => (int) $draft_id,
    ));
  `;
  return JSON.parse(wpCliArgs(['eval', php])) as {
    protectedId: number;
    publicId: number;
    pageId: number;
    draftId: number;
  };
}

async function metaContents(page: Page, selector: string): Promise<string[]> {
  return page.locator(selector).evaluateAll((els) => els.map((el) => el.getAttribute('content') || ''));
}

test.beforeAll(() => {
  originalSettings = getCookedSettings();
  const options = displayOptions(originalSettings);
  if (!options.includes('notes')) {
    options.push('notes');
  }
  const advanced = advancedList(originalSettings).filter((item) => item !== 'disable_public_recipes');
  testSettings = {
    ...originalSettings,
    recipe_info_display_options: options,
    advanced,
  };
  setCookedSettings(testSettings);
  wpCliArgs(['rewrite', 'flush', '--hard']);

  const created = createFixtures();
  protectedId = created.protectedId;
  publicId = created.publicId;
  pageId = created.pageId;
  draftId = created.draftId;
  protectedPath = permalinkPath(protectedId);
  publicPath = permalinkPath(publicId);
  pagePath = permalinkPath(pageId);
});

test.afterAll(() => {
  deletePost(protectedId);
  deletePost(publicId);
  deletePost(pageId);
  deletePost(draftId);
  if (Object.keys(originalSettings).length) {
    setCookedSettings(originalSettings);
    wpCliArgs(['rewrite', 'flush', '--hard']);
  }
});

test.describe('Password-protected print view', () => {
  test('keeps a logged-out print URL on the password form', async ({ page }) => {
    const response = await page.goto(`${protectedPath}?print=1`, { waitUntil: 'domcontentloaded' });
    expect(response?.status()).toBe(200);
    await expect(page.locator('form.post-password-form')).toBeVisible();
    await expect(page.locator('#printTitle')).toHaveCount(0);

    const bodyText = await page.locator('body').innerText();
    expect(bodyText).not.toContain(excerptMarker);
    expect(bodyText).not.toContain(seoMarker);
    expect(bodyText).not.toContain(ingredientMarker);
    expect(bodyText).not.toContain(notesMarker);

    const descriptions = await metaContents(page, 'meta[name="description"]');
    const ogDescriptions = await metaContents(page, 'meta[property="og:description"]');
    const descriptionText = [...descriptions, ...ogDescriptions].join('\n');
    expect(descriptionText).toContain(protectedTitle);
    expect(descriptionText).not.toContain(excerptMarker);
    expect(descriptionText).not.toContain(seoMarker);
    await expect(page.locator('meta[property="og:title"]').first()).toHaveAttribute('content', protectedTitle);
    await expect(page.locator('meta[property="og:url"]').first()).toHaveAttribute('content', new RegExp(protectedSlug));
  });

  test('shows a password form instead of an embedded protected recipe', async ({ page }) => {
    await page.goto(pagePath, { waitUntil: 'domcontentloaded' });

    const protectedEmbed = page.locator(`#cooked-recipe-embed-${protectedId}`);
    await expect(protectedEmbed.locator('form.post-password-form')).toBeVisible();
    await expect(protectedEmbed).not.toContainText(ingredientMarker);

    const publicEmbed = page.locator(`#cooked-recipe-embed-${publicId}`);
    await expect(publicEmbed).toContainText(publicExcerptMarker);
    await expect(publicEmbed.locator('form.post-password-form')).toHaveCount(0);
  });

  test('hides a protected recipe on a contributor preview until the password is entered', async ({ browser }) => {
    const context = await browser.newContext();
    const page = await context.newPage();
    const previewPath = `/?p=${draftId}&preview=true`;

    try {
      await ensureValidAuth(page, CONTRIB_USER.user, CONTRIB_USER.password);
      await page.goto(previewPath, { waitUntil: 'domcontentloaded' });
      await expect(page.locator('form.post-password-form').first()).toBeVisible();

      const lockedText = await page.locator('body').innerText();
      expect(lockedText).not.toContain(ingredientMarker);
      expect(lockedText).not.toContain(nutritionMarker);

      await page.locator('form.post-password-form input[name="post_password"]').first().fill(recipePassword);
      await page.locator('form.post-password-form input[type="submit"]').first().click();
      await page.waitForLoadState('domcontentloaded');
      await page.goto(previewPath, { waitUntil: 'domcontentloaded' });

      await expect(page.locator(`#cooked-recipe-embed-${protectedId}`)).toContainText(ingredientMarker);
      await expect(page.locator('.cooked-nutrition-label')).toContainText(nutritionMarker);
    } finally {
      await context.close();
    }
  });

  test('prints after the password is entered', async ({ page }) => {
    await page.goto(protectedPath, { waitUntil: 'domcontentloaded' });
    await page.locator('form.post-password-form input[name="post_password"]').fill(recipePassword);
    await page.locator('form.post-password-form input[type="submit"]').click();
    await page.waitForLoadState('domcontentloaded');

    await page.goto(`${protectedPath}?print=1`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#printTitle')).toHaveText(`Protected: ${protectedTitle}`);
    await expect(page.locator('.cooked-recipe-notes')).toContainText(notesMarker);
    await expect(page.locator('.cooked-recipe-ingredients')).toContainText(ingredientMarker);
    await expect(page.locator('#cooked-print-options')).toBeVisible();
  });

  test('still describes a public recipe with its excerpt', async ({ page }) => {
    await page.goto(publicPath, { waitUntil: 'domcontentloaded' });
    const descriptions = await metaContents(page, 'meta[name="description"]');
    const ogDescriptions = await metaContents(page, 'meta[property="og:description"]');
    expect(descriptions.join('\n')).toContain(publicExcerptMarker);
    expect(ogDescriptions.join('\n')).toContain(publicExcerptMarker);
  });

  test('does not print a password-protected embed, and still prints a public embed', async ({ page }) => {
    setPublicRecipesDisabled(true);

    try {
      const blocked = await page.goto(`${pagePath}?cooked_print=${protectedId}`, {
        waitUntil: 'domcontentloaded',
      });
      expect(blocked?.status()).toBe(200);
      await expect(page.locator('#printTitle')).toHaveCount(0);
      await expect(page.locator('#cooked-print-options')).toHaveCount(0);

      await page.goto(pagePath, { waitUntil: 'domcontentloaded' });
      const href = await page.locator(`#cooked-recipe-embed-${publicId} .cooked-print-icon`).getAttribute('href');
      const printUrl = new URL(href || '', page.url());
      expect(printUrl.searchParams.get('cooked_print')).toBe(String(publicId));

      await page.goto(printUrl.toString(), { waitUntil: 'domcontentloaded' });
      await expect(page.locator('#printTitle')).toHaveText(publicTitle);
      await expect(page.locator('body')).toContainText(publicExcerptMarker);
      await expect(page.locator('#cooked-print-options')).toBeVisible();
    } finally {
      setPublicRecipesDisabled(false);
    }
  });
});

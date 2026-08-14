import { type Page } from '@playwright/test';
import { test, expect } from '../../utils/fixtures';
import { fillAndPublishAdminRecipe } from '../../utils/form';
import { deletePost, getCookedSettings, setCookedSettings } from '../../utils/wp-cli';

test.describe.configure({ mode: 'serial' });

let originalSettings: Record<string, unknown> = {};
let recipeId = '';
let title = '';

const allergenCheckbox = (page: Page, key: string) =>
  page.locator(`input[name="_recipe_settings[allergens][]"][value="${key}"]`);

async function openAllergensMetabox(page: Page): Promise<void> {
  const metabox = page.locator('#cooked_allergens');
  await expect(metabox).toBeVisible();
  const isClosed = await metabox.evaluate((el) => el.classList.contains('closed'));
  if (isClosed) {
    await metabox.locator('.postbox-header, .handlediv, .hndle').first().click();
  }
}

async function ensureAllergensShortcode(page: Page): Promise<void> {
  await page.evaluate(() => {
    const editor = window.tinyMCE?.get('_recipe_settings_content');
    if (editor) {
      const content = (editor as unknown as { getContent: () => string }).getContent();
      if (!content.includes('allergens')) {
        editor.setContent(content + '<p>[cooked-info left="allergens"]</p>');
      }
      return;
    }

    const textarea = document.querySelector('#_recipe_settings_content') as HTMLTextAreaElement | null;
    if (textarea && !textarea.value.includes('allergens')) {
      textarea.value += '<p>[cooked-info left="allergens"]</p>';
    }
  });
}

async function gotoAllergenSettings(page: Page): Promise<void> {
  await page.goto('/wp-admin/admin.php?page=cooked_settings#recipe_settings', {
    waitUntil: 'networkidle',
  });
  await page.locator('#cooked-settings-tab-recipe_settings a').click();
  await expect(page.locator('#cooked-settings-tab-content-recipe_settings')).toBeVisible();
}

async function saveCookedSettings(page: Page): Promise<void> {
  await page.getByRole('button', { name: 'Update Settings' }).first().click();
  await expect(page.getByText('Cooked settings has been updated!')).toBeVisible();
}

async function searchBrowseRecipes(page: Page, query: string): Promise<void> {
  await page.goto('/browse-recipes', { waitUntil: 'networkidle' });
  await page.fill('.cooked-recipe-search:not(.cooked-search-compact) .cooked-browse-search', query);
  await page.evaluate(() => {
    document
      .querySelector('.cooked-recipe-search:not(.cooked-search-compact) .cooked-browse-search')
      ?.closest('form')
      ?.dispatchEvent(new Event('submit'));
  });
  await page.waitForURL(/\/browse-recipes\/search\//);
  await page.waitForLoadState('networkidle');
}

function recipeCard(page: Page) {
  return page.locator(`#cooked-recipe-${recipeId}`).or(
    page.locator('.cooked-recipe-card, article.cooked-recipe').filter({ hasText: title })
  );
}

test.beforeAll(() => {
  originalSettings = getCookedSettings();
});

test.describe('Allergen settings', () => {
  test('shows Recipe List Allergens and persists the checkbox', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();
    await gotoAllergenSettings(adminPage);

    await expect(adminPage.getByRole('heading', { name: 'Recipe List Allergens' })).toBeVisible();

    const allergenSetting = adminPage.locator('.recipe-setting-block', {
      has: adminPage.getByRole('heading', { name: 'Recipe List Allergens' }),
    });
    const checkbox = adminPage.locator('#checkbox-group-recipe_list_allergens-enabled');
    const toggle = allergenSetting.locator('.switchery');

    await expect(toggle).toBeVisible();
    await expect(adminPage.locator('label[for="checkbox-group-recipe_list_allergens-enabled"]')).toHaveText(
      'Show Allergens on Recipe Cards'
    );

    if (!(await checkbox.isChecked())) {
      await toggle.click();
    }
    await expect(checkbox).toBeChecked();
    await saveCookedSettings(adminPage);

    await gotoAllergenSettings(adminPage);
    await expect(adminPage.locator('#checkbox-group-recipe_list_allergens-enabled')).toBeChecked();

    await adminPage
      .locator('.recipe-setting-block', {
        has: adminPage.getByRole('heading', { name: 'Recipe List Allergens' }),
      })
      .locator('.switchery')
      .click();
    await expect(adminPage.locator('#checkbox-group-recipe_list_allergens-enabled')).not.toBeChecked();
    await saveCookedSettings(adminPage);

    await gotoAllergenSettings(adminPage);
    await expect(adminPage.locator('#checkbox-group-recipe_list_allergens-enabled')).not.toBeChecked();
  });
});

test.describe('Admin recipe allergens', () => {
  test('creates a recipe and saves peanuts and milk', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();
    title = 'E2E Allergens ' + Date.now();
    recipeId = await fillAndPublishAdminRecipe(adminPage, title);

    await openAllergensMetabox(adminPage);
    await allergenCheckbox(adminPage, 'peanuts').check();
    await allergenCheckbox(adminPage, 'milk').check();
    await ensureAllergensShortcode(adminPage);

    await adminPage.getByRole('button', { name: 'Update', exact: true }).click();
    await expect(adminPage.locator('.notice.notice-success')).toContainText('Post updated.', {
      timeout: 10000,
    });
  });

  test('persists selected allergens on the edit screen', async ({ adminContext }) => {
    if (!recipeId) {
      throw new Error('Recipe ID is not set');
    }

    const adminPage = await adminContext.newPage();
    await adminPage.goto(`/wp-admin/post.php?post=${recipeId}&action=edit`, {
      waitUntil: 'networkidle',
    });

    await openAllergensMetabox(adminPage);
    await expect(allergenCheckbox(adminPage, 'peanuts')).toBeChecked();
    await expect(allergenCheckbox(adminPage, 'milk')).toBeChecked();
    await expect(allergenCheckbox(adminPage, 'eggs')).not.toBeChecked();
  });
});

test.describe('Frontend allergens (anonymous)', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  test('shows allergen icons on the single recipe', async ({ page }) => {
    if (!recipeId) {
      throw new Error('Recipe ID is not set');
    }

    await page.goto('/?post_type=cp_recipe&p=' + recipeId, { waitUntil: 'networkidle' });
    await expect(page.locator('h1.entry-title')).toHaveText(title);

    const info = page.locator('.cooked-allergens-info');
    await expect(info).toBeVisible();
    await expect(info.locator('.cooked-meta-title')).toHaveText('Allergens');
    await expect(info.locator('.cooked-allergen-peanuts')).toBeVisible();
    await expect(info.locator('.cooked-allergen-peanuts')).toHaveAttribute('title', 'Contains Peanuts');
    await expect(info.locator('.cooked-allergen-milk')).toBeVisible();
    await expect(info.locator('.cooked-allergen-milk')).toHaveAttribute('title', 'Contains Milk');
    await expect(info.locator('.cooked-allergen-eggs')).toHaveCount(0);
  });

  test('hides allergen icons on browse cards when the setting is off', async ({ page }) => {
    if (!recipeId) {
      throw new Error('Recipe ID is not set');
    }

    setCookedSettings({
      ...originalSettings,
      recipe_list_allergens: [],
    });

    await searchBrowseRecipes(page, title);
    const card = recipeCard(page);
    await expect(card).toBeVisible();
    await expect(card.locator('.cooked-allergens')).toHaveCount(0);
  });

  test('shows allergen icons on browse cards when the setting is on', async ({ page }) => {
    if (!recipeId) {
      throw new Error('Recipe ID is not set');
    }

    setCookedSettings({
      ...originalSettings,
      recipe_list_allergens: ['enabled'],
    });

    await searchBrowseRecipes(page, title);
    const card = recipeCard(page);
    await expect(card).toBeVisible();
    await expect(card.locator('.cooked-allergen-peanuts')).toBeVisible();
    await expect(card.locator('.cooked-allergen-milk')).toBeVisible();
    await expect(card.locator('.cooked-allergen-eggs')).toHaveCount(0);
  });
});

test.afterAll(() => {
  deletePost(recipeId);
  setCookedSettings(originalSettings);
});

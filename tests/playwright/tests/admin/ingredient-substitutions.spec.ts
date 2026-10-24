import { test, expect } from '../../utils/fixtures';
import { Page } from '@playwright/test';
import { deletePost, wpCliArgs } from '../../utils/wp-cli';

// An ingredient with more than one substitute: added in the editor, shown on the recipe, kept
// when reopened, and the first one mirrored into the pre-2.0 sub_* fields add-ons read.

let postId = 0;
let permalink = '';

test.describe.configure({ mode: 'serial' });

test.afterAll(() => {
  if (postId) {
    deletePost(postId);
  }
});

// The recipe has one ingredient: the first row.
function butterRow(page: Page) {
  return page.locator('#cooked-ingredients-builder .cooked-ingredient-block:not(.cooked-ingredient-heading)').first();
}

async function saveRecipe(page: Page, button: 'Publish' | 'Update') {
  await Promise.all([
    page.waitForURL(/post\.php\?post=\d+&action=edit/),
    page.getByRole('button', { name: button, exact: true }).click(),
  ]);
  await expect(page.locator('.notice.notice-success').first()).toBeVisible();
}

test.describe('Ingredient substitutions (admin)', () => {
  test('adds two substitutes to one ingredient', async ({ adminContext }) => {
    const page = await adminContext.newPage();
    await page.goto('/wp-admin/post-new.php?post_type=cp_recipe', { waitUntil: 'networkidle' });
    await page.getByLabel('Recipe title ...').fill('Substitutions Playwright: ' + Date.now());
    await page.click('#cooked-recipe-tab-ingredients');

    const row = butterRow(page);
    await row.locator('input[data-ingredient-part="amount"]').fill('1');
    await row.locator('select[data-ingredient-part="measurement"]').selectOption('cup');
    await row.locator('input[data-ingredient-part="name"]').fill('butter');

    // The row's substitute button adds the first; "Add substitute" adds another.
    await row.locator('.cooked-show-substitution').click();
    const subs = row.locator('.cooked-substitution-row');
    await expect(subs).toHaveCount(1);
    await subs.nth(0).locator('input[data-substitution-part="amount"]').fill('1');
    await subs.nth(0).locator('input[data-substitution-part="name"]').fill('margarine');

    await row.locator('.cooked-add-substitution').click();
    await expect(subs).toHaveCount(2);
    await subs.nth(1).locator('input[data-substitution-part="name"]').fill('coconut oil');

    await saveRecipe(page, 'Publish');
    postId = parseInt(page.url().match(/post=(\d+)/)![1], 10);
    permalink = wpCliArgs(['post', 'list', '--post_type=cp_recipe', `--post__in=${postId}`, '--field=url']).split('\n').pop()!.trim();
  });

  test('shows both substitutes on the recipe', async ({ adminContext }) => {
    const page = await adminContext.newPage();
    await page.goto(permalink);
    const butter = page.locator('.cooked-single-ingredient').filter({ hasText: 'butter' }).first();
    await expect(butter.locator('.cooked-ingredient-substitution')).toHaveCount(2);
    await expect(butter.locator('.cooked-ingredient-substitution').nth(0)).toContainText('margarine');
    await expect(butter.locator('.cooked-ingredient-substitution').nth(1)).toContainText('coconut oil');
  });

  test('keeps them when reopened, and removes one', async ({ adminContext }) => {
    const page = await adminContext.newPage();
    await page.goto(`/wp-admin/post.php?post=${postId}&action=edit`, { waitUntil: 'networkidle' });
    await page.click('#cooked-recipe-tab-ingredients');

    const subs = butterRow(page).locator('.cooked-substitution-row');
    await expect(subs).toHaveCount(2);
    await expect(subs.nth(0).locator('input[data-substitution-part="name"]')).toHaveValue('margarine');
    await expect(subs.nth(1).locator('input[data-substitution-part="name"]')).toHaveValue('coconut oil');

    await subs.nth(0).locator('.cooked-delete-substitution').click();
    await expect(subs).toHaveCount(1);
    await saveRecipe(page, 'Update');

    const settings = JSON.parse(wpCliArgs(['post', 'meta', 'get', String(postId), '_recipe_settings', '--format=json']));
    const ingredient = Object.values(settings.ingredients as Record<string, Record<string, unknown>>).find((ing) => ing.name === 'butter')!;
    expect((ingredient.substitutions as unknown[]).length).toBe(1);
    expect(ingredient.sub_name).toBe('coconut oil');
  });
});

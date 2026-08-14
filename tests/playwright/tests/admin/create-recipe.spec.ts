import { test, expect } from '../../utils/fixtures';
import { deletePost } from '../../utils/wp-cli';

var postId: number;
var title: string;

declare global {
  interface Window {
    tinyMCE: {
      get: (id: string) => {
        setContent: (content: string) => void;
      };
      editors: { [key: string]: any };
    };
  }
}

test.describe.configure({ mode: 'serial' });

test.describe('Create a new complete recipe (admin)', () => {
  test('Create a new recipe (admin)', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();
    await adminPage.goto('/wp-admin/post-new.php?post_type=cp_recipe', { waitUntil: 'networkidle' });
    title = 'Test Recipe Playwright: ' + Date.now();

    await adminPage.getByLabel('Recipe title ...').fill(title);

    await adminPage.selectOption('select[name="_recipe_settings[difficulty_level]"]', '1');

    await adminPage.fill('input[name="_recipe_settings[prep_time]"]', '15');
    await adminPage.fill('input[name="_recipe_settings[cook_time]"]', '30');

    await adminPage.click('#cooked-recipe-tab-nutrition', { force: true });
    await adminPage.fill('input[name="_recipe_settings[nutrition][servings]"]', '4');

    await adminPage.check('input[name="tax_input[cp_recipe_category][]"][value="298"]');

    await adminPage.locator('#set-post-thumbnail').click();
    await adminPage.waitForSelector('.media-modal-content');
    await adminPage.getByRole('tab', { name: 'Media Library' }).click();
    await adminPage.waitForSelector('.attachment-preview');
    await adminPage.locator('.attachment-preview').first().click();
    await adminPage.locator('.media-button-select').first().click();
    await adminPage.waitForSelector('#remove-post-thumbnail');

    await adminPage.evaluate(() => {
      const excerptEditor = window.tinyMCE.get('_recipe_settings_excerpt');
      if (excerptEditor) {
        excerptEditor.setContent('This is a brief description of the recipe.');
      }

      const notesEditor = window.tinyMCE.get('_recipe_settings_notes');
      if (notesEditor) {
        notesEditor.setContent('Important notes about this recipe.');
      }

      const ingredientBlocks = document.querySelectorAll('.cooked-ingredient-block');
      if (ingredientBlocks.length > 0) {
        const firstIngredient = ingredientBlocks[0];
        const amountInput = firstIngredient.querySelector('input[data-ingredient-part="amount"]') as HTMLInputElement;
        const measurementSelect = firstIngredient.querySelector('select[data-ingredient-part="measurement"]') as HTMLSelectElement;
        const itemInput = firstIngredient.querySelector('input[data-ingredient-part="name"]') as HTMLInputElement;

        if (amountInput) amountInput.value = '2';
        if (measurementSelect) {
          measurementSelect.value = 'cup';
        }
        if (itemInput) itemInput.value = 'flour';
      }

      const directionEditors = Object.keys(window.tinyMCE.editors).filter(id => /^direction-\d+-content$/.test(id));
      if (directionEditors.length > 0) {
        const directionEditor = window.tinyMCE.get(directionEditors[0]);
        if (directionEditor) {
          directionEditor.setContent('First step of the recipe.');
        }
      }
    });

    await adminPage.getByRole('button', { name: 'Publish', exact: true }).click();

    await Promise.all([
      adminPage.waitForURL(/post\.php\?post=\d+&action=edit/),
      adminPage.waitForSelector('.notice-success', { timeout: 10000 })
    ]);

    await expect(adminPage.locator('.notice.notice-success')).toContainText('Post published.');

    const url = adminPage.url();
    const postIdMatch = url.match(/post=(\d+)/);
    postId = postIdMatch ? parseInt(postIdMatch[1]) : 0;

    expect(postId).toBeTruthy();
  });

  test('View the recipe (frontend)', async ({ adminContext }) => {
    if (!postId) {
      throw new Error('Post ID is not set');
    }
    const adminPage = await adminContext.newPage();
    await adminPage.goto('/?post_type=cp_recipe&p=' + postId, { waitUntil: 'networkidle' });
    await expect(adminPage.getByText(title)).toBeDefined();
  });

  test('Edit the recipe (admin)', async ({ adminContext }) => {
    if (!postId) {
      throw new Error('Post ID is not set');
    }
    const adminPage = await adminContext.newPage();
    await adminPage.goto(`/wp-admin/post.php?post=${postId}&action=edit`, { waitUntil: 'networkidle' });

    await expect(adminPage.getByText(title)).toBeDefined();

    await adminPage.getByLabel('Recipe title ...').fill('Test Recipe Playwright - Edited - ' + Date.now());

    await adminPage.getByRole('button', { name: 'Update', exact: true }).click();

    await Promise.all([
      adminPage.waitForURL(/post\.php\?post=\d+&action=edit/),
      adminPage.waitForSelector('.notice.notice-success', { timeout: 10000 })
    ]);

    await expect(adminPage.locator('.notice.notice-success')).toContainText('Post updated.');
  });
});

test.afterAll(() => {
  deletePost(postId);
});

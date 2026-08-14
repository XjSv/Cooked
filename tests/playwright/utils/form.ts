import { expect, type Page } from '@playwright/test';

declare global {
  interface Window {
    tinyMCE: {
      get: (id: string) => {
        setContent: (content: string) => void;
      };
      editors: { [key: string]: unknown };
    };
  }
}

export async function fillAndPublishAdminRecipe(page: Page, recipeTitle: string): Promise<string> {
  await page.goto('/wp-admin/post-new.php?post_type=cp_recipe', { waitUntil: 'networkidle' });

  await page.getByLabel('Recipe title ...').fill(recipeTitle);
  await page.selectOption('select[name="_recipe_settings[difficulty_level]"]', '1');
  await page.fill('input[name="_recipe_settings[prep_time]"]', '15');
  await page.fill('input[name="_recipe_settings[cook_time]"]', '30');

  await page.click('#cooked-recipe-tab-nutrition', { force: true });
  await page.fill('input[name="_recipe_settings[nutrition][servings]"]', '4');

  await page.check('input[name="tax_input[cp_recipe_category][]"][value="298"]');

  await page.locator('#set-post-thumbnail').click();
  await page.waitForSelector('.media-modal-content');
  await page.getByRole('tab', { name: 'Media Library' }).click();
  await page.waitForSelector('.attachment-preview');
  await page.locator('.attachment-preview').first().click();
  await page.locator('.media-button-select').first().click();
  await page.waitForSelector('#remove-post-thumbnail');

  await page.evaluate(() => {
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
      const measurementSelect = firstIngredient.querySelector(
        'select[data-ingredient-part="measurement"]'
      ) as HTMLSelectElement;
      const itemInput = firstIngredient.querySelector('input[data-ingredient-part="name"]') as HTMLInputElement;

      if (amountInput) {
        amountInput.value = '2';
      }
      if (measurementSelect) {
        measurementSelect.value = 'cup';
      }
      if (itemInput) {
        itemInput.value = 'flour';
      }
    }

    const directionEditors = Object.keys(window.tinyMCE.editors).filter((id) => /^direction-\d+-content$/.test(id));
    if (directionEditors.length > 0) {
      const directionEditor = window.tinyMCE.get(directionEditors[0]);
      if (directionEditor) {
        directionEditor.setContent('First step of the recipe.');
      }
    }
  });

  await page.getByRole('button', { name: 'Publish', exact: true }).click();

  await Promise.all([
    page.waitForURL(/post\.php\?post=\d+&action=edit/),
    page.waitForSelector('.notice-success', { timeout: 10000 }),
  ]);

  await expect(page.locator('.notice.notice-success')).toContainText('Post published.');

  const postId = page.url().match(/post=(\d+)/)?.[1] || '';
  if (!postId) {
    throw new Error('Published recipe ID was not found');
  }

  return postId;
}

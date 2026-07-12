import { test as base, expect } from '@playwright/test';
import { ensureValidAuth, getAuthPath } from '../../utils/auth';
import { execSync } from 'child_process';

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

// Create a fixture for authentication
const test = base.extend({
  adminContext: async ({ browser }, use) => {
    const context = await browser.newContext({
      storageState: getAuthPath('mtresova')
    });
    const page = await context.newPage();
    await ensureValidAuth(page, 'mtresova', 'password');
    await use(context);
    await page.close();
    await context.close();
  },
  contribContext: async ({ browser }, use) => {
    const context = await browser.newContext({
      storageState: getAuthPath('testUserContributor')
    });
    const page = await context.newPage();
    await ensureValidAuth(page, 'testUserContributor', 'password');
    await use(context);
    await page.close();
    await context.close();
  }
});

test.describe('Admin Star Ratings Settings (admin user)', () => {
  test('Enable Star Ratings (admin user)', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();
    await adminPage.goto('/wp-admin/admin.php?page=cooked_settings#engagement', { waitUntil: 'networkidle' });

    const select = adminPage.locator('select[name="cooked_settings[rating_type]"]');
    const currentValue = await select.evaluate((el) => (el as HTMLSelectElement).value);
    if (currentValue !== 'stars') {
      await select.selectOption('stars');
    }

    await adminPage.getByRole('button', { name: 'Update Settings' }).click();
    await expect(adminPage.getByText('Cooked settings has been updated!')).toBeDefined();
  });
});

test.describe('Create a new complete recipe (admin)', () => {
  test('Create a new recipe', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();
    await adminPage.goto('/wp-admin/post-new.php?post_type=cp_recipe', { waitUntil: 'networkidle' });
    title = 'Test Recipe Playwright: ' + Date.now();

    // Set recipe title
    await adminPage.getByLabel('Recipe title ...').fill(title);

    // Set difficulty level
    await adminPage.selectOption('select[name="_recipe_settings[difficulty_level]"]', '1'); // Beginner

    // Set times and servings
    await adminPage.fill('input[name="_recipe_settings[prep_time]"]', '15');
    await adminPage.fill('input[name="_recipe_settings[cook_time]"]', '30');
    // await adminPage.fill('input[name="_recipe_settings[total_time]"]', '45');

    // Click on the Nutrition tab to make those fields visible
    await adminPage.click('#cooked-recipe-tab-nutrition', { force: true });
    await adminPage.fill('input[name="_recipe_settings[nutrition][servings]"]', '4');

    // Set recipe taxonomies (using checkboxes)
    await adminPage.check('input[name="tax_input[cp_recipe_category][]"][value="298"]');
    await adminPage.check('input[name="tax_input[cp_recipe_cooking_method][]"][value="288"]');
    await adminPage.check('input[name="tax_input[cp_recipe_cuisine][]"][value="279"]');
    await adminPage.check('input[name="tax_input[cp_recipe_diet][]"][value="268"]');

    // Set recipe tags (can be multiple)
    await adminPage.locator('input[name="newtag[cp_recipe_tags]"]').fill('butter, egg, flour');
    await adminPage.locator('.button.tagadd').first().click();

    // Click the Set featured image link
    await adminPage.getByRole('link', { name: 'Set featured image' }).click();

    // Wait for the media modal to appear
    await adminPage.waitForSelector('.media-modal-content');

    // If you want to upload a new image, use this:
    // await adminPage.setInputFiles('input[type="file"]', 'path/to/your/image.jpg');
    // await adminPage.getByRole('button', { name: 'Upload' }).click();

    // Or to select an existing image from the media library:
    await adminPage.getByRole('tab', { name: 'Media Library' }).click();
    await adminPage.waitForSelector('.attachment-preview');

    // Select the first image in the media library
    await adminPage.locator('.attachment-preview').first().click();

    // Click the "Set Featured Image" button in the modal
    await adminPage.getByRole('button', { name: 'Set Featured Image' }).click();

    // Wait for the featured image to be set
    await adminPage.waitForSelector('#remove-post-thumbnail');

    // Handle all WYSIWYG editors
    await adminPage.evaluate(() => {
      // Excerpt editor
      const excerptEditor = window.tinyMCE.get('_recipe_settings_excerpt');
      if (excerptEditor) {
        excerptEditor.setContent('This is a brief description of the recipe.');
      }

      // Notes editor
      const notesEditor = window.tinyMCE.get('_recipe_settings_notes');
      if (notesEditor) {
        notesEditor.setContent('Important notes about this recipe.');
      }

      // Add ingredients
      // const addIngredientButton = document.querySelector('.cooked-add-ingredient-button');
      // if (addIngredientButton) {
      //   (addIngredientButton as HTMLElement).click();
      // }

      // Find the first ingredient fields using regex
      const ingredientBlocks = document.querySelectorAll('.cooked-ingredient-block');
      if (ingredientBlocks.length > 0) {
        const firstIngredient = ingredientBlocks[0];
        const amountInput = firstIngredient.querySelector('input[data-ingredient-part="amount"]') as HTMLInputElement;
        const measurementSelect = firstIngredient.querySelector('select[data-ingredient-part="measurement"]') as HTMLSelectElement;
        const itemInput = firstIngredient.querySelector('input[data-ingredient-part="name"]') as HTMLInputElement;

        if (amountInput) amountInput.value = '2';
        if (measurementSelect) {
          // Set the measurement to "cups"
          measurementSelect.value = 'cup';
        }
        if (itemInput) itemInput.value = 'flour';
      }

      // Add a direction step
      // const addDirectionButton = document.querySelector('.cooked-add-direction-button');
      // if (addDirectionButton) {
      //   (addDirectionButton as HTMLElement).click();
      // }

      // Find the first direction editor using regex
      const directionEditors = Object.keys(window.tinyMCE.editors).filter(id => /^direction-\d+-content$/.test(id));
      if (directionEditors.length > 0) {
        const directionEditor = window.tinyMCE.get(directionEditors[0]);
        if (directionEditor) {
          directionEditor.setContent('First step of the recipe.');
        }
      }
    });

    // Click the publish button first
    await adminPage.getByRole('button', { name: 'Publish', exact: true }).click();

    // Wait for both URL change and success message
    await Promise.all([
      // Wait for URL to change to post edit page
      adminPage.waitForURL(/post\.php\?post=\d+&action=edit/),
      // Wait for success message
      adminPage.waitForSelector('.notice-success', { timeout: 10000 })
    ]);

    // Check for success message content
    await expect(adminPage.locator('.notice-success')).toContainText('Post published.');

    // Get the post ID from the URL
    const url = adminPage.url();
    const postIdMatch = url.match(/post=(\d+)/);
    postId = postIdMatch ? parseInt(postIdMatch[1]) : 0;

    await adminPage.close();
  });
});

test.describe('Rate a recipe (contrib user)', () => {
  test('Rate the recipe (contrib user)', async ({ contribContext }) => {
    // Contributor rates the recipe
    const contribPage = await contribContext.newPage();

    // Get the frontend URL using WP-CLI
    if (!postId) {
      throw new Error('Post ID is not set');
    }

    await contribPage.goto('/?post_type=cp_recipe&p=' + postId, { waitUntil: 'networkidle' });
    await expect(contribPage.getByText(title)).toBeDefined();

    // Click on the 3-star rating button
    await contribPage.locator('.cooked-rating-stars .cooked-rating-choice[data-rating-value="3"]').click();

    // Add a small delay between ratings if needed
    await contribPage.waitForTimeout(1000); // 1 second delay

    // Verify the average rating is 3.0
    await expect(contribPage.locator('.cooked-current-rating')).toHaveText('3.0');

    // Now change to 5-star rating
    await contribPage.locator('.cooked-rating-stars .cooked-rating-choice[data-rating-value="5"]').click();

    // Add a small delay between ratings if needed
    await contribPage.waitForTimeout(1000); // 1 second delay

    // Verify the average rating is now 5.0
    await expect(contribPage.locator('.cooked-current-rating')).toHaveText('5.0');

    await contribPage.close();
  });
});

// Cleanup task - Delete the recipe
test.afterAll(async () => {
  if (postId) {
    try {
      console.log(`Cleaning up: Deleting recipe ${postId}`);
      execSync(`wp post delete ${postId} --force`);
    } catch (error) {
      console.error(`Failed to delete recipe ${postId}:`, error);
    }
  } else {
    console.log('No post ID to delete');
  }
});

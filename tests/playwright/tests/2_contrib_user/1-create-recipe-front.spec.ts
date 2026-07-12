import { test as base, Dialog, expect, Page } from '@playwright/test';
import { ensureValidAuth, getAuthPath } from '../../utils/auth';
import { readFileSync } from 'fs';

var title: string;
var postId: number;

const dragAndDropFile = async (
  page: Page,
  selector: string,
  filePath: string,
  fileName: string,
  fileType = ''
) => {
  const buffer = readFileSync(filePath).toString('base64');

  const dataTransfer = await page.evaluateHandle(
    async ({ bufferData, localFileName, localFileType }) => {
      const dt = new DataTransfer();

      const blobData = await fetch(bufferData).then((res) => res.blob());

      const file = new File([blobData], localFileName, { type: localFileType });
      dt.items.add(file);
      return dt;
    },
    {
      bufferData: `data:application/octet-stream;base64,${buffer}`,
      localFileName: fileName,
      localFileType: fileType,
    }
  );

  await page.dispatchEvent(selector, 'drop', { dataTransfer });
};

// Create a fixture for authentication
const test = base.extend({
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

test.describe('Create a new complete recipe front-end (contrib user)', () => {
  test('Create a new recipe (contrib user)', async ({ contribContext }) => {
    const contribPage = await contribContext.newPage();
    await contribPage.goto('/profile/add/', { waitUntil: 'networkidle' });
    title = 'Test Recipe Playwright: ' + Date.now();

    // Set recipe title
    await contribPage.fill('input[name="_recipe_settings[post_title]"]', title);

    // Set difficulty level
    await contribPage.selectOption('select[name="_recipe_settings[difficulty_level]"]', '1'); // Beginner

    // Set times and servings
    await contribPage.fill('input[name="_recipe_settings[prep_time]"]', '15');
    await contribPage.fill('input[name="_recipe_settings[cook_time]"]', '30');

    // Click on the Nutrition tab to make those fields visible
    await contribPage.click('.cooked-add-nutrition-button', { force: true });
    await contribPage.fill('input[name="_recipe_settings[nutrition][servings]"]', '4');

    // Set recipe taxonomies
    await contribPage.selectOption('select[name="_recipe_settings[category]"]', 'bread');
    await contribPage.selectOption('select[name="_recipe_settings[cooking_method]"]', 'baking');
    await contribPage.selectOption('select[name="_recipe_settings[cuisine]"]', 'albanian');
    await contribPage.selectOption('select[name="_recipe_settings[diet]"]', 'vegetarian');

    await dragAndDropFile(contribPage, "#featured_image", "tests/_files/icon_pro.png", "icon_pro.png", "image/png");

    await contribPage.fill('textarea[name="_recipe_settings[excerpt]"]', 'This is a brief description of the recipe.');
    await contribPage.fill('textarea[name="_recipe_settings[notes]"]', 'Important notes about this recipe.');

    // Handle all WYSIWYG editors
    await contribPage.evaluate(() => {
      // Add ingredients
      const addIngredientButton = document.querySelector('.cooked-add-ingredient-button');
      if (addIngredientButton) {
        (addIngredientButton as HTMLElement).click();
      }

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
      const addDirectionButton = document.querySelector('.cooked-add-direction-button');
      if (addDirectionButton) {
        (addDirectionButton as HTMLElement).click();
      }

      // Find and fill the direction textarea
      return new Promise((resolve) => {
        const checkTextarea = setInterval(() => {
          const directionTextareas = document.querySelectorAll('textarea[data-direction-part="content"]');

          if (directionTextareas.length > 0) {
            const firstDirectionTextarea = directionTextareas[0] as HTMLTextAreaElement;
            firstDirectionTextarea.value = 'First step of the recipe.';
            clearInterval(checkTextarea);
            resolve(true);
          }
        }, 500);

        // Set a timeout to prevent infinite checking
        setTimeout(() => {
          clearInterval(checkTextarea);
          resolve(false);
        }, 10000); // Maximum 10 second timeout
      });

    });

    // Click the publish button first
    await contribPage.getByRole('button', { name: 'Submit Recipe', exact: true }).click();

    // Wait for both URL change and success message
    await Promise.all([
      // Wait for URL to change to post edit page
      contribPage.waitForURL('/profile'),
      // Wait for success message
      contribPage.waitForSelector('.cooked-success-banner', { timeout: 10000 })
    ]);

    // Check for success message content
    await expect(contribPage.locator('.cooked-success-banner')).toContainText('You have successfully submitted a new recipe. It is now pending approval.');

    // After successful submission, get the recipe ID from the edit button URL
    const editButton = contribPage.locator('.cooked-edit-button').first();
    const href = await editButton.getAttribute('href');
    postId = parseInt(href?.match(/edit-recipe\/(\d+)/)?.[1] || '');

    expect(postId).toBeTruthy();
  });

  test('View the recipe (frontend)', async ({ contribContext }) => {
    const contribPage = await contribContext.newPage();
    await contribPage.goto('/?post_type=cp_recipe&p=' + postId, { waitUntil: 'networkidle' });

    // Check for recipe title in h1.entry-title
    await expect(contribPage.locator('h1.entry-title')).toHaveText(title);

    // Check for pending message
    await expect(contribPage.getByText('This recipe is pending review. No one else can see it yet.')).toBeVisible();
  });

  test('Edit the recipe (frontend)', async ({ contribContext }) => {
    const contribPage = await contribContext.newPage();
    // Get the frontend URL using WP-CLI
    if (!postId) {
      throw new Error('Post ID is not set');
    }

    await contribPage.goto('/profile/edit-recipe/' + postId + '/', { waitUntil: 'networkidle' });

    await expect(contribPage.getByText(title)).toBeDefined();

    // Change the title
    title = 'Test Recipe Playwright - Edited - ' + Date.now();
    await contribPage.fill('input[name="_recipe_settings[post_title]"]', title);

    // Click the publish button first
    await contribPage.getByRole('button', { name: 'Update Recipe', exact: true }).click();

    // Wait for both URL change and success message
    await Promise.all([
      // Wait for URL to change to post edit page
      contribPage.waitForURL('/profile'),
      // Wait for success message
      contribPage.waitForSelector('.cooked-success-banner', { timeout: 10000 })
    ]);

    // Check for success message content
    await expect(contribPage.locator('.cooked-success-banner')).toContainText('You have successfully edited the recipe. It is now pending approval.');

    await contribPage.close();
  });
});

// Delete the recipe
test.describe('Delete the recipe (contrib user)', () => {
  test('Delete the recipe (contrib user)', async ({ contribContext }) => {
    const contribPage = await contribContext.newPage();
    await contribPage.goto('/profile', { waitUntil: 'networkidle' });

    const deleteButton = contribPage.locator('.cooked-delete-button').first();
    await deleteButton.click();

    // Wait for confirmation dialog to appear
    await expect(contribPage.locator('.cooked-confirm-block .cooked-delete-final').first()).toBeVisible({ timeout: 10000 });

    // First, handle the confirm dialog that will appear
    contribPage.on('dialog', async (dialog: Dialog) => {
      // Automatically accept the confirmation
      await dialog.accept();
    });

    // OR alternatively, use dispatchEvent to trigger a native click
    await contribPage.evaluate(() => {
      const button = document.querySelector('.cooked-delete-button.cooked-delete-final') as HTMLElement;
      if (button) {
        button.dispatchEvent(new MouseEvent('click', {
          bubbles: true,
          cancelable: true,
          view: window
        }));
      }
    });

    // Wait for the recipe to be deleted
    await contribPage.waitForTimeout(1000);

    // Verify the recipe title no longer exists in the list
    await expect(contribPage.getByText(title)).not.toBeVisible();

    // Verify by trying to access the recipe URL directly
    const response = await contribPage.goto('/?post_type=cp_recipe&p=' + postId, { waitUntil: 'networkidle' });

    expect(response?.status()).toBe(404);
  });
});

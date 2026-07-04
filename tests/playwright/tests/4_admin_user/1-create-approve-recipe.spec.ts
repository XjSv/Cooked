import { test as base, Dialog, expect, Page } from '@playwright/test';
import { ensureValidAuth, getAuthPath } from '../../utils/auth';
import { execSync } from 'child_process';
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

test.describe('Create a new complete recipe front-end (contrib user)', () => {
  test('Create a new recipe', async ({ contribContext }) => {
    const contribPage = await contribContext.newPage();
    await contribPage.goto('/profile/add', { waitUntil: 'networkidle' });
    title = 'Test Recipe Playwright Time: ' + Date.now();

    // Set recipe title
    await contribPage.fill('input[name="_recipe_settings[post_title]"]', title);

    await dragAndDropFile(contribPage, "#featured_image", "tests/_files/icon_pro.png", "icon_pro.png", "image/png");

    // Set difficulty level
    await contribPage.selectOption('select[name="_recipe_settings[difficulty_level]"]', '1'); // Beginner

    // Set times and servings
    await contribPage.fill('input[name="_recipe_settings[prep_time]"]', '15');
    await contribPage.fill('input[name="_recipe_settings[cook_time]"]', '30');
    // await contribPage.fill('input[name="_recipe_settings[total_time]"]', '45');

    // Click on the Nutrition tab to make those fields visible
    await contribPage.click('.cooked-add-nutrition-button', { force: true });
    await contribPage.fill('input[name="_recipe_settings[nutrition][servings]"]', '4');

    // Set recipe taxonomies (using checkboxes)
    await contribPage.selectOption('select[name="_recipe_settings[category]"]', 'bread');
    await contribPage.selectOption('select[name="_recipe_settings[cooking_method]"]', 'baking');
    await contribPage.selectOption('select[name="_recipe_settings[cuisine]"]', 'albanian');
    await contribPage.selectOption('select[name="_recipe_settings[diet]"]', 'vegetarian');

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

    // Pause.
    // await contribPage.pause();

    // After successful submission, get the recipe ID from the edit button URL
    const editButton = contribPage.locator('.cooked-edit-button').first();
    const href = await editButton.getAttribute('href');
    postId = parseInt(href?.match(/edit-recipe\/(\d+)/)?.[1] || 0);

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
});

test.describe('Approve the recipe (admin user)', () => {
  test('Approve the recipe', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();
    await adminPage.goto('/wp-admin/admin.php?page=cooked_pending', { waitUntil: 'networkidle' });

    // First, handle the confirm dialog that will appear
    adminPage.on('dialog', async (dialog: Dialog) => {
      // Automatically accept the confirmation
      await dialog.accept();
    });

    // Find the recipe container by title and click its approve button
    const recipeContainer = adminPage.locator('.cooked-pending-recipe', {
        has: adminPage.locator('h3', { hasText: title })
    });
    await recipeContainer.locator('.button-primary').click();

    // Wait for the recipe to be deleted
    await adminPage.waitForTimeout(1000);

    // Check that the recipe title does not exist in the pending list
    await expect(adminPage.getByText(title)).not.toBeVisible();
  });

  test('Check that the recipe is visible (frontend)', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();
    await adminPage.goto('/?post_type=cp_recipe&p=' + postId, { waitUntil: 'networkidle' });

    // Check for recipe title in h1.entry-title
    await expect(adminPage.locator('h1.entry-title')).toHaveText(title);

    // Check for pending message
    await expect(adminPage.getByText('This recipe has been published and is viewable by everyone.')).toBeVisible();
  });
});

test.describe('View as anonymous user', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  test('Check that the recipe is visible (frontend)', async ({ page }) => {
    await page.goto('/?post_type=cp_recipe&p=' + postId, { waitUntil: 'networkidle' });

    // Check for recipe title in h1.entry-title
    await expect(page.locator('h1.entry-title')).toHaveText(title);
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

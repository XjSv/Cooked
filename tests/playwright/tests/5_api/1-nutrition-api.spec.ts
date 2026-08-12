import { test as base, expect } from '@playwright/test';
import { ensureValidAuth, getAuthPath } from '../../utils/auth';
import { execFileSync } from 'child_process';

function wp(args: string[]): string {
  return execFileSync('wp', [/* '--url=dev.mimisrecipes.ddev.site', */ '--quiet', ...args], {
    encoding: 'utf-8',
    maxBuffer: 10 * 1024 * 1024,
  }).trim();
}

function getCookedSettings(): Record<string, unknown> {
  try {
    const raw = wp(['option', 'get', 'cooked_settings', '--format=json']);
    return JSON.parse(raw) as Record<string, unknown>;
  } catch {
    return {};
  }
}

const EDAMAM_API_URL = 'https://api.edamam.com/api/nutrition-details';
const appId = process.env.EDAMAM_APP_ID || '';
const appKey = process.env.EDAMAM_APP_KEY || '';
const hasCredentials = appId !== '' && appKey !== '';

// ─── Test Group 1: Direct Edamam API Health Check ───────────────────────────

base.describe('Edamam API Health Check', () => {
  base.skip(!hasCredentials, 'EDAMAM_APP_ID and EDAMAM_APP_KEY must be set in .env');

  base.use({ storageState: { cookies: [], origins: [] } });

  base('API returns valid nutrition data for known ingredients', async ({ request }) => {
    const response = await request.post(
      `${EDAMAM_API_URL}?app_id=${appId}&app_key=${appKey}&beta=1&kitchen=home`,
      {
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Content-Language': 'en',
        },
        data: {
          title: 'Playwright Test Recipe',
          ingr: ['1 cup rice', '2 tablespoons olive oil', '1 teaspoon salt'],
          yield: '4',
        },
      }
    );

    expect(response.status()).toBe(200);

    const data = await response.json();

    expect(data).toHaveProperty('totalNutrients');
    expect(data).toHaveProperty('totalNutrientsKCal');
    expect(data).toHaveProperty('yield');
    expect(data).toHaveProperty('healthLabels');
    expect(data).toHaveProperty('dietLabels');

    expect(data.totalNutrients.FAT).toBeDefined();
    expect(data.totalNutrients.FAT).toHaveProperty('quantity');
    expect(data.totalNutrients.FAT).toHaveProperty('unit');

    expect(data.totalNutrients.PROCNT).toBeDefined();
    expect(data.totalNutrients.PROCNT).toHaveProperty('quantity');

    expect(data.totalNutrients.CHOCDF).toBeDefined();
    expect(data.totalNutrients.CHOCDF).toHaveProperty('quantity');

    expect(data.totalNutrientsKCal.ENERC_KCAL).toBeDefined();
    expect(data.totalNutrientsKCal.ENERC_KCAL.quantity).toBeGreaterThan(0);
  });

  base('API rejects invalid ingredients gracefully', async ({ request }) => {
    const response = await request.post(
      `${EDAMAM_API_URL}?app_id=${appId}&app_key=${appKey}&beta=1&kitchen=home`,
      {
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Content-Language': 'en',
        },
        data: {
          title: 'Bad Recipe',
          ingr: ['asdfghjkl zxcvbnm'],
          yield: '1',
        },
      }
    );

    // Edamam typically returns 422 or 555 for unrecognizable ingredients,
    // but may also return 200 with low-confidence data.
    const status = response.status();
    if (status === 200) {
      const data = await response.json();
      expect(data).toHaveProperty('totalNutrients');
    } else {
      expect([404, 422, 555]).toContain(status);
    }
  });
});

// ─── Test Group 2: WordPress Nutrition Integration ──────────────────────────

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

const test = base.extend({
  adminContext: async ({ browser }, use) => {
    const context = await browser.newContext({
      storageState: getAuthPath('mtresova'),
    });
    const page = await context.newPage();
    await ensureValidAuth(page, 'mtresova', 'password');
    await use(context);
    await page.close();
    await context.close();
  },
});

let postId: number;
let originalSettings: Record<string, unknown> | undefined;

test.describe('WordPress Nutrition Integration', () => {
  test.skip(!hasCredentials, 'EDAMAM_APP_ID and EDAMAM_APP_KEY must be set in .env');

  test.beforeAll(async () => {
    originalSettings = { ...getCookedSettings() };
    const merged = {
      ...originalSettings,
      enable_nutrition_api: ['enabled'],
      nutrition_api_app_id: appId,
      nutrition_api_app_key: appKey,
    };
    wp(['option', 'update', 'cooked_settings', JSON.stringify(merged), '--format=json']);
  });

  test('Create recipe and calculate nutrition via admin editor', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();

    // Step 1: Create and publish a recipe with ingredients
    await adminPage.goto('/wp-admin/post-new.php?post_type=cp_recipe', { waitUntil: 'networkidle' });

    const title = 'Chicken Tikka Masala';
    await adminPage.getByLabel('Recipe title ...').fill(title);

    await adminPage.fill('input[name="_recipe_settings[prep_time]"]', '10');
    await adminPage.fill('input[name="_recipe_settings[cook_time]"]', '20');

    // Click the Set featured image link
    await adminPage.locator('#set-post-thumbnail').click();

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
    await adminPage.locator('.media-button-select').first().click();

    // Wait for the featured image to be set
    await adminPage.waitForSelector('#remove-post-thumbnail');

    // Build a richer ingredient list so the nutrition API can parse it reliably.
    await adminPage.evaluate(() => {
      // Add excerpt because it is included in the nutrition API request payload.
      const excerptEditor = window.tinyMCE.get('_recipe_settings_excerpt');
      if (excerptEditor) {
        excerptEditor.setContent(
          'Creamy chicken tikka masala made with yogurt, tomato puree, and warm Indian spices for a rich, comforting flavor.'
        );
        // Ensure TinyMCE writes back to the underlying textarea that the AJAX request reads.
        if (typeof (excerptEditor as any).save === 'function') {
          (excerptEditor as any).save();
        }
      }
    });

    // Use a full, deterministic ingredient list. We first create enough blocks,
    // then fill by index so publish cannot race before inputs are populated.
    const recipeIngredients = [
      { amount: '1', measurement: 'lb', name: 'boneless chicken breast' },
      { amount: '0.5', measurement: 'cup', name: 'plain yogurt' },
      { amount: '1', measurement: 'tbsp', name: 'lemon juice' },
      { amount: '1', measurement: 'tbsp', name: 'garlic paste' },
      { amount: '1', measurement: 'tbsp', name: 'ginger paste' },
      { amount: '1', measurement: 'cup', name: 'tomato puree' },
      { amount: '2', measurement: 'tbsp', name: 'butter' },
      { amount: '2', measurement: 'tbsp', name: 'cooking oil' },
      { amount: '2', measurement: 'tsp', name: 'ground cumin' },
      { amount: '2', measurement: 'tsp', name: 'ground coriander' },
      { amount: '1', measurement: 'tsp', name: 'turmeric' },
      { amount: '1', measurement: 'tsp', name: 'chili powder' },
      { amount: '1', measurement: 'tsp', name: 'garam masala' },
      { amount: '1', measurement: 'tsp', name: 'kosher salt' },
      { amount: '2', measurement: 'tbsp', name: 'heavy cream' },
    ];

    // There is one default ingredient block; create the rest.
    // The "Add Ingredient" button lives under the Ingredients tab content.
    await adminPage.click('#cooked-recipe-tab-ingredients', { force: true });

    await adminPage.waitForSelector('#cooked-recipe-tab-content-ingredients .cooked-ingredient-block', {
      timeout: 30000,
    });
    await adminPage.waitForSelector('#cooked-recipe-tab-content-ingredients .cooked-add-ingredient-button', {
      timeout: 30000,
    });

    for (let i = 1; i < recipeIngredients.length; i++) {
      await adminPage.click('#cooked-recipe-tab-content-ingredients .cooked-add-ingredient-button');
    }

    await adminPage.waitForFunction((expectedCount: number) => {
      return document.querySelectorAll('.cooked-ingredient-block').length >= expectedCount;
    }, recipeIngredients.length);

    await adminPage.evaluate((ingredients: Array<{ amount: string; measurement: string; name: string }>) => {
      const ingredientBlocks = document.querySelectorAll('.cooked-ingredient-block');

      ingredients.forEach((ingredient, index) => {
        const block = ingredientBlocks[index] as HTMLElement | undefined;
        if (!block) return;

        const amount = block.querySelector('input[data-ingredient-part="amount"]') as HTMLInputElement | null;
        const measurement = block.querySelector('select[data-ingredient-part="measurement"]') as HTMLSelectElement | null;
        const name = block.querySelector('input[data-ingredient-part="name"]') as HTMLInputElement | null;

        if (amount) {
          amount.value = ingredient.amount;
          amount.dispatchEvent(new Event('input', { bubbles: true }));
          amount.dispatchEvent(new Event('change', { bubbles: true }));
        }
        if (measurement) {
          measurement.value = ingredient.measurement;
          measurement.dispatchEvent(new Event('change', { bubbles: true }));
        }
        if (name) {
          name.value = ingredient.name;
          name.dispatchEvent(new Event('input', { bubbles: true }));
          name.dispatchEvent(new Event('change', { bubbles: true }));
        }
      });
    }, recipeIngredients);

    await adminPage.waitForFunction((expectedCount: number) => {
      const blocks = document.querySelectorAll('.cooked-ingredient-block');
      if (blocks.length < expectedCount) return false;

      return Array.from(blocks)
        .slice(0, expectedCount)
        .every((block) => {
          const name = (block.querySelector('input[data-ingredient-part="name"]') as HTMLInputElement | null)?.value?.trim();
          return Boolean(name);
        });
    }, recipeIngredients.length);

    // Extra short settle so any plugin listeners complete before publish.
    await adminPage.waitForTimeout(300);

    // Fill a direction step so the recipe is valid
    await adminPage.click('#cooked-recipe-tab-directions', { force: true });
    await adminPage.evaluate(() => {
      const directionEditors = Object.keys(window.tinyMCE.editors).filter(id =>
        /^direction-\d+-content$/.test(id)
      );
      if (directionEditors.length > 0) {
        const editor = window.tinyMCE.get(directionEditors[0]);
        if (editor) editor.setContent(
          'Marinate chicken with yogurt, garlic, and ginger, then cook with butter and warm spices. Add tomato puree and simmer until thick, then stir in cream for a rich tikka masala sauce.'
        );
      }
    });

    await adminPage.click('#cooked-recipe-tab-nutrition', { force: true });
    await adminPage.fill('input[name="_recipe_settings[nutrition][servings]"]', '4');

    // Publish the recipe
    await adminPage.getByRole('button', { name: 'Publish', exact: true }).click();

    await Promise.all([
      adminPage.waitForURL(/post\.php\?post=\d+&action=edit/),
      adminPage.waitForSelector('.notice-success', { timeout: 10000 }),
    ]);

    const url = adminPage.url();
    const postIdMatch = url.match(/post=(\d+)/);
    postId = postIdMatch ? parseInt(postIdMatch[1]) : 0;
    expect(postId).toBeTruthy();

    // Step 2: Navigate to the nutrition tab and calculate
    await adminPage.click('#cooked-recipe-tab-nutrition', { force: true });

    // Click the "Calculate Nutrition Information" button to open the tooltip
    await adminPage.click('.cooked-auto-nutrition-button');

    // Wait for the tooltipster tooltip to appear with the Calculate button
    await adminPage.waitForSelector('#cooked-auto-nutrition-button', { state: 'visible', timeout: 5000 });

    // Click "Calculate" inside the tooltip
    await adminPage.click('#cooked-auto-nutrition-button');

    // Stop for debugging
    // await adminPage.pause();

    // Wait for the AJAX to complete — poll until calories field is populated
    const caloriesInput = adminPage.locator('input[name="_recipe_settings[nutrition][calories]"]');
    await expect(caloriesInput).not.toHaveValue('', { timeout: 30000000 }); // 30000

    // Assert: key nutrition fields are populated with numeric values > 0
    const calories = await caloriesInput.inputValue();
    expect(Number(calories)).toBeGreaterThan(0);

    const fat = await adminPage.locator('input[name="_recipe_settings[nutrition][fat]"]').inputValue();
    expect(Number(fat)).toBeGreaterThan(0);

    const protein = await adminPage.locator('input[name="_recipe_settings[nutrition][protein]"]').inputValue();
    expect(Number(protein)).toBeGreaterThan(0);

    const carbs = await adminPage.locator('input[name="_recipe_settings[nutrition][carbs]"]').inputValue();
    expect(Number(carbs)).toBeGreaterThan(0);

    // Assert: etag was saved (indicates a successful API round-trip)
    const etag = await adminPage.locator('input[name="_recipe_settings[nutrition][etag]"]').inputValue();
    expect(etag).toBeTruthy();
  });

  test.afterAll(async () => {
    if (postId) {
      try {
        wp(['post', 'delete', String(postId), '--force']);
      } catch (error) {
        console.error(`Failed to delete recipe ${postId}:`, error);
      }
    }

    if (originalSettings !== undefined) {
      try {
        wp([
          'option',
          'update',
          'cooked_settings',
          JSON.stringify(originalSettings),
          '--format=json',
        ]);
      } catch (error) {
        console.error('Failed to restore original settings:', error);
      }
    }
  });
});

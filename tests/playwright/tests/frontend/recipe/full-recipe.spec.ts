import { test, expect, type Page } from '@playwright/test';
import { FULL_RECIPE, SEED_CATEGORY, fullRecipeId } from '../../../utils/seed';

// The seed's full recipe uses every feature, so this spec checks that each one renders on the
// single recipe page and that the interactive ones work for a visitor.

let recipeUrl = '';

async function openRecipe(page: Page, query = ''): Promise<void> {
  await page.goto(recipeUrl + query, { waitUntil: 'networkidle' });
  await page.locator('.cooked-recipe-ingredients').first().waitFor();
}

test.describe('Full recipe (anonymous user)', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  test.beforeAll(() => {
    recipeUrl = `/?post_type=cp_recipe&p=${fullRecipeId()}`;
  });

  test('renders every recipe feature', async ({ page }) => {
    const errors: string[] = [];
    page.on('pageerror', (error) => errors.push(error.message));

    await openRecipe(page);
    const recipe = page.locator('.cooked-recipe-info').first().locator('xpath=..');

    await test.step('info bar', async () => {
      await expect(recipe.locator('.cooked-author').first()).toBeVisible();
      await expect(recipe.locator('.cooked-taxonomy').first()).toContainText(SEED_CATEGORY.name);
      await expect(recipe.locator('.cooked-difficulty-level').first()).toContainText('Intermediate');
      await expect(recipe.locator('.cooked-print a').first()).toHaveAttribute('href', /print=1/);
      await expect(recipe.locator('.cooked-fsm-button').first()).toBeVisible();
    });

    await test.step('excerpt, image, servings and times', async () => {
      await expect(recipe.locator('.cooked-recipe-excerpt').first()).toContainText(FULL_RECIPE.excerpt);
      await expect(recipe.locator('.cooked-post-featured-image img').first()).toBeVisible();
      await expect(recipe.locator('.cooked-servings').first()).toContainText(`${FULL_RECIPE.servings} Servings`);
      for (const time of ['.cooked-prep-time', '.cooked-cook-time', '.cooked-total-time']) {
        await expect(recipe.locator(time).first()).toBeVisible();
      }
      await expect(recipe.locator('.cooked-allergens').first()).toBeVisible();
    });

    await test.step('ingredients', async () => {
      const ingredients = recipe.locator('.cooked-recipe-ingredients').first();
      await expect(ingredients.locator('.cooked-heading')).toContainText(FULL_RECIPE.ingredientHeading);
      await expect(ingredients.locator('.cooked-ingredient-substitution').first()).toContainText(FULL_RECIPE.substitute);
    });

    await test.step('directions', async () => {
      const directions = recipe.locator('.cooked-recipe-directions').first();
      await expect(directions.locator('.cooked-heading')).toContainText(FULL_RECIPE.directionHeading);
      await expect(directions.locator('.cooked-direction-has-image img')).toBeVisible();
      await expect(directions.locator('.cooked-timer a')).toContainText(FULL_RECIPE.timerLabel);
    });

    await test.step('notes, nutrition and gallery', async () => {
      await expect(recipe.locator('.cooked-recipe-notes').first()).toContainText(FULL_RECIPE.notes);
      const label = recipe.locator('.cooked-nutrition-label').first();
      await expect(label).toBeVisible();
      await expect(label.locator('.cooked-nut-headline')).toContainText('320');
      await expect(label.locator('tr.cooked-nut-level-1').first()).toContainText('Saturated Fat');
      await expect(label.locator('tr.cooked-nut-level-2')).toContainText('Added Sugars');
      await expect(label.locator('tbody')).toHaveCount(2);
      await expect(recipe.locator('.cooked-gallery .cooked-gallery-nav-item')).toHaveCount(3);
    });

    expect(errors).toEqual([]);
  });

  test('servings switcher scales the ingredients', async ({ page }) => {
    await openRecipe(page);
    const flour = page.locator('.cooked-recipe-ingredients .cooked-ing-amount').first();
    await expect(flour).toHaveAttribute('data-decimal', '2');

    await page.locator('.cooked-servings-changer').first().selectOption(String(FULL_RECIPE.servings * 2));
    await page.waitForURL(/servings=8/);
    await expect(page.locator('.cooked-recipe-ingredients .cooked-ing-amount').first()).toHaveAttribute('data-decimal', '4');
  });

  test('timer starts from a direction', async ({ page }) => {
    await openRecipe(page);
    await page.locator('.cooked-recipe-directions .cooked-timer a').first().click();
    await expect(page.locator('#cooked-timers-wrap .cooked-timer-block').first()).toBeVisible();
    await expect(page.locator('#cooked-timers-wrap')).toContainText(FULL_RECIPE.timerLabel);
  });

  test('full-screen mode opens and closes', async ({ page }) => {
    await openRecipe(page);
    await page.locator('.cooked-fsm-button').first().click();
    const fsm = page.locator('.cooked-fsm.cooked-active');
    await expect(fsm).toBeVisible();
    await expect(fsm).toContainText(FULL_RECIPE.directionHeading);
    await fsm.locator('.cooked-close-fsm').click();
    await expect(page.locator('.cooked-fsm.cooked-active')).toHaveCount(0);
  });

  test('gallery thumbnails change the slide', async ({ page }) => {
    await openRecipe(page);
    const thumbs = page.locator('.cooked-gallery .cooked-gallery-nav-item');
    await thumbs.nth(1).click();
    await expect(thumbs.nth(1)).toHaveAttribute('aria-current', 'true');
    await expect(thumbs.nth(0)).not.toHaveAttribute('aria-current', 'true');
  });

  test('print view shows the recipe', async ({ page }) => {
    await openRecipe(page, '&print=1');
    await expect(page.locator('.cooked-recipe-ingredients').first()).toContainText(FULL_RECIPE.substitute);
    await expect(page.locator('.cooked-recipe-directions').first()).toContainText(FULL_RECIPE.directionHeading);
  });
});

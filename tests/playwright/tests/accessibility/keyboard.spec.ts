import type { Page } from '@playwright/test';
import { test, expect } from '../../utils/fixtures';

// Keyboard-only use of Cooked's admin screens and the recipe editor: everything below is done
// with key presses, no clicks.

async function openSettings(page: Page, hash = '') {
  await page.goto(`/wp-admin/admin.php?page=cooked_settings${hash ? '#' + hash : ''}`, { waitUntil: 'networkidle' });
  await page.locator('.cooked-admin-ready').waitFor();
}

/** Presses Tab until the focused element matches, or fails after `max` presses. */
async function tabTo(page: Page, selector: string, max = 250) {
  for (let i = 0; i < max; i++) {
    await page.keyboard.press('Tab');
    if (await page.evaluate((s) => !!document.activeElement && document.activeElement.matches(s), selector)) {
      return;
    }
  }
  throw new Error(`Tab never reached ${selector}`);
}

async function focusedText(page: Page): Promise<string> {
  return page.evaluate(() => (document.activeElement?.textContent || '').trim());
}

test.describe('Keyboard: admin frame', () => {
  test('skip link jumps to the section content', async ({ adminContext }) => {
    const page = await adminContext.newPage();
    await openSettings(page);
    await tabTo(page, '.cooked-admin-skip-link');
    await expect(page.locator('.cooked-admin-skip-link')).toBeVisible();
    await page.keyboard.press('Enter');
    await expect(page.locator('#cooked-admin-content')).toBeFocused();
  });

  test('section menu: arrow keys move, Enter opens', async ({ adminContext }) => {
    const page = await adminContext.newPage();
    await openSettings(page);
    const sections = page.locator('.cooked-admin-section');
    await tabTo(page, '.cooked-admin-section');
    const first = await focusedText(page);
    await page.keyboard.press('ArrowDown');
    const second = await focusedText(page);
    expect(second).not.toBe(first);
    await page.keyboard.press('Enter');
    await expect(sections.filter({ hasText: second })).toHaveClass(/is-active/);
    await expect(page.locator('.cooked-admin-section-header h2')).toHaveText(second);
  });

  test('page tabs open another screen with Enter', async ({ adminContext }) => {
    const page = await adminContext.newPage();
    await openSettings(page);
    await tabTo(page, '.cooked-admin-page-tab[href*="cooked_import"]');
    await page.keyboard.press('Enter');
    await expect(page).toHaveURL(/page=cooked_import/);
    await expect(page.locator('.cooked-admin-screen[data-screen="cooked_import"]')).toBeVisible();
  });

  test('settings search: type, pick with the arrows, land on the setting', async ({ adminContext }) => {
    const page = await adminContext.newPage();
    await openSettings(page);
    await tabTo(page, '.cooked-admin-search-input');
    await page.keyboard.type('recipe permalink');
    await page.locator('.cooked-admin-search-result').first().waitFor();
    await page.keyboard.press('ArrowDown');
    await page.keyboard.press('Enter');
    await expect(page.locator('.cooked-admin-section.is-active')).toHaveText(/Permalinks/);
    // Focus moves to the setting's control.
    await expect.poll(() => page.evaluate(() => (document.activeElement as HTMLInputElement | null)?.name || '')).toMatch(/recipe_permalink/);
  });
});

test.describe('Keyboard: recipe editor', () => {
  test('tabs: Tab reaches the active tab; arrows, Home and End switch tabs', async ({ adminContext }) => {
    const page = await adminContext.newPage();
    await page.goto('/wp-admin/post-new.php?post_type=cp_recipe', { waitUntil: 'networkidle' });
    await expect(page.locator('#cooked-recipe-tabs')).toHaveAttribute('role', 'tablist');

    // Only the active tab is in the Tab order.
    await tabTo(page, '#cooked-recipe-tabs [role="tab"]', 80);
    await expect(page.locator('#cooked-recipe-tab-content')).toBeFocused();

    await page.keyboard.press('ArrowRight');
    await expect(page.locator('#cooked-recipe-tab-ingredients')).toBeFocused();
    await expect(page.locator('#cooked-recipe-tab-ingredients')).toHaveAttribute('aria-selected', 'true');
    await expect(page.locator('#cooked-recipe-tab-content-ingredients')).toBeVisible();

    await page.keyboard.press('End');
    await expect(page.locator('#cooked-recipe-tab-shortcodes')).toBeFocused();
    await expect(page.locator('#cooked-recipe-tab-content-shortcodes')).toBeVisible();

    await page.keyboard.press('Home');
    await expect(page.locator('#cooked-recipe-tab-content')).toBeFocused();
    await expect(page.locator('#cooked-recipe-tab-content-content')).toBeVisible();
    await expect(page.locator('#cooked-recipe-tab-shortcodes')).toHaveAttribute('tabindex', '-1');
  });

  test('ingredient row actions are reachable and work', async ({ adminContext }) => {
    const page = await adminContext.newPage();
    await page.goto('/wp-admin/post-new.php?post_type=cp_recipe', { waitUntil: 'networkidle' });
    await page.locator('#cooked-recipe-tab-ingredients').focus();
    await page.keyboard.press('Enter');

    const row = page.locator('#cooked-ingredients-builder .cooked-ingredient-block:not(.cooked-ingredient-heading)').first();
    await row.locator('input[data-ingredient-part="name"]').focus();
    await page.keyboard.type('butter');
    await tabTo(page, '.cooked-show-substitution', 5);
    await expect(row.locator('.cooked-show-substitution')).toHaveCSS('opacity', '1');
    await page.keyboard.press('Enter');
    await expect(row.locator('.cooked-substitution-row')).toHaveCount(1);
    await expect(row.locator('.cooked-substitution-row input[data-substitution-part="amount"]')).toBeFocused();
  });

  test('shortcode options open with Enter', async ({ adminContext }) => {
    const page = await adminContext.newPage();
    await page.goto('/wp-admin/post-new.php?post_type=cp_recipe', { waitUntil: 'networkidle' });
    await page.locator('#cooked-recipe-tab-shortcodes').focus();
    await page.keyboard.press('Enter');
    const options = page.locator('.cooked-shortcode-options').first();
    await options.locator('summary').focus();
    await page.keyboard.press('Enter');
    await expect(options).toHaveAttribute('open', '');
    await expect(options.locator('.cooked-shortcode-options-table')).toBeVisible();
  });
});

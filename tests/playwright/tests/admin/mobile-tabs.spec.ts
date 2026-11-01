import type { Locator, Page } from '@playwright/test';
import { test, expect } from '../../utils/fixtures';

// On a phone the recipe editor's tabs and the Settings section menu show only the items that fit
// whole, with arrows that page through the rest: no label is ever cut off at an edge, and every
// item can be reached.

/** Labels fully inside the row, and how many items stick out of it partly. */
async function shownItems(row: Locator, items: Locator): Promise<{ shown: string[]; cut: number }> {
  return row.evaluate((el, selector) => {
    const box = el.getBoundingClientRect();
    let cut = 0;
    const shown: string[] = [];
    el.querySelectorAll<HTMLElement>(selector).forEach((item) => {
      const r = item.getBoundingClientRect();
      if (!r.width) {
        return; // hidden by a condition
      }
      const inside = r.left >= box.left - 1 && r.right <= box.right + 1;
      const overlaps = r.right > box.left + 1 && r.left < box.right - 1;
      if (inside) {
        shown.push((item.textContent || '').trim());
      } else if (overlaps) {
        cut++;
      }
    });
    return { shown, cut };
  }, await items.first().evaluate((el) => (el.matches('li') ? 'li' : '.cooked-admin-section')));
}

/** Pages right to the end, checking each page; returns every label seen. */
async function pageThrough(page: Page, row: Locator, items: Locator, next: Locator): Promise<string[]> {
  const seen = new Set<string>();
  for (let i = 0; i < 12; i++) {
    const { shown, cut } = await shownItems(row, items);
    expect(cut, `page ${i}: no item cut off`).toBe(0);
    expect(shown.length, `page ${i}: something shown`).toBeGreaterThan(0);
    shown.forEach((label) => seen.add(label));
    if (await next.isDisabled()) {
      break;
    }
    await next.click();
    await page.waitForTimeout(400); // the smooth scroll
  }
  return [...seen];
}

test.describe('Tab rows on a phone (390px)', () => {
  test('recipe editor tabs page through whole tabs', async ({ adminContext }) => {
    const page = await adminContext.newPage();
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/wp-admin/post-new.php?post_type=cp_recipe', { waitUntil: 'networkidle' });

    const row = page.locator('#cooked-recipe-tabs');
    const items = row.locator('li');
    await expect(page.locator('.cooked-recipe-tabs-bar')).toHaveClass(/has-overflow/);

    const seen = await pageThrough(page, row, items, page.locator('.cooked-recipe-tabs-arrow.is-right'));
    const all = await items.evaluateAll((lis) => lis.filter((li) => (li as HTMLElement).offsetParent).map((li) => (li.textContent || '').trim()));
    expect(seen.sort()).toEqual(all.sort());

    // A tab reached this way opens.
    await page.locator('#cooked-recipe-tab-shortcodes').click();
    await expect(page.locator('#cooked-recipe-tab-content-shortcodes')).toBeVisible();
  });

  test('Settings sections page through whole sections', async ({ adminContext }) => {
    const page = await adminContext.newPage();
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/wp-admin/admin.php?page=cooked_settings', { waitUntil: 'networkidle' });
    await page.locator('.cooked-admin-ready').waitFor();

    const row = page.locator('.cooked-admin-sections');
    const items = row.locator('.cooked-admin-section');
    const seen = await pageThrough(page, row, items, page.locator('.cooked-admin-sections-arrow.is-right'));
    const all = await items.allTextContents();
    expect(seen.sort()).toEqual(all.map((t) => t.trim()).sort());

    // A deep link opens on its section's page.
    await page.goto('/wp-admin/admin.php?page=cooked_settings#permalinks', { waitUntil: 'networkidle' });
    await page.locator('.cooked-admin-ready').waitFor();
    const { shown } = await shownItems(row, items);
    expect(shown).toContain('Permalinks');
  });
});

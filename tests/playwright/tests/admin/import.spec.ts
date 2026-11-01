import { test, expect } from '../../utils/fixtures';

// The importers for source plugins that have no recipes to import read as an empty state
// ("Nothing to import yet"), not as a lone sentence under the source's name.

test('an importer with nothing to import shows an empty state', async ({ adminContext }) => {
  const page = await adminContext.newPage();
  await page.goto('/wp-admin/admin.php?page=cooked_import', { waitUntil: 'networkidle' });
  await page.locator('.cooked-admin-ready').waitFor();

  test.skip((await page.locator('.cooked-import-empty').count()) === 0, 'every source plugin has recipes to import');

  for (const [slug, section] of [
    ['no_delicious_recipes', 'WP Delicious'],
    ['no_wp_recipe_maker_recipes', 'WP Recipe Maker'],
  ]) {
    const content = page.locator(`#cooked-settings-tab-content-${slug}`);
    if ((await content.count()) === 0) {
      continue;
    }

    await page.locator('.cooked-admin-section').filter({ hasText: section }).click();
    const empty = content.locator('.cooked-import-empty');
    await expect(empty).toBeVisible();
    await expect(empty.locator('.cooked-import-empty-title')).toHaveText('Nothing to import yet');
    await expect(empty).toContainText('appear here ready to import');
  }
});

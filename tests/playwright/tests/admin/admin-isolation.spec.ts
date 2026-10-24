import { test, expect } from '../../utils/fixtures';

// Cooked's frame owns the page inset and adds body classes only on its own screens. Other
// WordPress admin screens (including the recipe list, where Cooked assets load) must keep
// WordPress's normal #wpcontent padding and no Cooked body class.

const SCREENS = [
  ['dashboard', '/wp-admin/index.php'],
  ['posts list', '/wp-admin/edit.php'],
  ['plugins', '/wp-admin/plugins.php'],
  ['general settings', '/wp-admin/options-general.php'],
  ['profile', '/wp-admin/profile.php'],
  ['recipe list', '/wp-admin/edit.php?post_type=cp_recipe'],
];

const COOKED_BODY_CLASSES = [
  'cooked-admin-page',
  'cooked-recipe-editor',
  'cooked-admin-dark',
  'cooked-admin-dark-auto',
  'cooked-settings-page',
];

for (const [name, url] of SCREENS) {
  test(`${name} keeps WordPress's admin padding and no Cooked body class`, async ({ adminContext }) => {
    const page = await adminContext.newPage();
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto(url, { waitUntil: 'networkidle' });

    const bodyClass = (await page.locator('body').getAttribute('class')) || '';
    for (const cookedClass of COOKED_BODY_CLASSES) {
      expect(bodyClass.split(/\s+/), `${name}: no ${cookedClass}`).not.toContain(cookedClass);
    }

    await expect(page.locator('#cooked-admin-app')).toHaveCount(0);

    const padding = await page.locator('#wpcontent').evaluate((el) => parseFloat(getComputedStyle(el).paddingLeft));
    expect(padding, `${name}: #wpcontent keeps its inset`).toBeGreaterThan(0);
  });
}

test('the dashboard keeps WordPress padding on a phone', async ({ adminContext }) => {
  const page = await adminContext.newPage();
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/wp-admin/index.php', { waitUntil: 'networkidle' });

  const bodyClass = (await page.locator('body').getAttribute('class')) || '';
  expect(bodyClass).not.toMatch(/\bcooked-/);

  const padding = await page.locator('#wpcontent').evaluate((el) => parseFloat(getComputedStyle(el).paddingLeft));
  expect(padding).toBeGreaterThan(0);
});

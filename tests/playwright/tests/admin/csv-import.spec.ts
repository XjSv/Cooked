import { test, expect } from '../../utils/fixtures';
import { Page } from '@playwright/test';
import { deletePostsByTitle } from '../../utils/wp-cli';
import path from 'path';

const TEST_DATA_DIR = path.resolve(__dirname, '../../../test_data');

const SMALL_CSV_TITLES = ['Chocolate Chip Cookies'];
const MEDIUM_CSV_TITLES = ['Classic Beef Lasagna', 'Vegetarian Buddha Bowl', 'Chocolate Lava Cake'];
const LARGE_CSV_TITLES = [
  'Simple Scrambled Eggs', 'Classic Caesar Salad', 'Beef Stroganoff',
  'Banana Bread', 'Chicken Tikka Masala', 'Caprese Salad',
  'Beef and Broccoli Stir Fry', 'Apple Pie', 'Greek Salad', 'Chicken Noodle Soup'
];

const allImportedTitles: string[] = [];

async function importCsvFile(adminPage: Page, csvFileName: string) {
  await adminPage.goto('/wp-admin/admin.php?page=cooked_import', { waitUntil: 'networkidle' });

  await adminPage.locator('#cooked-settings-tab-csv_import a').click();

  await expect(adminPage.locator('#cooked-settings-tab-content-csv_import')).toBeVisible({ timeout: 10000 });

  const csvPath = path.join(TEST_DATA_DIR, csvFileName);
  await adminPage.setInputFiles('#cooked-csv-file', csvPath);

  adminPage.once('dialog', dialog => dialog.accept());
  await adminPage.click('#cooked-csv-import-button');

  await expect(adminPage.locator('#cooked-csv-import-completed')).toBeVisible({ timeout: 60000 });
  await expect(adminPage.locator('#cooked-csv-import-completed')).toContainText('Import Complete!');
}

test.describe('CSV Import (admin)', () => {

  test('Import small CSV (1 recipe)', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();
    await importCsvFile(adminPage, 'recipes-small.csv');
    allImportedTitles.push(...SMALL_CSV_TITLES);
  });

  test('Verify small CSV recipe exists as draft', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();
    await adminPage.goto('/wp-admin/edit.php?post_type=cp_recipe&post_status=draft', { waitUntil: 'networkidle' });

    for (const title of SMALL_CSV_TITLES) {
      await expect(adminPage.locator('.wp-list-table a.row-title').filter({ hasText: title }).first()).toBeVisible();
    }
  });

  test('Import medium CSV (3 recipes with section headings and substitutions)', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();
    await importCsvFile(adminPage, 'recipes-medium.csv');
    allImportedTitles.push(...MEDIUM_CSV_TITLES);
  });

  test('Verify medium CSV recipes exist as drafts', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();
    await adminPage.goto('/wp-admin/edit.php?post_type=cp_recipe&post_status=draft', { waitUntil: 'networkidle' });

    for (const title of MEDIUM_CSV_TITLES) {
      await expect(adminPage.locator('.wp-list-table a.row-title').filter({ hasText: title }).first()).toBeVisible();
    }
  });

  test('Import large CSV (10 recipes)', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();
    await importCsvFile(adminPage, 'recipes-large.csv');
    allImportedTitles.push(...LARGE_CSV_TITLES);
  });

  test('Verify large CSV recipes exist as drafts', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();
    await adminPage.goto('/wp-admin/edit.php?post_type=cp_recipe&post_status=draft', { waitUntil: 'networkidle' });

    for (const title of LARGE_CSV_TITLES) {
      await expect(adminPage.locator('.wp-list-table a.row-title').filter({ hasText: title }).first()).toBeVisible();
    }
  });

  test('Verify imported recipe content (spot check)', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();
    await adminPage.goto('/wp-admin/edit.php?post_type=cp_recipe&post_status=draft', { waitUntil: 'networkidle' });

    const recipeLink = adminPage.locator('.wp-list-table .row-title').filter({ hasText: 'Chocolate Chip Cookies' }).first();
    await recipeLink.click();
    await adminPage.waitForURL(/post\.php\?post=\d+&action=edit/, { timeout: 15000 });

    await expect(adminPage.locator('input[name="_recipe_settings[prep_time]"]')).toHaveValue('15');
    await expect(adminPage.locator('input[name="_recipe_settings[cook_time]"]')).toHaveValue('12');
    await expect(adminPage.locator('select[name="_recipe_settings[difficulty_level]"]')).toHaveValue('1');
  });
});

test.afterAll(() => {
  const allTitles = [...SMALL_CSV_TITLES, ...MEDIUM_CSV_TITLES, ...LARGE_CSV_TITLES];
  for (const title of allTitles) {
    deletePostsByTitle(title);
  }
});

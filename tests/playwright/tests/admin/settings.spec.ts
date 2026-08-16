import { type Page } from '@playwright/test';
import { test, expect } from '../../utils/fixtures';
import { getCookedSettings, setCookedSettings } from '../../utils/wp-cli';

test.describe.configure({ mode: 'serial' });

let originalSettings: Record<string, unknown> = {};

async function gotoSettings(page: Page, hash: string): Promise<void> {
  await page.goto(`/wp-admin/admin.php?page=cooked_settings#${hash}`, {
    waitUntil: 'networkidle',
  });
  await page.locator(`#cooked-settings-tab-${hash} a`).click();
  await expect(page.locator(`#cooked-settings-tab-content-${hash}`)).toBeVisible();
}

async function saveSettings(page: Page): Promise<void> {
  await page.getByRole('button', { name: 'Update Settings' }).first().click();
  await expect(page.getByText('Cooked settings has been updated!')).toBeVisible();
  await expect(page.locator('#cooked-settings-panel .notice-error')).toHaveCount(0);
  await expect(page.getByRole('heading', { name: /There has been a critical error/i })).toHaveCount(0);
  await expect(page).toHaveURL(/page=cooked_settings/);
}

async function clickSwitch(page: Page, checkboxId: string): Promise<void> {
  await page.locator(`#${checkboxId} + .switchery`).click();
}

function settingList(settings: Record<string, unknown>, key: string): string[] {
  const value = settings[key];
  return Array.isArray(value) ? value.map(String) : [];
}

test.beforeAll(() => {
  originalSettings = getCookedSettings();
});

test.describe('Cooked settings page', () => {
  test('saves without changes', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();
    await gotoSettings(adminPage, 'recipe_settings');

    await saveSettings(adminPage);
  });

  test('persists a select field', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();
    await gotoSettings(adminPage, 'recipe_settings');

    const select = adminPage.locator('select[name="cooked_settings[carb_format]"]');
    const current = await select.inputValue();
    const next = current === 'net' ? 'total' : 'net';

    await select.selectOption(next);
    await saveSettings(adminPage);

    await gotoSettings(adminPage, 'recipe_settings');
    await expect(adminPage.locator('select[name="cooked_settings[carb_format]"]')).toHaveValue(next);
    expect(getCookedSettings().carb_format).toBe(next);
  });

  test('persists a checkbox toggle', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();
    await gotoSettings(adminPage, 'recipe_settings');

    const checkboxId = 'checkbox-group-print_view_display_options-site_logo';
    const checkbox = adminPage.locator(`#${checkboxId}`);
    const wasChecked = await checkbox.isChecked();

    await clickSwitch(adminPage, checkboxId);
    if (wasChecked) {
      await expect(checkbox).not.toBeChecked();
    } else {
      await expect(checkbox).toBeChecked();
    }
    await saveSettings(adminPage);

    await gotoSettings(adminPage, 'recipe_settings');
    const reloaded = adminPage.locator(`#${checkboxId}`);
    if (wasChecked) {
      await expect(reloaded).not.toBeChecked();
    } else {
      await expect(reloaded).toBeChecked();
    }

    const saved = settingList(getCookedSettings(), 'print_view_display_options');
    expect(saved.includes('site_logo')).toBe(!wasChecked);
  });

  test('persists a number field on the Design tab', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();
    await gotoSettings(adminPage, 'design');

    const input = adminPage.locator('input[name="cooked_settings[responsive_breakpoint_1]"]');
    const current = await input.inputValue();
    const next = current === '1001' ? '1002' : '1001';

    await input.fill(next);
    await saveSettings(adminPage);

    await gotoSettings(adminPage, 'design');
    await expect(adminPage.locator('input[name="cooked_settings[responsive_breakpoint_1]"]')).toHaveValue(next);
    expect(String(getCookedSettings().responsive_breakpoint_1)).toBe(next);
  });
});

test.afterAll(() => {
  setCookedSettings(originalSettings);
});

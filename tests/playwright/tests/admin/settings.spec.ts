import { type Page } from '@playwright/test';
import { test, expect } from '../../utils/fixtures';
import { gotoSettingsSection, saveSettings } from '../../utils/settings';
import { getCookedSettings, setCookedSettings } from '../../utils/wp-cli';

test.describe.configure({ mode: 'serial' });

let originalSettings: Record<string, unknown> = {};

async function clickSwitch(page: Page, checkboxId: string): Promise<void> {
  await page.locator(`#${checkboxId}`).click();
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
    await gotoSettingsSection(adminPage, 'recipe_settings');

    await saveSettings(adminPage);
  });

  test('persists a select field', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();
    await gotoSettingsSection(adminPage, 'recipe_settings');

    const select = adminPage.locator('select[name="cooked_settings[carb_format]"]');
    const current = await select.inputValue();
    const next = current === 'net' ? 'total' : 'net';

    await select.selectOption(next);
    await saveSettings(adminPage);

    await gotoSettingsSection(adminPage, 'recipe_settings');
    await expect(adminPage.locator('select[name="cooked_settings[carb_format]"]')).toHaveValue(next);
    expect(getCookedSettings().carb_format).toBe(next);
  });

  test('persists a checkbox toggle', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();
    await gotoSettingsSection(adminPage, 'recipe_settings');

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

    await gotoSettingsSection(adminPage, 'recipe_settings');
    const reloaded = adminPage.locator(`#${checkboxId}`);
    if (wasChecked) {
      await expect(reloaded).not.toBeChecked();
    } else {
      await expect(reloaded).toBeChecked();
    }

    const saved = settingList(getCookedSettings(), 'print_view_display_options');
    expect(saved.includes('site_logo')).toBe(!wasChecked);
  });

  test('persists a colour field on the Design tab', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();
    await gotoSettingsSection(adminPage, 'design');

    const input = adminPage.locator('input[name="cooked_settings[main_color]"]');
    const current = await input.inputValue();
    const next = current === '#123456' ? '#654321' : '#123456';

    // The text field sits inside WordPress's closed colour picker, so set it directly.
    const setColour = (value: string) =>
      adminPage.locator('input[name="cooked_settings[main_color]"]').evaluate((el, v) => {
        (el as HTMLInputElement).value = v;
      }, value);

    await setColour(next);
    await saveSettings(adminPage);

    await gotoSettingsSection(adminPage, 'design');
    await expect(adminPage.locator('input[name="cooked_settings[main_color]"]')).toHaveValue(next);
    expect(String(getCookedSettings().main_color)).toBe(next);

    await setColour(current);
    await saveSettings(adminPage);
  });

  test('reports a value that was not kept and marks its row', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();
    await gotoSettingsSection(adminPage, 'design');

    const input = adminPage.locator('input[name="cooked_settings[main_color]"]');
    const original = await input.inputValue();

    // What a paste into the colour field sends: no browser validation blocks it, so the
    // server falls back to the default and has to say so.
    await input.evaluate((el, value) => {
      (el as HTMLInputElement).value = value;
    }, '#zzzzzz');
    await adminPage.getByRole('button', { name: 'Update Settings' }).first().click();

    await expect(adminPage.getByText(/Main Color: "#zzzzzz" is not a valid value/i)).toBeVisible();
    const row = adminPage.locator('.cooked-setting-row.has-error').filter({ has: input });
    await expect(row).toHaveCount(1);
    await expect(input).toHaveCSS('border-color', 'rgb(212, 77, 31)');

    await input.fill(original);
    await saveSettings(adminPage);
  });

  test('opens the section of an invalid field before reporting it', async ({ adminContext }) => {
    const adminPage = await adminContext.newPage();
    await gotoSettingsSection(adminPage, 'recipe_settings');

    // A field in a hidden section that fails the browser's validation: the browser cannot show
    // its message there, so the frame has to open the field's section first.
    const input = adminPage.locator('input[name="cooked_settings[recipe_permalink]"]');
    await input.evaluate((el) => {
      (el as HTMLInputElement).setCustomValidity('Invalid permalink');
    });

    await adminPage.getByRole('button', { name: 'Update Settings' }).first().click();

    await expect(adminPage.locator('.cooked-admin-section.is-active')).toHaveText(/Permalinks/);
    await expect(input).toBeFocused();
    expect(await input.evaluate((el: HTMLInputElement) => el.checkValidity())).toBe(false);

    await input.evaluate((el) => {
      (el as HTMLInputElement).setCustomValidity('');
    });
  });
});

test.afterAll(() => {
  setCookedSettings(originalSettings);
});

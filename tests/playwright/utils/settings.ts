import { expect, type Page } from '@playwright/test';

/**
 * Open a Cooked Settings section through the new admin frame. The legacy PHP
 * tabs are hidden once the frame mounts, so tests should use the section hash
 * instead of clicking the old tab links.
 */
export async function gotoSettingsSection(page: Page, section: string): Promise<void> {
  await page.goto(`/wp-admin/admin.php?page=cooked_settings#${section}`, {
    waitUntil: 'networkidle',
  });

  await expect(page.locator('.cooked-admin-section.is-active')).toBeVisible();
  await expect(page.locator(`#cooked-settings-tab-content-${section}`)).toBeVisible();
}

/** Save the current Settings section and fail on WordPress/PHP error notices. */
export async function saveSettings(page: Page): Promise<void> {
  await page.getByRole('button', { name: 'Update Settings' }).first().click();
  await expect(page.getByText('Cooked settings has been updated!')).toBeVisible();
  await expect(page.locator('#cooked-settings-panel .notice-error')).toHaveCount(0);
  await expect(page.getByRole('heading', { name: /There has been a critical error/i })).toHaveCount(0);
  await expect(page).toHaveURL(/page=cooked_settings/);
}

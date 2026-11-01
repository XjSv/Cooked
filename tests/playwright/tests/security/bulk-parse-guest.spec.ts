import { test, expect } from '../../utils/fixtures';
import { getCookedSettings, setCookedSettings, wpCliArgs } from '../../utils/wp-cli';

test.describe.configure({ mode: 'serial' });

function guestNonce(): string {
  return wpCliArgs(['eval', 'echo wp_create_nonce("cooked_bulk_add");']);
}

function setGuestMode(enabled: boolean): void {
  const settings = getCookedSettings();
  settings.recipe_submission_guest_mode = enabled ? ['active'] : [];
  setCookedSettings(settings);
}

test.describe('Bulk ingredient parse', () => {
  let originalSettings: Record<string, unknown> = {};

  test.beforeAll(() => {
    originalSettings = getCookedSettings();
    setGuestMode(false);
  });

  test.afterAll(() => {
    if (Object.keys(originalSettings).length) {
      setCookedSettings(originalSettings);
    }
  });

  test('does not parse for a logged-out visitor when guest submissions are off', async ({ browser }) => {
    setGuestMode(false);
    const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    const response = await context.request.post('/wp-admin/admin-ajax.php', {
      form: {
        action: 'cooked_parse_bulk_ingredients',
        nonce: guestNonce(),
        'lines[0]': '2 cups flour',
      },
    });
    expect(response.ok()).toBeTruthy();
    const body = await response.json();
    expect(body.success).toBe(false);
    await context.close();
  });

  test('parses one ingredient for a logged-out visitor when guest submissions are on', async ({ browser }) => {
    setGuestMode(true);
    const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    const response = await context.request.post('/wp-admin/admin-ajax.php', {
      form: {
        action: 'cooked_parse_bulk_ingredients',
        nonce: guestNonce(),
        'lines[0]': '2 cups flour',
      },
    });
    expect(response.ok()).toBeTruthy();
    const body = await response.json();
    expect(body.success).toBe(true);
    expect(body.data.parsed[0].amount).toBe('2');
    expect(body.data.parsed[0].measurement).toBe('cup');
    expect(body.data.parsed[0].name).toBe('flour');
    await context.close();
  });

  test('rejects more than 300 lines', async ({ browser }) => {
    setGuestMode(true);
    const form: Record<string, string> = {
      action: 'cooked_parse_bulk_ingredients',
      nonce: guestNonce(),
    };
    for (let i = 0; i < 301; i++) {
      form[`lines[${i}]`] = '1 cup flour';
    }

    const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    const response = await context.request.post('/wp-admin/admin-ajax.php', { form });
    expect(response.ok()).toBeTruthy();
    const body = await response.json();
    expect(body.success).toBe(false);
    expect(body.data.parsed).toBeUndefined();
    await context.close();
  });

  test('still parses for a logged-in editor when guest submissions are off', async ({ adminContext }) => {
    setGuestMode(false);
    const page = await adminContext.newPage();
    await page.goto('/wp-admin/post-new.php?post_type=cp_recipe', { waitUntil: 'load' });
    const nonce = await page.evaluate(() => {
      const vars = (window as { cooked_migration_js_vars?: { cooked_bulk_add_nonce?: string } }).cooked_migration_js_vars;
      return vars?.cooked_bulk_add_nonce || '';
    });
    expect(nonce).not.toBe('');

    const response = await page.request.post('/wp-admin/admin-ajax.php', {
      form: {
        action: 'cooked_parse_bulk_ingredients',
        nonce,
        'lines[0]': '2 cups flour',
      },
    });
    expect(response.ok()).toBeTruthy();
    const body = await response.json();
    expect(body.success).toBe(true);
    expect(body.data.parsed[0].name).toBe('flour');
    await page.close();
  });
});

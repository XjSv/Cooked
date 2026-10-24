import { Page } from '@playwright/test';
import { test } from '../../utils/fixtures';
import { expectAccessible } from '../../utils/a11y';
import { ADMIN_USER } from '../../utils/users';
import { wpCliArgs } from '../../utils/wp-cli';

// Cooked's admin screens (the admin frame: Settings, Import, What's New, Upgrade to Pro),
// every section, and the modals and dropdowns they open, in light and dark mode. WordPress's
// own admin menu is excluded (the shared helper already excludes the admin bar).

async function openScreen(page: Page, slug: string, hash = '') {
  await page.goto(`/wp-admin/admin.php?page=${slug}&t=${Date.now()}${hash ? '#' + hash : ''}`, { waitUntil: 'networkidle' });
  await page.locator('.cooked-admin-ready').waitFor();
}

async function sectionSlugs(page: Page): Promise<string[]> {
  return page.locator('.cooked-admin-section').evaluateAll(() =>
    // The section menu has no slugs in the DOM; read them from the localized config.
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    ((window as any).cooked_admin_app_js_vars.screens[new URL(location.href).searchParams.get('page') || ''].sections as { slug: string }[]).map((s) => s.slug)
  );
}

function proActive(): boolean {
  try {
    wpCliArgs(['plugin', 'is-active', 'cooked-pro']);
    return true;
  } catch {
    return false;
  }
}

for (const theme of ['light', 'dark'] as const) {
  test.describe(`Admin screens accessibility (${theme})`, () => {
    test.describe.configure({ mode: 'serial' });

    test.beforeAll(() => {
      if (theme === 'dark') {
        wpCliArgs(['user', 'meta', 'update', ADMIN_USER.user, 'cooked_admin_dark_mode', 'enabled']);
      }
    });

    test.afterAll(() => {
      if (theme === 'dark') {
        wpCliArgs(['user', 'meta', 'delete', ADMIN_USER.user, 'cooked_admin_dark_mode']);
      }
    });

    for (const screen of ['cooked_settings', 'cooked_import']) {
      test(`${screen} - every section`, async ({ adminContext }) => {
        const page = await adminContext.newPage();
        await openScreen(page, screen);
        for (const slug of await sectionSlugs(page)) {
          await test.step(slug, async () => {
            await openScreen(page, screen, slug);
            await expectAccessible(page, { exclude: ['#adminmenumain'] });
          });
        }
      });
    }

    for (const screen of ['cooked_welcome', 'cooked_pro']) {
      test(`${screen}`, async ({ adminContext }) => {
        // Upgrade to Pro is not registered while Cooked Pro is active.
        test.skip(screen === 'cooked_pro' && proActive(), 'Cooked Pro is active');
        const page = await adminContext.newPage();
        await openScreen(page, screen);
        await expectAccessible(page, { exclude: ['#adminmenumain'] });
      });
    }

    test('changelog modal', async ({ adminContext }) => {
      const page = await adminContext.newPage();
      await openScreen(page, 'cooked_welcome');
      await page.locator('.cooked-full-changelog-button').click();
      await page.locator('.cooked-changelog-full').waitFor();
      await expectAccessible(page, { include: ['.cooked-modal'] });
    });

    test('CSV preview modal', async ({ adminContext }) => {
      const page = await adminContext.newPage();
      await openScreen(page, 'cooked_import', 'csv_import');
      await page.locator('.cooked-csv-sample').first().click();
      await page.locator('.cooked-csv-preview table').waitFor();
      await expectAccessible(page, { include: ['.cooked-modal'] });
    });

    test('settings search with suggestions open', async ({ adminContext }) => {
      const page = await adminContext.newPage();
      await openScreen(page, 'cooked_welcome');
      await page.locator('.cooked-admin-search-input').fill('break');
      await page.locator('.cooked-admin-search-result').first().waitFor();
      await page.keyboard.press('ArrowDown');
      await expectAccessible(page, { exclude: ['#adminmenumain'] });
    });

    test('settings select dropdown (Tom Select)', async ({ adminContext }) => {
      const page = await adminContext.newPage();
      await openScreen(page, 'cooked_settings', 'recipe_settings');
      await page.locator('select[name="cooked_settings[carb_format]"] + .ts-wrapper .ts-control').click();
      await page.locator('select[name="cooked_settings[carb_format]"] + .ts-wrapper .ts-dropdown').waitFor();
      await expectAccessible(page, { exclude: ['#adminmenumain'] });
    });
  });
}

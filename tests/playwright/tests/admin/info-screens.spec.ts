import { test, expect } from '../../utils/fixtures';
import { wpCliArgs } from '../../utils/wp-cli';

// Smoke tests for the What's New and Upgrade to Pro screens of the admin frame.

function proActive(): boolean {
  try {
    wpCliArgs(['plugin', 'is-active', 'cooked-pro']);
    return true;
  } catch {
    return false;
  }
}

test.describe("What's New", () => {
  test('banner, quick links and the changelog', async ({ adminContext }) => {
    const page = await adminContext.newPage();
    await page.goto('/wp-admin/admin.php?page=cooked_welcome', { waitUntil: 'networkidle' });
    await page.locator('.cooked-admin-ready').waitFor();
    const screen = page.locator('.cooked-admin-screen[data-screen="cooked_welcome"]');

    const banner = screen.locator('img.cooked-admin-banner');
    await expect(banner).toBeVisible();
    await expect(banner).toHaveAttribute('alt', /.+/);
    expect(await banner.evaluate((img: HTMLImageElement) => img.complete && img.naturalWidth > 0)).toBe(true);

    // Quick links: documentation and Discord open outside, Settings and a new recipe inside.
    const links = screen.locator('.cooked-welcome-links a');
    await expect(links.filter({ has: page.locator('.cooked-welcome-link-external') })).toHaveCount(2);
    await expect(screen.locator('.cooked-welcome-links a[href*="page=cooked_settings"]')).toHaveCount(1);
    await expect(screen.locator('.cooked-welcome-links a[href*="post-new.php?post_type=cp_recipe"]')).toHaveCount(1);
    await expect(screen.locator('.cooked-welcome-links a[href*="page=cooked_pro"]')).toHaveCount(proActive() ? 0 : 1);

    // The changelog: the newest release with typed entries, and all releases in the modal.
    const changelog = screen.locator('.cooked-welcome-changelog');
    await expect(changelog.locator('h4').first()).toBeVisible();
    expect(await changelog.locator('li[class^="cooked-change-"]').count()).toBeGreaterThan(0);
    await screen.locator('.cooked-full-changelog-button').click();
    const full = page.locator('.cooked-changelog-full');
    await expect(full).toBeVisible();
    expect(await full.locator('h4.cooked-changelog-version').count()).toBeGreaterThan(1);
  });
});

test.describe('Upgrade to Pro', () => {
  test('shown without Cooked Pro: features, the call to action and the coupon', async ({ adminContext }) => {
    test.skip(proActive(), 'Cooked Pro is active');
    const page = await adminContext.newPage();
    await page.goto('/wp-admin/admin.php?page=cooked_pro', { waitUntil: 'networkidle' });
    await page.locator('.cooked-admin-ready').waitFor();
    const screen = page.locator('.cooked-admin-screen[data-screen="cooked_pro"]');

    expect(await screen.locator('.cooked-pro-features li').count()).toBeGreaterThan(6);
    const cta = screen.locator('a.cooked-pro-button');
    await expect(cta).toHaveAttribute('href', /cooked\.pro/);
    await expect(cta).toHaveAttribute('target', '_blank');
    await expect(cta).toHaveAttribute('rel', /noopener/);
    await expect(screen.locator('.cooked-pro-coupon .cooked-copy-code')).toBeVisible();

    // In the menu and the page tabs.
    await expect(page.locator('#adminmenu a[href="admin.php?page=cooked_pro"]')).toHaveCount(1);
    await expect(page.locator('.cooked-admin-page-tab[href*="cooked_pro"]')).toHaveCount(1);
  });

  test('hidden with Cooked Pro active', async ({ adminContext }) => {
    test.skip(!proActive(), 'Cooked Pro is not active');
    const page = await adminContext.newPage();
    await page.goto('/wp-admin/admin.php?page=cooked_settings', { waitUntil: 'networkidle' });
    await page.locator('.cooked-admin-ready').waitFor();
    await expect(page.locator('#adminmenu a[href="admin.php?page=cooked_pro"]')).toHaveCount(0);
    await expect(page.locator('.cooked-admin-page-tab[href*="cooked_pro"]')).toHaveCount(0);
    await expect(page.locator('.cooked-admin-screen[data-screen="cooked_pro"]')).toHaveCount(0);
  });
});

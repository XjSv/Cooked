import { test, expect } from '../../utils/fixtures';
import { deletePost, wpCliArgs } from '../../utils/wp-cli';

test.describe('Settings Vue delimiters', () => {
  let pageId = 0;
  const marker = "{{ 'EXECUTED' }}";

  test.beforeAll(() => {
    const title = `E2E ${marker} ${Date.now()}`;
    pageId = Number(
      wpCliArgs([
        'post',
        'create',
        '--post_type=page',
        '--post_status=publish',
        `--post_title=${title}`,
        '--porcelain',
      ])
    );
  });

  test.afterAll(() => {
    deletePost(pageId);
  });

  // Cooked 2.0 no longer mounts Vue on the settings screen; the title must still stay literal.
  test('keeps a mustache page title literal on the settings screen', async ({ adminContext }) => {
    const page = await adminContext.newPage();
    await page.goto('/wp-admin/admin.php?page=cooked_settings', { waitUntil: 'domcontentloaded' });

    const browsePage = page.locator('select[name="cooked_settings[browse_page]"]');
    await expect(browsePage.locator('option', { hasText: marker })).toHaveCount(1);

    const pwned = await page.evaluate(() => (window as Window & { __pwned?: boolean }).__pwned);
    expect(pwned).not.toBe(true);
  });
});

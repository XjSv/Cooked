import { test, expect } from '../../utils/fixtures';
import { deletePost, getCookedSettings, setCookedSettings, wpCliArgs } from '../../utils/wp-cli';
import { CONTRIB_USER } from '../../utils/users';

const ORIGINAL = 'ORIGINAL-BLOG-CONTENT-DO-NOT-TOUCH';
const INJECT_EXCERPT = 'PWNED-BY-CONTRIBUTOR-VIA-RECIPE-HOOK';

function recipeMeta(id: number): string {
  try {
    return wpCliArgs(['post', 'meta', 'get', String(id), '_recipe_settings', '--format=json']);
  } catch {
    return '';
  }
}

test.describe('Recipe meta post type', () => {
  let postId = 0;
  let originalSettings: Record<string, unknown> = {};

  test.beforeAll(() => {
    originalSettings = getCookedSettings();
    const settings = JSON.parse(JSON.stringify(originalSettings)) as Record<string, unknown>;
    const roles = settings.disable_dashboard_access_roles;
    if (Array.isArray(roles) && roles.includes('contributor')) {
      settings.disable_dashboard_access_roles = roles.filter((role) => role !== 'contributor');
      setCookedSettings(settings);
    }

    const authorId = wpCliArgs(['user', 'get', CONTRIB_USER.user, '--field=ID']);
    postId = Number(
      wpCliArgs([
        'post',
        'create',
        '--post_type=post',
        '--post_status=draft',
        `--post_author=${authorId}`,
        '--post_title=My Innocent Blog Post',
        `--post_content=${ORIGINAL}`,
        '--porcelain',
      ])
    );
  });

  test.afterAll(() => {
    deletePost(postId);
    if (Object.keys(originalSettings).length) {
      setCookedSettings(originalSettings);
    }
  });

  test('does not write recipe settings onto a contributor blog post', async ({ contribContext }) => {
    const page = await contribContext.newPage();
    await page.goto('/wp-admin/post-new.php?post_type=cp_recipe', { waitUntil: 'networkidle' });
    const recipeNonce = await page.locator('input[name="cooked_recipe_custom_box_nonce"]').inputValue();

    await page.goto(`/wp-admin/post.php?post=${postId}&action=edit`, { waitUntil: 'networkidle' });
    const wpnonce = await page.locator('#_wpnonce').inputValue();

    const savedTitle = `My Innocent Blog Post ${Date.now()}`;
    const response = await page.request.post('/wp-admin/post.php', {
      form: {
        action: 'editpost',
        post_ID: String(postId),
        post_type: 'post',
        _wpnonce: wpnonce,
        _wp_http_referer: `/wp-admin/post.php?post=${postId}&action=edit`,
        post_title: savedTitle,
        content: ORIGINAL,
        post_status: 'draft',
        original_post_status: 'draft',
        cooked_recipe_custom_box_nonce: recipeNonce,
        '_recipe_settings[excerpt]': INJECT_EXCERPT,
        '_recipe_settings[content]': 'injected recipe body',
        save: 'Save Draft',
      },
    });
    expect(response.ok()).toBeTruthy();

    expect(wpCliArgs(['post', 'get', String(postId), '--field=post_title'])).toBe(savedTitle);
    expect(wpCliArgs(['post', 'get', String(postId), '--field=post_content'])).toContain(ORIGINAL);
    expect(wpCliArgs(['post', 'get', String(postId), '--field=post_excerpt'])).not.toContain(INJECT_EXCERPT);
    expect(recipeMeta(postId)).toBe('');
  });
});

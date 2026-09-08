import { test, expect } from '../../utils/fixtures';
import { deletePost, deletePostsByTitle, wpCliArgs } from '../../utils/wp-cli';
import { ADMIN_USER, CONTRIB_USER } from '../../utils/users';

const BODY_MARKER = 'HTTP-POSITIVE-BEFORE-COOKED-IDOR';
const EXCERPT_MARKER = 'HTTP-POSITIVE-BEFORE-EXCERPT-COOKED-IDOR';
const PAGE_TITLE = 'Cooked IDOR Victim Page';
const PRIVATE_PAGE_TITLE = 'Cooked IDOR Private Page';

function createPage(opts: {
  title: string;
  content: string;
  excerpt: string;
  status: 'publish' | 'private';
}): number {
  const authorId = wpCliArgs(['user', 'get', ADMIN_USER.user, '--field=ID']);
  const id = wpCliArgs([
    'post',
    'create',
    '--post_type=page',
    `--post_status=${opts.status}`,
    `--post_title=${opts.title}`,
    `--post_content=${opts.content}`,
    `--post_excerpt=${opts.excerpt}`,
    `--post_author=${authorId}`,
    '--porcelain',
  ]);
  return Number(id);
}

function deletePagesByTitle(title: string): void {
  try {
    const ids = wpCliArgs([
      'post',
      'list',
      '--post_type=page',
      '--post_status=any',
      `--title=${title}`,
      '--format=ids',
    ]);
    for (const id of ids.split(/\s+/).filter(Boolean)) {
      deletePost(id);
    }
  } catch {
    // none
  }
}

function getPostField(id: number, field: string): string {
  return wpCliArgs(['post', 'get', String(id), `--field=${field}`]);
}

function nonceForUser(action: string, user: string): string {
  return wpCliArgs(['eval', `echo wp_create_nonce(${JSON.stringify(action)});`, `--user=${user}`]);
}

test.describe('Migrate/import AJAX IDOR', () => {
  let pageId: number;
  let privatePageId: number;

  test.beforeAll(() => {
    deletePagesByTitle(PAGE_TITLE);
    deletePagesByTitle(PRIVATE_PAGE_TITLE);
    deletePostsByTitle(PAGE_TITLE);
    deletePostsByTitle(PRIVATE_PAGE_TITLE);

    pageId = createPage({
      title: PAGE_TITLE,
      content: BODY_MARKER,
      excerpt: EXCERPT_MARKER,
      status: 'publish',
    });
    privatePageId = createPage({
      title: PRIVATE_PAGE_TITLE,
      content: 'PRIVATE-BODY-COOKED-IDOR',
      excerpt: 'PRIVATE-EXCERPT-COOKED-IDOR',
      status: 'private',
    });
  });

  test.afterAll(() => {
    deletePost(pageId);
    deletePost(privatePageId);
    deletePagesByTitle(PAGE_TITLE);
    deletePagesByTitle(PRIVATE_PAGE_TITLE);
    deletePostsByTitle(PAGE_TITLE);
    deletePostsByTitle(PRIVATE_PAGE_TITLE);
  });

  test('Contributor cannot overwrite an admin page via cooked_migrate_recipes without a nonce', async ({
    contribContext,
  }) => {
    const contribPage = await contribContext.newPage();
    await contribPage.goto('/wp-admin/', { waitUntil: 'domcontentloaded' });

    const response = await contribPage.request.post('/wp-admin/admin-ajax.php', {
      form: {
        action: 'cooked_migrate_recipes',
        recipe_ids: JSON.stringify([pageId]),
      },
    });

    const body = (await response.text()).trim();
    expect(body).not.toBe('false');
    expect(getPostField(pageId, 'post_content')).toContain(BODY_MARKER);
    expect(getPostField(pageId, 'post_excerpt')).toContain(EXCERPT_MARKER);
  });

  test('Contributor cannot overwrite an admin page via cooked_migrate_recipes with their own nonce', async ({
    contribContext,
  }) => {
    const nonce = nonceForUser('cooked_migrate_recipes', CONTRIB_USER.user);
    const contribPage = await contribContext.newPage();
    await contribPage.goto('/wp-admin/', { waitUntil: 'domcontentloaded' });

    const response = await contribPage.request.post('/wp-admin/admin-ajax.php', {
      form: {
        action: 'cooked_migrate_recipes',
        recipe_ids: JSON.stringify([pageId]),
        nonce,
      },
    });

    const body = (await response.text()).trim();
    expect(body).not.toBe('false');
    expect(getPostField(pageId, 'post_content')).toContain(BODY_MARKER);
    expect(getPostField(pageId, 'post_excerpt')).toContain(EXCERPT_MARKER);
  });

  test('Contributor cannot import a private page via cooked_import_recipes', async ({ contribContext }) => {
    const nonce = nonceForUser('cooked_import_recipes', CONTRIB_USER.user);
    const contribPage = await contribContext.newPage();
    await contribPage.goto('/wp-admin/', { waitUntil: 'domcontentloaded' });

    await contribPage.request.post('/wp-admin/admin-ajax.php', {
      form: {
        action: 'cooked_import_recipes',
        recipe_ids: JSON.stringify([privatePageId]),
        import_type: 'delicious_recipes',
        nonce,
      },
    });

    const imported = wpCliArgs([
      'post',
      'list',
      '--post_type=cp_recipe',
      '--post_status=any',
      `--title=${PRIVATE_PAGE_TITLE}`,
      '--format=ids',
    ]);
    expect(imported).toBe('');
    expect(getPostField(privatePageId, 'post_content')).toContain('PRIVATE-BODY-COOKED-IDOR');
  });
});

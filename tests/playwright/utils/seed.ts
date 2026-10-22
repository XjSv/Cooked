import { ADMIN_USER } from './users';
import { getCookedSettings, setCookedSettings, wpCli, wpCliArgs } from './wp-cli';

// Content the specs expect on any site, including a fresh wp-env stack in CI.
// Each step checks first, so an existing site keeps its own content.

export const BROWSE_SLUG = 'browse-recipes';
export const SEED_CATEGORY = { name: 'Bread', slug: 'bread' };

// Old dates keep the seed recipes at the end of "newest first" and the start of "oldest first".
export const SEED_RECIPES = {
  bread: { title: 'E2E Seed Bread', date: '2000-01-01 10:00:00', category: SEED_CATEGORY.slug },
  beef: { title: 'E2E Seed Beef Stew', date: '2000-01-02 10:00:00' },
  chicken: { title: 'E2E Seed Chicken Soup', date: '2000-01-03 10:00:00' },
};

const SEED_IMAGE = 'wp-content/plugins/cooked/tests/playwright/test-data/images/icon_pro.png';

function firstId(output: string): number {
  const id = Number(output.split('\n')[0]);
  return id > 0 ? id : 0;
}

/** A published page with [cooked-browse], set as the Cooked browse page. */
function ensureBrowsePage(): void {
  let pageId = firstId(wpCli(`wp post list --post_type=page --post_status=publish --name=${BROWSE_SLUG} --field=ID`));
  if (!pageId) {
    pageId = Number(
      wpCliArgs(['post', 'create', '--post_type=page', '--post_status=publish', '--post_title=Browse Recipes', `--post_name=${BROWSE_SLUG}`, '--post_content=[cooked-browse]', '--porcelain'])
    );
  }

  const settings = getCookedSettings();
  if (Number(settings.browse_page) !== pageId) {
    setCookedSettings({ ...settings, browse_page: pageId });
    // The browse rewrite rules are built from the browse page.
    wpCli('wp rewrite flush');
  }
}

/** At least one image in the Media Library, for the featured image pickers. */
function ensureMedia(): void {
  if (Number(wpCli('wp post list --post_type=attachment --post_mime_type=image --format=count')) > 0) {
    return;
  }
  wpCli(`wp media import ${SEED_IMAGE} --title="E2E image"`);
}

function ensureCategory(): void {
  try {
    wpCliArgs(['term', 'get', 'cp_recipe_category', SEED_CATEGORY.slug, '--by=slug', '--field=term_id']);
  } catch {
    wpCliArgs(['term', 'create', 'cp_recipe_category', SEED_CATEGORY.name, `--slug=${SEED_CATEGORY.slug}`]);
  }
}

/** Published recipes for the browse search, sort and category specs. */
function ensureRecipes(): void {
  const authorId = wpCliArgs(['user', 'get', ADMIN_USER.user, '--field=ID']);
  const cookedVersion = wpCliArgs(['eval', 'echo COOKED_VERSION;']);

  for (const recipe of Object.values(SEED_RECIPES)) {
    if (firstId(wpCliArgs(['post', 'list', '--post_type=cp_recipe', '--post_status=publish', `--title=${recipe.title}`, '--field=ID']))) {
      continue;
    }
    const recipeId = wpCliArgs([
      'post', 'create', '--post_type=cp_recipe', '--post_status=publish', `--post_title=${recipe.title}`,
      `--post_date=${recipe.date}`, `--post_author=${authorId}`, '--porcelain',
    ]);
    // What the recipe editor saves; without cooked_version Cooked reads the post as a 2.x recipe.
    wpCliArgs(['post', 'meta', 'update', recipeId, '_recipe_settings', JSON.stringify({ cooked_version: cookedVersion }), '--format=json']);
    if ('category' in recipe) {
      ensureCategory();
      wpCliArgs(['post', 'term', 'set', recipeId, 'cp_recipe_category', recipe.category, '--by=slug']);
    }
  }
}

export function seedSite(): void {
  ensureBrowsePage();
  ensureMedia();
  ensureRecipes();
}

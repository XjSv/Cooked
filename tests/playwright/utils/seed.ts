import { ADMIN_USER } from './users';
import { getCookedSettings, setCookedSettings, wpCli, wpCliArgs } from './wp-cli';

// Content the specs expect on any site, including a fresh wp-env stack in CI.
// Each step checks first, so an existing site keeps its own content.

export const BROWSE_SLUG = 'browse-recipes';
export const SEED_CATEGORY = { name: 'Bread', slug: 'bread' };
export const SEED_BREAKFAST_CATEGORY = { name: 'Breakfast', slug: 'breakfast' };

interface SeedRecipe {
  title: string;
  date: string;
  categories?: string[];
}

// Old dates keep the seed recipes at the end of "newest first" and the start of "oldest first".
export const SEED_RECIPES: Record<'bread' | 'beef' | 'chicken', SeedRecipe> = {
  // Bread has two categories so the multi-select filter has something to combine.
  bread: {
    title: 'E2E Seed Bread',
    date: '2000-01-01 10:00:00',
    categories: [SEED_CATEGORY.slug, SEED_BREAKFAST_CATEGORY.slug],
  },
  beef: { title: 'E2E Seed Beef Stew', date: '2000-01-02 10:00:00' },
  chicken: { title: 'E2E Seed Chicken Soup', date: '2000-01-03 10:00:00' },
};

const SEED_IMAGE = 'wp-content/plugins/cooked/tests/playwright/test-data/images/icon_pro.png';

// A recipe that uses every feature: the specs for the single recipe page, the editor tabs and
// the print view read it. Its settings are rewritten on every run, so it stays complete.
export const FULL_RECIPE = {
  title: 'E2E Seed Full Recipe',
  date: '2000-01-04 10:00:00',
  excerpt: 'A complete recipe for the end-to-end tests.',
  notes: 'Keeps for three days in an airtight tin.',
  ingredientHeading: 'Dough',
  substitute: 'oat milk',
  directionHeading: 'Bake',
  timerLabel: 'Rest the dough',
  allergens: ['wheat', 'milk', 'eggs'],
  servings: 4,
};

/** Named images for the full recipe's featured image, direction image and gallery. */
export const SEED_IMAGES = ['E2E Seed Image 1', 'E2E Seed Image 2', 'E2E Seed Image 3'];

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

/** The seed images, imported once each; returns their attachment IDs in order. */
function ensureSeedImages(): number[] {
  return SEED_IMAGES.map((title) => {
    const existing = firstId(wpCliArgs(['post', 'list', '--post_type=attachment', `--title=${title}`, '--field=ID']));
    if (existing) {
      return existing;
    }
    return Number(wpCliArgs(['media', 'import', SEED_IMAGE, `--title=${title}`, '--porcelain']));
  });
}

function ensureCategory(category: { name: string; slug: string }): void {
  try {
    wpCliArgs(['term', 'get', 'cp_recipe_category', category.slug, '--by=slug', '--field=term_id']);
  } catch {
    wpCliArgs(['term', 'create', 'cp_recipe_category', category.name, `--slug=${category.slug}`]);
  }
}

/** Published recipes for the browse search, sort and category specs. */
function ensureRecipes(): void {
  const authorId = wpCliArgs(['user', 'get', ADMIN_USER.user, '--field=ID']);
  const cookedVersion = wpCliArgs(['eval', 'echo COOKED_VERSION;']);

  for (const recipe of Object.values(SEED_RECIPES)) {
    let recipeId = firstId(wpCliArgs(['post', 'list', '--post_type=cp_recipe', '--post_status=publish', `--title=${recipe.title}`, '--field=ID']));

    if (!recipeId) {
      recipeId = Number(wpCliArgs([
        'post', 'create', '--post_type=cp_recipe', '--post_status=publish', `--post_title=${recipe.title}`,
        `--post_date=${recipe.date}`, `--post_author=${authorId}`, '--porcelain',
      ]));
      // What the recipe editor saves; without cooked_version Cooked reads the post as a 2.x recipe.
      wpCliArgs(['post', 'meta', 'update', String(recipeId), '_recipe_settings', JSON.stringify({ cooked_version: cookedVersion }), '--format=json']);
    }

    if (recipe.categories) {
      const names: Record<string, string> = {
        [SEED_CATEGORY.slug]: SEED_CATEGORY.name,
        [SEED_BREAKFAST_CATEGORY.slug]: SEED_BREAKFAST_CATEGORY.name,
      };

      for (const slug of recipe.categories) {
        ensureCategory({ name: names[slug] || slug, slug });
      }

      wpCliArgs(['post', 'term', 'set', String(recipeId), 'cp_recipe_category', ...recipe.categories, '--by=slug']);
    }
  }
}

/** The full recipe: every recipe feature filled in. */
function ensureFullRecipe(): void {
  const authorId = wpCliArgs(['user', 'get', ADMIN_USER.user, '--field=ID']);
  const cookedVersion = wpCliArgs(['eval', 'echo COOKED_VERSION;']);
  const [featured, step, extra] = ensureSeedImages();

  let recipeId = firstId(wpCliArgs(['post', 'list', '--post_type=cp_recipe', '--post_status=publish', `--title=${FULL_RECIPE.title}`, '--field=ID']));
  if (!recipeId) {
    recipeId = Number(wpCliArgs([
      'post', 'create', '--post_type=cp_recipe', '--post_status=publish', `--post_title=${FULL_RECIPE.title}`,
      `--post_date=${FULL_RECIPE.date}`, `--post_author=${authorId}`, '--porcelain',
    ]));
  }

  const settings = {
    cooked_version: cookedVersion,
    content: [
      '[cooked-info left="author,taxonomies,difficulty" right="print,fullscreen"]',
      '[cooked-excerpt]',
      '[cooked-image]',
      '[cooked-info left="servings" right="prep_time,cook_time,total_time"]',
      '[cooked-info left="allergens"]',
      '[cooked-ingredients]',
      '[cooked-directions]',
      '[cooked-notes show_header=true]',
      '[cooked-nutrition]',
      '[cooked-gallery]',
    ].join(' '),
    difficulty_level: '2',
    prep_time: 20,
    cook_time: 35,
    total_time: 55,
    excerpt: FULL_RECIPE.excerpt,
    seo_description: 'Seed recipe with every Cooked feature filled in.',
    notes: FULL_RECIPE.notes,
    allergens: FULL_RECIPE.allergens,
    ingredients: {
      i1: { section_heading_name: FULL_RECIPE.ingredientHeading, section_heading_element: 'h3' },
      i2: {
        amount: '2', measurement: 'cup', name: 'strong white flour', url: '', description: '',
        substitutions: [], sub_amount: '', sub_measurement: '', sub_name: '',
      },
      i3: {
        amount: '1', measurement: 'cup', name: 'whole milk', url: '', description: '',
        substitutions: [
          { amount: '1', measurement: 'cup', name: FULL_RECIPE.substitute },
          { amount: '1', measurement: 'cup', name: 'water' },
        ],
        sub_amount: '1', sub_measurement: 'cup', sub_name: FULL_RECIPE.substitute,
      },
      i4: { amount: '1 1/2', measurement: 'tsp', name: 'salt', url: '', description: '', substitutions: [], sub_amount: '', sub_measurement: '', sub_name: '' },
      i5: { amount: '2', measurement: '', name: 'eggs', url: '', description: '', substitutions: [], sub_amount: '', sub_measurement: '', sub_name: '' },
    },
    directions: {
      d1: { image: '', content: 'Mix the flour, milk, salt and eggs into a soft dough.', video: '' },
      d2: { image: String(step), content: `[cooked-timer minutes="1"]${FULL_RECIPE.timerLabel}[/cooked-timer] until it is smooth.`, video: '' },
      d3: { section_heading_name: FULL_RECIPE.directionHeading, section_heading_element: 'h3' },
      d4: { image: '', content: 'Bake until golden.', video: '' },
    },
    nutrition: {
      servings: String(FULL_RECIPE.servings), serving_size: '1 slice', calories: '320', fat: '12', sat_fat: '3', trans_fat: '0',
      cholesterol: '45', sodium: '480', carbs: '42', fiber: '2', sugars: '4', added_sugars: '3', protein: '9',
      vitamin_d: '2', calcium: '8', iron: '10', potassium: '300',
    },
    gallery: {
      type: 'cooked', items: [String(featured), String(step), String(extra)], video_url: '',
      style: 'thumbs', ratio: '4/3', fit: 'cover', loop: '1', autoplay: '0',
    },
  };

  wpCliArgs(['post', 'meta', 'update', String(recipeId), '_recipe_settings', JSON.stringify(settings), '--format=json']);

  // Notes are off by default in Global Recipe Toggles; turn them on so the notes render.
  // A fresh site has no saved toggles, so read the effective list (saved or default).
  const toggles = JSON.parse(wpCliArgs(['eval', 'echo wp_json_encode( Cooked_Settings::get()["recipe_info_display_options"] );'])) as string[];
  if (!toggles.includes('notes')) {
    setCookedSettings({ ...getCookedSettings(), recipe_info_display_options: [...toggles, 'notes'] });
  }
  wpCliArgs(['post', 'meta', 'update', String(recipeId), '_thumbnail_id', String(featured)]);
  ensureCategory(SEED_CATEGORY);
  wpCliArgs(['post', 'term', 'set', String(recipeId), 'cp_recipe_category', SEED_CATEGORY.slug, '--by=slug']);
}

export function fullRecipeId(): string {
  return wpCliArgs(['post', 'list', '--post_type=cp_recipe', `--title=${FULL_RECIPE.title}`, '--field=ID']).split('\n')[0];
}

export function seedSite(): void {
  ensureBrowsePage();
  ensureMedia();
  ensureRecipes();
  ensureFullRecipe();
}
